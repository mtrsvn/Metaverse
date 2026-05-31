<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AuditLogger
{
    public static function logAction(int $userId, string $action): void
    {
        if ($userId <= 0 || $action === '') {
            return;
        }

        DB::table('audit_log')->insert([
            'user_id' => $userId,
            'action' => $action,
        ]);
    }
}
