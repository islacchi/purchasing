<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class ActivityLogger
{
    /**
     * Write one entry to the activity log.
     *
     * user_name and role_name are copied (denormalized) at log time so the
     * entry still reads correctly after the user is renamed, deactivated, or
     * moved to a different role.
     *
     * This must run INSIDE the DB::transaction that performs the action — the
     * log row and the action then commit or roll back together. An audit
     * entry must never claim something happened when it did not.
     */
    public static function log(string $action, string $module, string $recordLabel, ?Model $subject = null): ActivityLog
    {
        $user = Auth::user();

        if ($user === null) {
            throw new RuntimeException('ActivityLogger requires an authenticated user.');
        }

        return ActivityLog::query()->create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'role_name' => $user->role?->name,
            'action' => $action,
            'module' => $module,
            'record_label' => $recordLabel,
            'subject_type' => $subject === null ? null : $subject::class,
            'subject_id' => $subject?->getKey(),
            'created_at' => now(),
        ]);
    }
}
