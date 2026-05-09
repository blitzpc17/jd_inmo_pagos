<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'alias',
        'password',
        'personal_id',
        'role_id',
        'status_id',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}