<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\RoleModuleAccess;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\BackupStorage;
use App\Support\DatabaseDumper;
use App\Support\ModuleAccess;
use App\Support\Pagination;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Renders the settings page.
 *
 * NOTE: Not currently wired up to a route — the `settings` route uses
 * PageController to render `Settings.index`.
 */
class SettingsController extends Controller
{
    /**
     * Show the settings page.
     *
     * @return Response
     */
    public function index()
    {
        return view('settings');
    }

    /**
     * Show the activity logs page.
     *
     * Reads the real activity_logs table (written by App\Support\ActivityLogger
     * from every create/edit/delete path), newest first, 50 per page.
     * Filters: search, action, module, user, and a created_at date range.
     */
    public function logs(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $filterAction = $request->get('action', 'all');
        $filterModule = $request->get('module', 'all');
        $filterUser = $request->get('user', 'all');
        $filterFrom = trim((string) $request->get('from_date', ''));
        $filterTo = trim((string) $request->get('to_date', ''));
        $dateNotice = null;

        // Guard the date range: ignore malformed dates and swap an inverted
        // From/To instead of silently returning zero rows.
        $fromDate = $this->validDate($filterFrom);
        $toDate = $this->validDate($filterTo);

        if ($filterFrom !== '' && $fromDate === null) {
            $dateNotice = 'The "From" date was not recognized and was ignored.';
        }

        if ($filterTo !== '' && $toDate === null) {
            $dateNotice = trim(($dateNotice ? $dateNotice.' ' : '').'The "To" date was not recognized and was ignored.');
        }

        if ($fromDate !== null && $toDate !== null && $fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
            [$filterFrom, $filterTo] = [$filterTo, $filterFrom];
            $dateNotice = 'The "From" date was after the "To" date — the range was swapped.';
        }

        $perPage = Pagination::perPage($request, 50);

        $logs = ActivityLog::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('user_name', 'like', "%{$search}%")
                        ->orWhere('module', 'like', "%{$search}%")
                        ->orWhere('record_label', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%");
                });
            })
            ->when($filterAction !== 'all', fn ($query) => $query->where('action', $filterAction))
            ->when($filterModule !== 'all', fn ($query) => $query->where('module', $filterModule))
            ->when($filterUser !== 'all', fn ($query) => $query->where('user_name', $filterUser))
            ->when($fromDate !== null, fn ($query) => $query->whereDate('created_at', '>=', $fromDate))
            ->when($toDate !== null, fn ($query) => $query->whereDate('created_at', '<=', $toDate))
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $activityLogs = $logs->getCollection()->map(fn (ActivityLog $log) => [
            'time' => $log->created_at?->format('Y-m-d H:i:s'),
            'user' => $log->user_name,
            'role' => $log->role_name,
            'action' => $log->action,
            'module' => $log->module,
            'record' => $log->record_label,
        ]);

        $modules = ActivityLog::query()->distinct()->orderBy('module')->pluck('module');
        $users = ActivityLog::query()->distinct()->orderBy('user_name')->pluck('user_name');

        return view('settings.logs', compact(
            'activityLogs',
            'logs',
            'search',
            'perPage',
            'filterAction',
            'filterModule',
            'filterUser',
            'filterFrom',
            'filterTo',
            'dateNotice',
            'modules',
            'users'
        ));
    }

    /**
     * Return the value as a canonical Y-m-d string if it is a real calendar
     * date, or null if it is empty or malformed.
     */
    private function validDate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        $date = DateTime::createFromFormat('Y-m-d', $value);

        if ($date === false || $date->format('Y-m-d') !== $value) {
            return null;
        }

        return $value;
    }

    /**
     * Show the user privilege management page.
     */
    public function privileges(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $users = $this->privilegeUsers($search);

        $roles = Role::query()
            ->withCount('users')
            ->orderBy('name')
            ->get();

        // The matrix editor manages every non-administrator role. Administrator
        // is implicit full access and is not listed here.
        $matrixRoles = $roles->reject(fn (Role $role) => $role->name === 'Administrator')->values();

        $moduleAccess = RoleModuleAccess::query()
            ->whereIn('role_id', $matrixRoles->pluck('id'))
            ->get()
            ->groupBy('role_id');

        return view('Settings.privileges', compact(
            'users',
            'roles',
            'matrixRoles',
            'moduleAccess',
            'search',
        ));
    }

    /**
     * Persist the role × module access matrix. Administrator is not editable.
     */
    public function updateModuleAccess(Request $request)
    {
        $validated = $request->validate([
            'access' => ['required', 'array'],
            'access.*' => ['array'],
            'access.*.*' => ['required', 'string', Rule::in([
                RoleModuleAccess::LEVEL_NONE,
                RoleModuleAccess::LEVEL_VIEW,
                RoleModuleAccess::LEVEL_MANAGE,
            ])],
        ]);

        $names = ModuleAccess::MODULES;
        $administratorIds = Role::query()->where('name', 'Administrator')->pluck('id')->all();

        DB::transaction(function () use ($validated, $names, $administratorIds) {
            foreach ($validated['access'] as $roleId => $modules) {
                if (! ctype_digit((string) $roleId) || in_array((int) $roleId, $administratorIds, true)) {
                    continue;
                }

                $role = Role::query()->find((int) $roleId);

                if (! $role) {
                    continue;
                }

                foreach ($modules as $module => $level) {
                    if (! array_key_exists($module, $names)) {
                        continue;
                    }

                    RoleModuleAccess::query()->updateOrCreate(
                        ['role_id' => $role->id, 'module' => $module],
                        ['level' => $level],
                    );
                }
            }

            ActivityLogger::log('Edited', 'Users', 'Role module access matrix');
        });

        ModuleAccess::flush();

        return redirect()
            ->route('settings.privileges')
            ->with('status', 'Role access updated.');
    }

    /**
     * Return the matching user rows for live search.
     */
    public function privilegeSearch(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $users = $this->privilegeUsers($search);

        return view('Settings.privileges_user_rows', compact('users', 'search'));
    }

    /**
     * Build the filtered user query used by the privilege screens.
     */
    private function privilegeUsers(string $search)
    {
        return User::query()
            ->with('role')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('role', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Show the form for creating a new user account.
     */
    public function privilegeCreate()
    {
        $roles = Role::query()->orderBy('name')->get();

        return view('Settings.privilege_form', [
            'user' => null,
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created user account.
     */
    public function privilegeStore(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('users', 'name')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'role_id' => $validated['role_id'],
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_active' => $validated['status'] === 'Active',
            ]);

            ActivityLogger::log('Created', 'Users', $user->name, $user);
        });

        return redirect()
            ->route('settings.privileges')
            ->with('status', 'User account created successfully.');
    }

    /**
     * Show the form for editing an existing user account.
     */
    public function privilegeEdit($id)
    {
        $user = User::query()->with('role')->findOrFail($id);
        $roles = Role::query()->orderBy('name')->get();

        return view('Settings.privilege_form', compact('user', 'roles'));
    }

    /**
     * Update an existing user account.
     */
    public function privilegeUpdate(Request $request, $id)
    {
        $user = User::query()->with('role')->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('users', 'name')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $willBeAdministrator = $role->name === 'Administrator';
        $willBeActive = $validated['status'] === 'Active';
        $editingSelf = $request->user()->id === $user->id;

        if ($editingSelf && (! $willBeAdministrator || ! $willBeActive)) {
            return back()
                ->withInput()
                ->with('error', 'You cannot remove your own administrator access or deactivate your own account.');
        }

        if ($user->isAdministrator() && (! $willBeAdministrator || ! $willBeActive)
            && ! $this->hasOtherActiveAdministrator($user)) {
            return back()
                ->withInput()
                ->with('error', 'At least one active administrator account must remain.');
        }

        DB::transaction(function () use ($user, $validated, $willBeActive) {
            $user->fill([
                'role_id' => $validated['role_id'],
                'name' => $validated['name'],
                'email' => $validated['email'],
                'is_active' => $willBeActive,
            ]);

            if (! empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();

            ActivityLogger::log('Edited', 'Users', $user->name, $user);
        });

        return redirect()
            ->route('settings.privileges')
            ->with('status', 'User account updated successfully.');
    }

    /**
     * Activate or deactivate a user account.
     */
    public function privilegeToggleStatus(Request $request, $id)
    {
        $user = User::query()->with('role')->findOrFail($id);

        if ($user->is_active && $request->user()->id === $user->id) {
            return redirect()
                ->route('settings.privileges')
                ->with('error', 'You cannot deactivate your own account.');
        }

        if ($user->is_active && $user->isAdministrator() && ! $this->hasOtherActiveAdministrator($user)) {
            return redirect()
                ->route('settings.privileges')
                ->with('error', 'At least one active administrator account must remain.');
        }

        DB::transaction(function () use ($user) {
            $user->is_active = ! $user->is_active;
            $user->save();

            ActivityLogger::log('Edited', 'Users', $user->name, $user);
        });

        return redirect()
            ->route('settings.privileges')
            ->with('status', $user->is_active
                ? 'User account activated successfully.'
                : 'User account deactivated successfully.');
    }

    /**
     * Delete a user account.
     */
    public function privilegeDestroy(Request $request, $id)
    {
        $user = User::query()->with('role')->findOrFail($id);

        if ($request->user()->id === $user->id) {
            return redirect()
                ->route('settings.privileges')
                ->with('error', 'You cannot delete your own account.');
        }

        if ($user->is_active && $user->isAdministrator() && ! $this->hasOtherActiveAdministrator($user)) {
            return redirect()
                ->route('settings.privileges')
                ->with('error', 'At least one active administrator account must remain.');
        }

        DB::transaction(function () use ($user) {
            $user->delete();

            ActivityLogger::log('Deleted', 'Users', $user->name, $user);
        });

        return redirect()
            ->route('settings.privileges')
            ->with('status', 'User account deleted successfully.');
    }

    /**
     * Determine whether another active administrator exists besides the given user.
     */
    private function hasOtherActiveAdministrator(User $user): bool
    {
        return User::query()
            ->whereKeyNot($user->getKey())
            ->where('is_active', true)
            ->whereHas('role', function ($query) {
                $query->where('name', 'Administrator');
            })
            ->exists();
    }

    /**
     * Show the database back-up page.
     *
     * Backups are stored under the local disk's private/backups directory, created on demand
     * from the configured MySQL database, and older ones are pruned to the last 15.
     *
     * @return Response
     */
    public function backup()
    {
        $config = config('database.connections.'.config('database.default'));
        $dbInfo = [
            'driver' => strtoupper((string) ($config['driver'] ?? 'unknown')),
            'database' => (string) ($config['database'] ?? ''),
            'host' => (string) ($config['host'] ?? 'localhost'),
            'port' => (string) ($config['port'] ?? '3306'),
            'is_mysql' => ($config['driver'] ?? null) === 'mysql',
        ];

        $backups = BackupStorage::records();
        $latest = $backups === [] ? null : $backups[0];

        return view('settings.backup', compact('dbInfo', 'backups', 'latest'));
    }

    /**
     * Create a full database backup (structure and contents) and queue nothing — runs synchronously.
     */
    public function storeBackup()
    {
        $filename = BackupStorage::createFilename();
        $absolutePath = BackupStorage::path($filename);

        try {
            app(DatabaseDumper::class)->dump($absolutePath);
        } catch (RuntimeException $exception) {
            @unlink($absolutePath);

            return redirect()
                ->route('settings.backup')
                ->withErrors(['backup' => $exception->getMessage()]);
        }

        BackupStorage::prune();

        ActivityLogger::log('Created', 'Settings', "Database back-up: {$filename}");

        return redirect()
            ->route('settings.backup')
            ->with('status', 'Database back-up created.');
    }

    /**
     * Download one backup file, guarded against traversal and unknown filenames.
     */
    public function downloadBackup(string $file)
    {
        if (! BackupStorage::isValidFilename($file) || ! Storage::disk('local')->exists(BackupStorage::DIRECTORY.'/'.$file)) {
            abort(404);
        }

        ActivityLogger::log('Downloaded', 'Settings', "Database back-up: {$file}");

        return response()->download(
            Storage::disk('local')->path(BackupStorage::DIRECTORY.'/'.$file),
            $file,
            ['Content-Type' => 'application/sql'],
        );
    }

    /**
     * Delete one backup file, guarded against traversal and unknown filenames.
     */
    public function destroyBackup(string $file)
    {
        if (! BackupStorage::isValidFilename($file) || ! Storage::disk('local')->exists(BackupStorage::DIRECTORY.'/'.$file)) {
            abort(404);
        }

        Storage::disk('local')->delete(BackupStorage::DIRECTORY.'/'.$file);

        ActivityLogger::log('Deleted', 'Settings', "Database back-up: {$file}");

        return redirect()
            ->route('settings.backup')
            ->with('status', 'Database back-up deleted.');
    }
}
