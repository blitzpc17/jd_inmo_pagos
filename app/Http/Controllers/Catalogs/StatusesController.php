<?php

namespace App\Http\Controllers\Catalogs;

use App\Models\Process;
use App\Models\Status;
use Illuminate\Validation\Rule;

class StatusesController extends BaseCatalogController
{
    protected string $modelClass = Status::class;
    protected string $routeName = 'catalogs.statuses';
    protected string $title = 'Estados';
    protected array $fields = ['id', 'process_id', 'clave', 'nombre'];
    protected array $with = ['process'];
    protected array $rules = [
        'process_id' => 'required|exists:processes,id',
        'clave' => 'required|string|max:60',
        'nombre' => 'required|string|max:120',
    ];

    protected function formData(): array
    {
        return [
            'processes' => Process::orderBy('nombre')->get(['id', 'nombre']),
        ];
    }

    protected function rulesForUpdate($id): array
    {
        return $this->rules;
    }

    protected function mapRow($row): array
    {
        return [
            'id' => $row->id,
            'process' => $row->process?->nombre,
            'clave' => $row->clave,
            'nombre' => $row->nombre,
        ];
    }
}