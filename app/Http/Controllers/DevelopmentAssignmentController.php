<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DevelopmentAssignmentController extends Controller
{
    public function index()
    {
        return view('asignacion_lotificaciones.index');
    }

    public function options()
    {
        $roles = DB::table('roles')
            ->orderBy('nombre')
            ->get(['id as value', 'nombre as text']);

        $users = DB::table('users as u')
            ->join('personal as p', 'p.id', '=', 'u.personal_id')
            ->whereNull('u.fecha_baja')
            ->orderBy('u.alias')
            ->get([
                'u.id as value',
                DB::raw("u.alias || ' - ' || p.nombres || ' ' || p.apellidos as text")
            ]);

        $developments = DB::table('developments')
            ->whereNull('fecha_baja')
            ->orderBy('nombre')
            ->get(['id as value', 'nombre as text']);

        return response()->json([
            'roles' => $roles,
            'users' => $users,
            'developments' => $developments,
        ]);
    }

    public function roleAssignments(int $roleId)
    {
        $assigned = DB::table('role_developments')
            ->where('role_id', $roleId)
            ->pluck('development_id')
            ->map(fn($v) => (int)$v)
            ->values();

        return response()->json([
            'ok' => true,
            'assigned' => $assigned,
        ]);
    }

    public function saveRoleAssignments(Request $request, int $roleId)
    {
        $data = Validator::make($request->all(), [
            'development_ids' => ['nullable', 'array'],
            'development_ids.*' => ['integer', 'exists:developments,id'],
        ])->validate();

        $ids = collect($data['development_ids'] ?? [])
            ->unique()
            ->map(fn($v) => (int)$v)
            ->values()
            ->all();

        DB::beginTransaction();

        try {
            DB::table('role_developments')->where('role_id', $roleId)->delete();

            $rows = [];
            foreach ($ids as $developmentId) {
                $rows[] = [
                    'role_id' => $roleId,
                    'development_id' => $developmentId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($rows)) {
                DB::table('role_developments')->insert($rows);
            }

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Asignación por rol guardada correctamente.'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function userAssignments(int $userId)
    {
        $assigned = DB::table('user_developments')
            ->where('user_id', $userId)
            ->pluck('development_id')
            ->map(fn($v) => (int)$v)
            ->values();

        $user = DB::table('users')->where('id', $userId)->first();

        $roleAssigned = collect();
        if ($user) {
            $roleAssigned = DB::table('role_developments')
                ->where('role_id', $user->role_id)
                ->pluck('development_id')
                ->map(fn($v) => (int)$v)
                ->values();
        }

        return response()->json([
            'ok' => true,
            'assigned' => $assigned,
            'role_assigned' => $roleAssigned,
        ]);
    }

    public function saveUserAssignments(Request $request, int $userId)
    {
        $data = Validator::make($request->all(), [
            'development_ids' => ['nullable', 'array'],
            'development_ids.*' => ['integer', 'exists:developments,id'],
        ])->validate();

        $ids = collect($data['development_ids'] ?? [])
            ->unique()
            ->map(fn($v) => (int)$v)
            ->values()
            ->all();

        DB::beginTransaction();

        try {
            DB::table('user_developments')->where('user_id', $userId)->delete();

            $rows = [];
            foreach ($ids as $developmentId) {
                $rows[] = [
                    'user_id' => $userId,
                    'development_id' => $developmentId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($rows)) {
                DB::table('user_developments')->insert($rows);
            }

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Asignación por usuario guardada correctamente.'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}