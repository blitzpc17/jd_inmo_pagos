<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDelete;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    use HasFactory, HasLogicalDelete;

    protected $table = 'positions';

    protected $fillable = [
        'nombre',
        'status_id',
    ];

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}