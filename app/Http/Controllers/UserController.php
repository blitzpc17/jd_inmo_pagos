<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return view('usuarios.index');
    }

    public function datatable()
    {
        $rows = DB::table('users as u')
            ->join('personal as p', 'p.id', '=', 'u.personal_id')
            ->join('roles as r', 'r.id', '=', 'u.role_id')
            ->join('statuses as s', 's.id', '=', 'u.status_id')
            ->leftJoin('positions as po', 'po.id', '=', 'p.position_id')
            ->select([
                'u.id',
                'u.alias',
                'p.nombres',
                'p.apellidos',
                'p.email',
                'p.telefono',
                'r.nombre as rol',
                's.nombre as estado',
                'po.nombre as puesto',
                'u.created_at',
            ])
            ->orderByDesc('u.id')
            ->get()
            ->map(function ($r) {
                $r->acciones = '
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-primary btn-edit" data-id="'.$r->id.'">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger btn-delete" data-id="'.$r->id.'">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                ';
                return $r;
            });

        return response()->json(['data' => $rows]);
    }

    public function createOptions()
    {
        $roles = DB::table('roles')->orderBy('nombre')->get(['id as value', 'nombre as text']);
        $positions = DB::table('positions')->orderBy('nombre')->get(['id as value', 'nombre as text']);

        $statuses = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'GENERAL')
            ->orderBy('s.nombre')
            ->get(['s.id as value', 's.nombre as text']);

        return response()->json([
            'roles' => $roles,
            'positions' => $positions,
            'statuses' => $statuses,
        ]);
    }

    public function show(int $id)
    {
        $row = DB::table('users as u')
            ->join('personal as p', 'p.id', '=', 'u.personal_id')
            ->select([
                'u.id',
                'u.alias',
                'u.role_id',
                'u.status_id',
                'p.id as personal_id',
                'p.nombres',
                'p.apellidos',
                'p.telefono',
                'p.email',
                'p.direccion',
                'p.position_id',
            ])
            ->where('u.id', $id)
            ->first();

        abort_if(!$row, 404, 'Usuario no encontrado');

        return response()->json([
            'ok' => true,
            'data' => $row,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'alias' => ['required', 'string', 'max:80', 'unique:users,alias'],
            'password' => ['required', 'string', 'min:6'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'status_id' => ['required', 'integer', 'exists:statuses,id'],
            'nombres' => ['required', 'string', 'max:150'],
            'apellidos' => ['required', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150', 'unique:personal,email'],
            'direccion' => ['nullable', 'string'],
            'position_id' => ['required', 'integer', 'exists:positions,id'],
        ]);

        $data = $validator->validate();

        DB::beginTransaction();

        try {
            $personalId = DB::table('personal')->insertGetId([
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'telefono' => $data['telefono'] ?? null,
                'email' => $data['email'] ?? null,
                'direccion' => $data['direccion'] ?? null,
                'position_id' => $data['position_id'],
                'status_id' => $data['status_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('users')->insert([
                'alias' => $data['alias'],
                'password' => Hash::make($data['password']),
                'personal_id' => $personalId,
                'role_id' => $data['role_id'],
                'status_id' => $data['status_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Usuario creado correctamente',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(Request $request, int $id)
    {
        $user = DB::table('users')->where('id', $id)->first();
        abort_if(!$user, 404, 'Usuario no encontrado');

        $validator = Validator::make($request->all(), [
            'alias' => ['required', 'string', 'max:80', Rule::unique('users', 'alias')->ignore($id)],
            'password' => ['nullable', 'string', 'min:6'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'status_id' => ['required', 'integer', 'exists:statuses,id'],
            'nombres' => ['required', 'string', 'max:150'],
            'apellidos' => ['required', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('personal', 'email')->ignore($user->personal_id)],
            'direccion' => ['nullable', 'string'],
            'position_id' => ['required', 'integer', 'exists:positions,id'],
        ]);

        $data = $validator->validate();

        DB::beginTransaction();

        try {
            DB::table('personal')
                ->where('id', $user->personal_id)
                ->update([
                    'nombres' => $data['nombres'],
                    'apellidos' => $data['apellidos'],
                    'telefono' => $data['telefono'] ?? null,
                    'email' => $data['email'] ?? null,
                    'direccion' => $data['direccion'] ?? null,
                    'position_id' => $data['position_id'],
                    'status_id' => $data['status_id'],
                    'updated_at' => now(),
                ]);

            $updateUser = [
                'alias' => $data['alias'],
                'role_id' => $data['role_id'],
                'status_id' => $data['status_id'],
                'updated_at' => now(),
            ];

            if (!empty($data['password'])) {
                $updateUser['password'] = Hash::make($data['password']);
            }

            DB::table('users')
                ->where('id', $id)
                ->update($updateUser);

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Usuario actualizado correctamente',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy(int $id)
    {
        $user = DB::table('users')->where('id', $id)->first();
        abort_if(!$user, 404, 'Usuario no encontrado');

        $inactiveId = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'GENERAL')
            ->where('s.clave', 'INACTIVE')
            ->value('s.id');

        DB::beginTransaction();

        try {
            DB::table('users')
                ->where('id', $id)
                ->update([
                    'status_id' => $inactiveId,
                    'updated_at' => now(),
                ]);

            DB::table('personal')
                ->where('id', $user->personal_id)
                ->update([
                    'status_id' => $inactiveId,
                    'fecha_baja' => now(),
                    'updated_at' => now(),
                ]);

            return response()->json([
                'ok' => true,
                'message' => 'Usuario desactivado correctamente',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}