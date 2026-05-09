<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class FolioService
{
    public function generate(string $variableName): string
    {
        return DB::transaction(function () use ($variableName) {
            $row = DB::table('global_variables')
                ->where('nombre', $variableName)
                ->lockForUpdate()
                ->first();

            if (!$row) {
                throw new RuntimeException("No existe la variable global {$variableName}");
            }

            $config = json_decode($row->valor, true);

            if (!is_array($config)) {
                throw new RuntimeException("La variable {$variableName} no contiene un JSON válido");
            }

            $longitud = (int) ($config['longitud'] ?? 0);
            $prefijo = (string) ($config['prefijo'] ?? '');
            $consecutivo = (int) ($config['consecutivo'] ?? 1);
            $tipo = strtoupper((string) ($config['tipo'] ?? 'NUMERICO'));

            if ($longitud <= 0) {
                throw new RuntimeException("La longitud del folio {$variableName} debe ser mayor a cero");
            }

            $folioCore = match ($tipo) {
                'NUMERICO' => $this->buildNumeric($consecutivo, $longitud),
                'MIXTO' => $this->buildMixed($consecutivo, $longitud),
                default => throw new RuntimeException("Tipo de folio no soportado: {$tipo}"),
            };

            $folio = $prefijo . $folioCore;

            $config['consecutivo'] = $consecutivo + 1;

            DB::table('global_variables')
                ->where('id', $row->id)
                ->update([
                    'valor' => json_encode($config),
                    'updated_at' => now(),
                ]);

            return $folio;
        });
    }

    protected function buildNumeric(int $number, int $length): string
    {
        return str_pad((string) $number, $length, '0', STR_PAD_LEFT);
    }

    protected function buildMixed(int $number, int $length): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $base = strlen($alphabet);

        $n = max(0, $number - 1);
        $result = '';

        do {
            $result = $alphabet[$n % $base] . $result;
            $n = intdiv($n, $base);
        } while ($n > 0);

        return str_pad($result, $length, '0', STR_PAD_LEFT);
    }
}