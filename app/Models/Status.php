<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;

    protected $table = 'statuses';

    protected $fillable = [
        'process_id',
        'clave',
        'nombre',
    ];

    public function process()
    {
        return $this->belongsTo(Process::class, 'process_id');
    }
}