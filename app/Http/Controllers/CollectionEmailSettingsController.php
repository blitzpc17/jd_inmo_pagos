<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CollectionEmailSettingsController extends Controller
{
    public function index()
    {
        $settings = $this->settings();

        $users = DB::table('users')
            ->select([
                'id',
                'alias',
                'email',
            ])
            ->orderBy('alias')
            ->get()
            ->map(function ($u) {
                $u->text = trim(($u->alias ?: 'USUARIO ' . $u->id) . ' - ' . ($u->email ?: 'SIN CORREO'));
                $u->has_email = !empty($u->email);
                return $u;
            });

        return view('collection_email_settings.index', [
            'settings' => $settings,
            'users' => $users,
        ]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'enabled' => ['nullable'],
            'notify_on_contract_finalized' => ['nullable'],
            'notify_before_finalization' => ['nullable'],
            'days_before_finalization' => ['required', 'integer', 'min:1', 'max:90'],
            'recipient_user_ids' => ['nullable', 'array'],
            'recipient_user_ids.*' => ['integer', 'exists:users,id'],
            'fallback_user_id' => ['required', 'integer', 'exists:users,id'],
            'subject_finalized' => ['required', 'string', 'max:180'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => 'Revisa la información capturada.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $recipientUserIds = $request->input('recipient_user_ids', []);

        if (!is_array($recipientUserIds)) {
            $recipientUserIds = [];
        }

        $recipientUserIds = collect($recipientUserIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $fallbackUserId = (int) $request->input('fallback_user_id');

        $usersWithoutEmail = DB::table('users')
            ->whereIn('id', array_merge($recipientUserIds, [$fallbackUserId]))
            ->where(function ($q) {
                $q->whereNull('email')
                    ->orWhere('email', '');
            })
            ->get(['id', 'alias']);

        if ($usersWithoutEmail->isNotEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'Hay usuarios seleccionados sin correo. Deben tener correo para recibir notificaciones.',
                'users_without_email' => $usersWithoutEmail,
            ], 422);
        }

        $payload = [
            'enabled' => $request->boolean('enabled'),
            'notify_on_contract_finalized' => $request->boolean('notify_on_contract_finalized'),
            'notify_before_finalization' => $request->boolean('notify_before_finalization'),
            'days_before_finalization' => (int) $request->input('days_before_finalization'),
            'recipient_user_ids' => $recipientUserIds,
            'fallback_user_id' => $fallbackUserId,
            'subject_finalized' => trim((string) $request->input('subject_finalized')),
        ];

        DB::table('global_variables')->updateOrInsert(
            ['nombre' => 'COLLECTION_EMAIL_SETTINGS'],
            [
                'valor' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
                'created_at' => DB::raw('COALESCE(created_at, NOW())'),
            ]
        );

        return response()->json([
            'ok' => true,
            'message' => 'Configuración de correos de cobranza guardada correctamente.',
            'settings' => $payload,
        ]);
    }

    protected function settings(): array
    {
        $default = [
            'enabled' => true,
            'notify_on_contract_finalized' => true,
            'notify_before_finalization' => false,
            'days_before_finalization' => 5,
            'recipient_user_ids' => [],
            'fallback_user_id' => 1,
            'subject_finalized' => 'Contrato finalizado por atraso',
        ];

        $value = DB::table('global_variables')
            ->where('nombre', 'COLLECTION_EMAIL_SETTINGS')
            ->value('valor');

        if (!$value) {
            return $default;
        }

        $json = is_array($value) ? $value : json_decode($value, true);

        if (!is_array($json)) {
            return $default;
        }

        return array_merge($default, $json);
    }
}