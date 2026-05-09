<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasLogicalDelete
{
    public function scopeActivos(Builder $query): Builder
    {
        return $query->whereNull('fecha_baja');
    }

    public function scopeNoBaja(Builder $query): Builder
    {
        return $query->whereNull('fecha_baja');
    }

    public function darDeBaja(?int $usuarioBajaId = null): bool
    {
        if (property_exists($this, 'fillable')) {
            if (in_array('usuario_baja_id', $this->fillable)) {
                $this->usuario_baja_id = $usuarioBajaId;
            }
        }

        if (array_key_exists('fecha_baja', $this->getAttributes()) || $this->hasCast('fecha_baja')) {
            $this->fecha_baja = now();
        }

        return $this->save();
    }
}