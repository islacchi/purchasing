<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
            if (!empty($filterFrom) || !empty($filterTo)) {
                $logDate = date('Y-m-d', strtotime($log['time']));
                if (!empty($filterFrom) && $logDate < $filterFrom) {
                    $matchDateRange = false;
                }
                if (!empty($filterTo) && $logDate > $filterTo) {
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
     *
     * Displays dummy user data with role/privilege information.
     * Pending real User/Role model integration.
     *
     * @return Response
     */
    public function privileges()
    {
        $users = [
            [
                'id' => 1,
                'name' => 'J. Santos',
                'email' => 'j.santos@company.com',
                'role' => 'Procurement officer',
                'status' => 'Active',
            ],
            [
                'id' => 2,
                'name' => 'M. Reyes',
                'email' => 'm.reyes@company.com',
                'role' => 'Admin',
                'status' => 'Active',
            ],
            [
                'id' => 3,
                'name' => 'A. Cruz',
                'email' => 'a.cruz@company.com',
                'role' => 'Procurement officer',
                'status' => 'Inactive',
            ],
        ];

        $roles = [
            ['name' => 'Admin', 'description' => 'Full system access'],
            ['name' => 'Procurement officer', 'description' => 'Can create and manage purchase orders'],
            ['name' => 'Viewer', 'description' => 'Read-only access to all modules'],
        ];

        return view('settings.privileges', compact('users', 'roles'));
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