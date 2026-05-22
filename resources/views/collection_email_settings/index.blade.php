@extends('layouts.app')

@section('content')
<div class="page-card mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h3 class="fw-bold mb-1">Configuración de correos de cobranza</h3>
            <div class="text-muted">
                Define qué usuarios recibirán avisos cuando un contrato sea finalizado por atraso.
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0" style="border-radius:18px;">
    <div class="card-body">
        <form id="formCollectionEmailSettings">
            @csrf

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="enabled" name="enabled"
                            {{ !empty($settings['enabled']) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="enabled">
                            Activar envío de correos
                        </label>
                    </div>
                    <div class="text-muted small">
                        Si está desactivado, el mantenimiento no enviará correos.
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="notify_on_contract_finalized" name="notify_on_contract_finalized"
                            {{ !empty($settings['notify_on_contract_finalized']) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="notify_on_contract_finalized">
                            Notificar contratos finalizados
                        </label>
                    </div>
                    <div class="text-muted small">
                        Envía correo cuando se finaliza un contrato por atraso.
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="notify_before_finalization" name="notify_before_finalization"
                            {{ !empty($settings['notify_before_finalization']) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="notify_before_finalization">
                            Aviso preventivo
                        </label>
                    </div>
                    <div class="text-muted small">
                        Reservado para avisar antes de finalizar.
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Días antes del aviso preventivo</label>
                    <input type="number" min="1" max="90" class="form-control" name="days_before_finalization"
                        value="{{ $settings['days_before_finalization'] ?? 5 }}">
                </div>

                <div class="col-md-8">
                    <label class="form-label">Asunto del correo de finalización</label>
                    <input type="text" class="form-control" name="subject_finalized"
                        value="{{ $settings['subject_finalized'] ?? 'Contrato finalizado por atraso' }}">
                </div>

                <div class="col-md-8">
                    <label class="form-label">Usuarios destinatarios</label>
                    <select class="form-select" id="recipient_user_ids" name="recipient_user_ids[]" multiple>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}"
                                {{ in_array($user->id, $settings['recipient_user_ids'] ?? []) ? 'selected' : '' }}
                                {{ !$user->has_email ? 'disabled' : '' }}>
                                {{ $user->text }}
                            </option>
                        @endforeach
                    </select>
                    <div class="text-muted small mt-1">
                        Los usuarios sin correo aparecen deshabilitados.
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Usuario fallback</label>
                    <select class="form-select" id="fallback_user_id" name="fallback_user_id">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}"
                                {{ (int)($settings['fallback_user_id'] ?? 1) === (int)$user->id ? 'selected' : '' }}
                                {{ !$user->has_email ? 'disabled' : '' }}>
                                {{ $user->text }}
                            </option>
                        @endforeach
                    </select>
                    <div class="text-muted small mt-1">
                        Se usará si no hay destinatarios configurados.
                    </div>
                </div>

                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary" type="submit" id="btnSaveEmailSettings">
                        <i class="fa-solid fa-floppy-disk me-1"></i>
                        Guardar configuración
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('#recipient_user_ids, #fallback_user_id').select2({
        width: '100%',
        placeholder: 'Seleccione...'
    });

    $('#formCollectionEmailSettings').on('submit', async function (e) {
        e.preventDefault();

        const form = this;
        const formData = new FormData(form);

        try {
            const res = await fetch('{{ route('collection-email-settings.update') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            });

            const json = await res.json();

            if (!res.ok || !json.ok) {
                throw new Error(json.message || 'No se pudo guardar la configuración.');
            }

            Swal.fire({
                icon: 'success',
                title: 'Guardado',
                text: json.message
            });
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message
            });
        }
    });
});
</script>
@endpush