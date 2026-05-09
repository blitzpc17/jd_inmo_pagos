<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

trait DevelopmentVisibility
{
    protected function visibleDevelopmentIdsForUser(int $userId): array
    {
        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) {
            return [];
        }

        $role = DB::table('roles')->where('id', $user->role_id)->first();
        if ($role && mb_strtolower(trim($role->nombre)) === 'admin') {
            return DB::table('developments')->pluck('id')->map(fn($v) => (int)$v)->all();
        }

        $roleIds = DB::table('role_developments')
            ->where('role_id', $user->role_id)
            ->pluck('development_id')
            ->map(fn($v) => (int)$v)
            ->all();

        $userIds = DB::table('user_developments')
            ->where('user_id', $userId)
            ->pluck('development_id')
            ->map(fn($v) => (int)$v)
            ->all();

        return array_values(array_unique(array_merge($roleIds, $userIds)));
    }
}