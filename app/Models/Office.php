<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDelete;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Office extends Model
{
    use HasFactory, HasLogicalDelete;

    protected $table = 'offices';

    protected $fillable = [
        'nombre',
        'color',
        'status_id',
    ];

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}