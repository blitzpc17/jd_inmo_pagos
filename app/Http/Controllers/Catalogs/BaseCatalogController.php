namespace App\Http\Controllers\Catalogs;
        $validator = Validator::make($request->all(), $this->rulesForUpdate($id));

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => 'Error de validación.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $modelClass = $this->modelClass;
        $record = $modelClass::findOrFail($id);
        $record->update($request->only($this->fillableFields()));

        return response()->json([
            'ok' => true,
            'message' => 'Registro actualizado correctamente.',
            'data' => $record,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $modelClass = $this->modelClass;
        $record = $modelClass::findOrFail($id);

        if ($this->usesLogicalDelete($record)) {
            $record->update(['fecha_baja' => now()]);
        } else {
            $record->delete();
        }

        return response()->json([
            'ok' => true,
            'message' => 'Registro dado de baja correctamente.',
        ]);
    }

    protected function fillableFields(): array
    {
        return array_keys($this->rules);
    }

    protected function formData(): array
    {
        return [];
    }

    protected function rulesForUpdate(int $id): array
    {
        return $this->rules;
    }

    protected function transformRow(Model $row): array
    {
        return $row->toArray();
    }

    protected function usesLogicalDelete(Model $record): bool
    {
        return in_array('fecha_baja', $record->getFillable(), true)
            || array_key_exists('fecha_baja', $record->getAttributes());
    }
}