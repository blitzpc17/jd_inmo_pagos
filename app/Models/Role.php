<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDelete;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory, HasLogicalDelete;

    protected $table = 'roles';

    protected $fillable = [
        'nombre',
        'status_id',
    ];

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}