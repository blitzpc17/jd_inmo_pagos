<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class ShareMenu
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Session::get('auth_user');

        if ($user) {
            $menuData = $this->buildMenu((int) $user['id'], (int) $user['role_id']);
            view()->share('dynamicMenu', $menuData['tree']);
            view()->share('userPermissions', $menuData['permissions']);
        } else {
            view()->share('dynamicMenu', []);
            view()->share('userPermissions', []);
        }

        return $next($request);
    }

    protected function buildMenu(int $userId, int $roleId): array
    {
        $rows = DB::select("
            SELECT DISTINCT
                m.id,
                m.nombre,
                m.clave,
                m.ruta,
                m.icono,
                m.parent_id,
                m.orden,
                m.es_menu
            FROM menus m
            INNER JOIN statuses s ON s.id = m.status_id
            INNER JOIN processes p ON p.id = s.process_id
            WHERE p.clave = 'GENERAL'
              AND s.clave = 'ACTIVE'
              AND (
                    EXISTS (
                        SELECT 1
                        FROM role_menu_permissions rmp
                        WHERE rmp.role_id = ?
                          AND rmp.menu_id = m.id
                          AND rmp.can_view = TRUE
                    )
                    OR EXISTS (
                        SELECT 1
                        FROM user_menu_permissions ump
                        WHERE ump.user_id = ?
                          AND ump.menu_id = m.id
                          AND ump.can_view = TRUE
                    )
              )
            ORDER BY m.parent_id NULLS FIRST, m.orden ASC, m.nombre ASC
        ", [$roleId, $userId]);

        $items = collect($rows)->map(fn ($r) => (array) $r)->keyBy('id')->toArray();
        $permissions = array_column($items, 'clave');

        $tree = [];
        foreach ($items as $id => &$item) {
            $item['children'] = [];
        }

        foreach ($items as $id => &$item) {
            if (!$item['es_menu']) {
                continue;
            }
            if (!empty($item['parent_id']) && isset($items[$item['parent_id']])) {
                $items[$item['parent_id']]['children'][] = &$item;
            } else {
                $tree[] = &$item;
            }
        }

        return [
            'tree' => $tree,
            'permissions' => $permissions
        ];
    }
}