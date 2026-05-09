<?php

namespace App\Http\Controllers\Catalogs;

use App\Models\Position;
use App\Models\Status;
use Illuminate\Validation\Rule;

class PositionsController extends BaseCatalogController
{
    protected string $modelClass = Position::class;
    protected string $routeName = 'catalogs.positions';
    protected string $title = 'Puestos';
    protected array $fields = ['id', 'nombre', 'status_id'];
    protected array $with = ['status'];
    protected array $rules = [
        'nombre' => 'required|string|max:120|unique:positions,nombre',
        'status_id' => 'required|exists:statuses,id',
    ];

    protected function rulesForUpdate($id): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120', Rule::unique('positions', 'nombre')->ignore($id)],
            'status_id' => ['required', 'exists:statuses,id'],
        ];
    }

    protected function formData(): array
    {
        return [
            'statuses' => Status::orderBy('nombre')->get(['id', 'nombre']),
        ];
    }

    protected function mapRow($row): array
    {
        return [
            'id' => $row->id,
            'nombre' => $row->nombre,
            'status' => $row->status?->nombre,
        ];
    }
}