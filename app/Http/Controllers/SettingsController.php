<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

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
     * Displays dummy activity records of user actions across modules
     * (create, edit, delete). Pending real audit-log table integration.
     *
     * @return Response
     */
    public function logs(Request $request)
    {
        $activityLogs = [
            [
                'user' => 'J. Santos',
                'role' => 'Procurement officer',
                'action' => 'Created',
                'module' => 'Projects',
                'record' => 'Purchase of pharma supplies Q3',
                'time' => '2026-07-18 09:12:00',
            ],
            [
                'user' => 'M. Reyes',
                'role' => 'Admin',
                'action' => 'Edited',
                'module' => 'Purchase Order',
                'record' => 'PO-2026-0117',
                'time' => '2026-07-18 08:45:00',
            ],
            [
                'user' => 'A. Cruz',
                'role' => 'Procurement officer',
                'action' => 'Deleted',
                'module' => 'Preplist',
                'record' => 'Office Equipment FY2026',
                'time' => '2026-07-17 16:30:00',
            ],
            [
                'user' => 'J. Santos',
                'role' => 'Procurement officer',
                'action' => 'Created',
                'module' => 'Quotation',
                'record' => 'RFQ-2026-0090',
                'time' => '2026-07-17 14:15:00',
            ],
            [
                'user' => 'M. Reyes',
                'role' => 'Admin',
                'action' => 'Edited',
                'module' => 'Entities',
                'record' => 'PGH Manila',
                'time' => '2026-07-16 11:05:00',
            ],
            [
                'user' => 'A. Cruz',
                'role' => 'Procurement officer',
                'action' => 'Created',
                'module' => 'Purchase Order',
                'record' => 'PO-2026-0116',
                'time' => '2026-07-15 10:22:00',
            ],
            [
                'user' => 'J. Santos',
                'role' => 'Procurement officer',
                'action' => 'Deleted',
                'module' => 'Quotation',
                'record' => 'RFQ-2026-0088',
                'time' => '2026-07-14 09:48:00',
            ],
            [
                'user' => 'M. Reyes',
                'role' => 'Admin',
                'action' => 'Created',
                'module' => 'Entities',
                'record' => 'Triple Tact',
                'time' => '2026-07-13 15:10:00',
            ],
        ];

        // Get filter values from request
        $search = $request->get('search', '');
        $filterAction = $request->get('action', 'all');
        $filterModule = $request->get('module', 'all');
        $filterUser = $request->get('user', 'all');
        $filterFrom = $request->get('from_date', '');
        $filterTo = $request->get('to_date', '');

        // Apply filters
        $activityLogs = array_filter($activityLogs, function ($log) use ($search, $filterAction, $filterModule, $filterUser, $filterFrom, $filterTo) {
            $matchSearch = empty($search) ||
                stripos($log['user'], $search) !== false ||
                stripos($log['module'], $search) !== false ||
                stripos($log['record'], $search) !== false ||
                stripos($log['action'], $search) !== false;

            $matchAction = $filterAction === 'all' || $log['action'] === $filterAction;
            $matchModule = $filterModule === 'all' || $log['module'] === $filterModule;
            $matchUser = $filterUser === 'all' || $log['user'] === $filterUser;

            // Date range filtering
            $matchDateRange = true;
            if (! empty($filterFrom) || ! empty($filterTo)) {
                $logDate = date('Y-m-d', strtotime($log['time']));
                if (! empty($filterFrom) && $logDate < $filterFrom) {
                    $matchDateRange = false;
                }
                if (! empty($filterTo) && $logDate > $filterTo) {
                    $matchDateRange = false;
                }
            }

            return $matchSearch && $matchAction && $matchModule && $matchUser && $matchDateRange;
        });

        // Re-index array after filtering
        $activityLogs = array_values($activityLogs);

        // Unique values for filter dropdowns
        $actions = array_unique(array_column($activityLogs, 'action'));
        $modules = array_unique(array_column($activityLogs, 'module'));
        $users = array_unique(array_column($activityLogs, 'user'));

        return view('settings.logs', compact(
            'activityLogs',
            'search',
            'filterAction',
            'filterModule',
            'filterUser',
            'filterFrom',
            'filterTo',
            'actions',
            'modules',
            'users'
        ));
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

        return view('Settings.privileges', compact('users', 'roles', 'search'));
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
     * Show the database backup page.
     *
     * Displays backup options and dummy backup history.
     * Pending real backup engine integration.
     *
     * @return Response
     */
    public function backup()
    {
        $dbPath = database_path('database.sqlite');
        $dbInfo = null;

        if (file_exists($dbPath)) {
            $dbInfo = [
                'name' => 'database.sqlite',
                'size' => filesize($dbPath),
                'modified' => date('Y-m-d H:i:s', filemtime($dbPath)),
                'path' => $dbPath,
            ];
        }

        $backups = [
            [
                'filename' => 'purchasing_backup_2026-07-18.sqlite',
                'size' => 40960,
                'created' => '2026-07-18 08:30:00',
            ],
            [
                'filename' => 'purchasing_backup_2026-07-15.sqlite',
                'size' => 36864,
                'created' => '2026-07-15 14:15:00',
            ],
        ];

        return view('settings.backup', compact('dbInfo', 'backups'));
    }
}
