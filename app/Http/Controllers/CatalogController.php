<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Session;

class CatalogController extends Controller
{
    protected function getCatalogConfig(string $catalog): array
    {
        $config = config("catalogs.$catalog");

        abort_if(!$config, 404, 'Catálogo no encontrado');

        return $config;
    }

    public function index(string $catalog)
    {
        $config = $this->getCatalogConfig($catalog);

        return view('catalogs.index', [
            'catalogKey' => $catalog,
            'catalog' => $config,
        ]);
    }

    public function datatable(Request $request, string $catalog)
    {
        $config = $this->getCatalogConfig($catalog);

        $table = $config['table'];
        $primary = $config['primary'] ?? 'id';
        $softField = $config['soft_field'] ?? null;

        $query = DB::table($table);

        if (!empty($config['joins'])) {
            foreach ($config['joins'] as $join) {
                $query->leftJoin("{$join['table']} as {$join['alias']}", $join['first'], $join['operator'], $join['second']);
            }
        }

        if (!empty($config['selects'])) {
            $query->select($config['selects']);
        } else {
            $query->select("$table.*");
        }

        if ($softField) {
            $query->whereNull("$table.$softField");
        }

        if (!empty($config['default_order'])) {
            $query->orderBy($config['default_order'][0], $config['default_order'][1]);
        }

        $rows = $query->get();

        return response()->json([
            'data' => $rows->map(function ($row) use ($primary) {
                $row->acciones = '
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-primary btn-edit" data-id="'.$row->$primary.'">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger btn-delete" data-id="'.$row->$primary.'">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                ';
                return $row;
            }),
        ]);
    }

    public function show(string $catalog, int $id)
    {
        $config = $this->getCatalogConfig($catalog);

        $row = DB::table($config['table'])->where($config['primary'], $id)->first();

        abort_if(!$row, 404, 'Registro no encontrado');

        return response()->json([
            'ok' => true,
            'data' => $row,
        ]);
    }

    public function store(Request $request, string $catalog)
    {
        $config = $this->getCatalogConfig($catalog);

        $data = $this->validateData($request, $config);

        if (array_key_exists('es_menu', $config['fields'])) {
            $data['es_menu'] = $request->boolean('es_menu');
        }

        $data['created_at'] = now();
        $data['updated_at'] = now();

        DB::table($config['table'])->insert($data);

        return response()->json([
            'ok' => true,
            'message' => 'Registro creado correctamente',
        ]);
    }

    public function update(Request $request, string $catalog, int $id)
    {
        $config = $this->getCatalogConfig($catalog);

        $exists = DB::table($config['table'])->where($config['primary'], $id)->exists();
        abort_if(!$exists, 404, 'Registro no encontrado');

        $data = $this->validateData($request, $config, $id);

        if (array_key_exists('es_menu', $config['fields'])) {
            $data['es_menu'] = $request->boolean('es_menu');
        }

        $data['updated_at'] = now();

        DB::table($config['table'])
            ->where($config['primary'], $id)
            ->update($data);

        return response()->json([
            'ok' => true,
            'message' => 'Registro actualizado correctamente',
        ]);
    }

    public function destroy(string $catalog, int $id)
    {
        $config = $this->getCatalogConfig($catalog);

        $softField = $config['soft_field'] ?? null;

        $query = DB::table($config['table'])->where($config['primary'], $id);
        abort_if(!$query->exists(), 404, 'Registro no encontrado');

        if ($softField) {
            $query->update([
                $softField => now(),
                'updated_at' => now(),
            ]);
        } else {
            $query->delete();
        }

        return response()->json([
            'ok' => true,
            'message' => 'Registro eliminado correctamente',
        ]);
    }

    public function selectOptions(Request $request, string $catalog)
    {
        $config = $this->getCatalogConfig($catalog);

        $field = $request->get('field');
        abort_if(!$field || empty($config['fields'][$field]), 404, 'Campo no encontrado');

        $fieldConfig = $config['fields'][$field];

        if (($fieldConfig['type'] ?? null) === 'general_status') {
            $data = DB::table('statuses as s')
                ->join('processes as p', 'p.id', '=', 's.process_id')
                ->where('p.clave', 'GENERAL')
                ->orderBy('s.nombre')
                ->get([
                    's.id as value',
                    's.nombre as text',
                ]);

            return response()->json($data);
        }

        if (!isset($fieldConfig['source'])) {
            return response()->json([]);
        }

        $source = $fieldConfig['source'];

        $query = DB::table($source['table'])
            ->orderBy($source['order_by'] ?? $source['text']);

        if ($source['table'] === 'menus') {
            $query->whereNull('parent_id');
        }

        if ($source['table'] === 'offices' || $source['table'] === 'partners') {
            $query->whereNull('fecha_baja');
        }

        $data = $query->get([
            $source['value'].' as value',
            $source['text'].' as text',
        ]);

        return response()->json($data);
    }

    protected function validateData(Request $request, array $config, ?int $id = null): array
    {
        $rules = [];
        $table = $config['table'];
        $primary = $config['primary'] ?? 'id';

        foreach ($config['fields'] as $field => $meta) {
            $type = $meta['type'] ?? 'text';
            $required = $meta['required'] ?? false;

            $fieldRules = $required ? ['required'] : ['nullable'];

            switch ($type) {
                case 'text':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:255';
                    break;

                case 'number':
                    $fieldRules[] = 'numeric';
                    break;

                case 'select':
                    $fieldRules[] = 'integer';
                    break;

                case 'select_nullable':
                    $fieldRules[] = 'nullable';
                    $fieldRules[] = 'integer';
                    break;

                case 'general_status':
                    $fieldRules[] = 'integer';
                    break;

                case 'checkbox':
                    $fieldRules[] = 'boolean';
                    break;
            }

            if (in_array($field, ['clave', 'nombre', 'alias'], true)) {
                if ($field === 'clave' && in_array($table, ['statuses'], true)) {
                    // en statuses la unicidad ideal es por process_id + clave
                } else {
                    $fieldRules[] = Rule::unique($table, $field)->ignore($id, $primary);
                }
            }

            $rules[$field] = $fieldRules;
        }

        if ($table === 'statuses') {
            $rules['clave'][] = Rule::unique('statuses', 'clave')
                ->where(function ($q) use ($request) {
                    return $q->where('process_id', $request->process_id);
                })
                ->ignore($id, 'id');

            $rules['nombre'][] = Rule::unique('statuses', 'nombre')
                ->where(function ($q) use ($request) {
                    return $q->where('process_id', $request->process_id);
                })
                ->ignore($id, 'id');
        }

        if ($table === 'payment_methods') {
            $rules['nombre'][] = Rule::unique('payment_methods', 'nombre')
                ->where(function ($q) use ($request) {
                    return $q->where('office_id', $request->office_id);
                })
                ->ignore($id, 'id');
        }

        $validator = Validator::make($request->all(), $rules, [
            'required' => 'El campo :attribute es obligatorio.',
            'unique' => 'El valor del campo :attribute ya existe.',
        ]);

        return $validator->validate();
    }
}