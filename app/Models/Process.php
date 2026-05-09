<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Process extends Model
{
    use HasFactory;

    protected $table = 'processes';

    protected $fillable = [
        'clave',
        'nombre',
    ];

    public function statuses()
    {
        return $this->hasMany(Status::class, 'process_id');
    }
}