<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{
    public function index()
    {
        return view('permisos.index');
    }

    public function rolesSelect()
    {
        $data = DB::table('roles')
            ->orderBy('nombre')
            ->get(['id as value', 'nombre as text']);

        return response()->json($data);
    }

    public function usersSelect()
    {
        $data = DB::table('users as u')
            ->join('personal as p', 'p.id', '=', 'u.personal_id')
            ->orderBy('u.alias')
            ->get([
                'u.id as value',
                DB::raw("u.alias || ' - ' || p.nombres || ' ' || p.apellidos as text")
            ]);

        return response()->json($data);
    }

    public function roleTree(int $roleId)
    {
        $menus = DB::table('menus')->orderBy('orden')->orderBy('nombre')->get();
        $assigned = DB::table('role_menu_permissions')
            ->where('role_id', $roleId)
            ->where('can_view', true)
            ->pluck('menu_id')
            ->map(fn ($v) => (int)$v)
            ->toArray();

        return response()->json($this->mapTree($menus, $assigned));
    }  

    public function userTree(int $userId)
    {
        $user = DB::table('users')->where('id', $userId)->first();
        abort_if(!$user, 404, 'Usuario no encontrado');

        $menus = DB::table('menus')->orderBy('orden')->orderBy('nombre')->get();

        $roleAssigned = DB::table('role_menu_permissions')
            ->where('role_id', $user->role_id)
            ->where('can_view', true)
            ->pluck('menu_id')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        $userAssigned = DB::table('user_menu_permissions')
            ->where('user_id', $userId)
            ->where('can_view', true)
            ->pluck('menu_id')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        $allSelected = array_values(array_unique(array_merge($roleAssigned, $userAssigned)));

        $tree = collect($menus)->map(function ($m) use ($roleAssigned, $allSelected) {
            $menuId = (int) $m->id;
            $isFromRole = in_array($menuId, $roleAssigned, true);

            return [
                'id' => (string) $m->id,
                'parent' => $m->parent_id ? (string) $m->parent_id : '#',
                'text' => $isFromRole ? $m->nombre . ' (por rol)' : $m->nombre,
                'icon' => 'fa fa-folder',
                'state' => [
                    'opened' => true,
                    'selected' => in_array($menuId, $allSelected, true),
                    'disabled' => $isFromRole,
                ],
                'li_attr' => [
                    'data-ruta' => $m->ruta,
                    'data-clave' => $m->clave,
                    'data-from-role' => $isFromRole ? '1' : '0',
                ],
            ];
        })->values();

        return response()->json($tree);
    }

    public function saveRolePermissions(Request $request, int $roleId)
    {
        $ids = collect($request->input('menu_ids', []))
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        DB::beginTransaction();

        try {
            DB::table('role_menu_permissions')
                ->where('role_id', $roleId)
                ->delete();

            foreach ($ids as $menuId) {
                DB::table('role_menu_permissions')->insert([
                    'role_id' => $roleId,
                    'menu_id' => $menuId,
                    'can_view' => true,
                    'can_create' => true,
                    'can_update' => true,
                    'can_delete' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Permisos por rol guardados correctamente',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function saveUserPermissions(Request $request, int $userId)
    {
        $user = DB::table('users')->where('id', $userId)->first();
        abort_if(!$user, 404, 'Usuario no encontrado');

        $selectedIds = collect($request->input('menu_ids', []))
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        $roleIds = DB::table('role_menu_permissions')
            ->where('role_id', $user->role_id)
            ->where('can_view', true)
            ->pluck('menu_id')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        $extraIds = array_values(array_diff($selectedIds, $roleIds));

        DB::beginTransaction();

        try {
            DB::table('user_menu_permissions')
                ->where('user_id', $userId)
                ->delete();

            foreach ($extraIds as $menuId) {
                DB::table('user_menu_permissions')->insert([
                    'user_id' => $userId,
                    'menu_id' => $menuId,
                    'can_view' => true,
                    'can_create' => true,
                    'can_update' => true,
                    'can_delete' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Permisos extra por usuario guardados correctamente',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function mapTree($menus, array $selected = [])
    {
        return collect($menus)->map(function ($m) use ($selected) {
            return [
                'id' => (string) $m->id,
                'parent' => $m->parent_id ? (string) $m->parent_id : '#',
                'text' => $m->nombre,
                'icon' => 'fa fa-folder',
                'state' => [
                    'opened' => true,
                    'selected' => in_array((int)$m->id, $selected, true),
                ],
                'li_attr' => [
                    'data-ruta' => $m->ruta,
                    'data-clave' => $m->clave,
                ],
            ];
        })->values();
    }
}