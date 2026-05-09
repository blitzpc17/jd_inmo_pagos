<?php

namespace App\Http\Controllers\Catalogs;

use App\Models\Process;
use Illuminate\Validation\Rule;

class ProcessesController extends BaseCatalogController
{
    protected string $modelClass = Process::class;
    protected string $routeName = 'catalogs.processes';
    protected string $title = 'Procesos';
    protected array $fields = ['id', 'clave', 'nombre'];
    protected array $rules = [
        'clave' => 'required|string|max:60|unique:processes,clave',
        'nombre' => 'required|string|max:120|unique:processes,nombre',
    ];

    protected function rulesForUpdate($id): array
    {
        return [
            'clave' => ['required', 'string', 'max:60', Rule::unique('processes', 'clave')->ignore($id)],
            'nombre' => ['required', 'string', 'max:120', Rule::unique('processes', 'nombre')->ignore($id)],
        ];
    }
}