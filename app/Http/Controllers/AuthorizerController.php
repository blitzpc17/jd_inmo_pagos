<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AuthorizerController extends Controller
{
    public function index()
    {
        $userId = session('auth_user.id');
        $isAuthorizer = DB::table('authorizer_users')->where('user_id', $userId)->exists();

        return view('authorizers.index', [
            'isAuthorizer' => $isAuthorizer
        ]);
    }

    public function datatable()
    {
        $rows = DB::table('authorizer_users as au')
            ->join('users as u', 'u.id', '=', 'au.user_id')
            ->join('personal as p', 'p.id', '=', 'u.personal_id')
            ->select([
                'au.id',
                'u.alias',
                DB::raw("TRIM(COALESCE(p.nombres,'') || ' ' || COALESCE(p.apellidos,'')) as nombre_completo"),
                'au.created_at'
            ])
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'integer', 'exists:users,id', 'unique:authorizer_users,user_id']
        ], [
            'unique' => 'Este usuario ya está registrado como autorizante.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        DB::table('authorizer_users')->insert([
            'user_id' => $request->user_id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Usuario autorizante agregado correctamente.'
        ]);
    }

    public function destroy(int $id)
    {
        $exists = DB::table('authorizer_users')->where('id', $id)->exists();
        if (!$exists) {
            return response()->json([
                'ok' => false,
                'message' => 'Registro no encontrado.'
            ], 404);
        }

        DB::table('authorizer_users')->where('id', $id)->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Usuario autorizante eliminado correctamente.'
        ]);
    }
}
