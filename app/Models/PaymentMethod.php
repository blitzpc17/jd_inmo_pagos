<?php

namespace App\Models;

use App\Models\Concerns\HasLogicalDelete;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory, HasLogicalDelete;

    protected $table = 'payment_methods';

    protected $fillable = [
        'nombre',
        'office_id',
        'status_id',
    ];

    public function office()
    {
        return $this->belongsTo(Office::class, 'office_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}