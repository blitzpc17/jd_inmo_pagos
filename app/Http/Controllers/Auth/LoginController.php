<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $request->validate([
            'alias' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = DB::table('users as u')
            ->join('roles as r', 'r.id', '=', 'u.role_id')
            ->join('personal as p', 'p.id', '=', 'u.personal_id')
            ->join('statuses as s', 's.id', '=', 'u.status_id')
            ->join('processes as pr', 'pr.id', '=', 's.process_id')
            ->where('u.alias', $request->alias)
            ->where('pr.clave', 'GENERAL')
            ->where('s.clave', 'ACTIVE')
            ->select([
                'u.*',
                'r.nombre as role_name',
                'p.nombres',
                'p.apellidos',
            ])
            ->first();

        if (!$user) {
            return back()->withErrors(['alias' => 'Usuario no encontrado o inactivo'])->withInput();
        }

        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Contraseña incorrecta'])->withInput();
        }

        Session::put('auth_user', [
            'id' => $user->id,
            'alias' => $user->alias,
            'role_id' => $user->role_id,
            'role_name' => $user->role_name,
            'personal_id' => $user->personal_id,
            'nombre' => trim($user->nombres . ' ' . $user->apellidos),
        ]);

        return redirect()->route('dashboard');
    }

    public function destroy(Request $request)
    {
        Session::forget('auth_user');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}