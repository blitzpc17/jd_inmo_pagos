@extends('layouts.app')

@section('content')
<div class="page-card mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h3 class="fw-bold mb-1">Usuarios Autorizantes</h3>
            <div class="text-muted">
                Registra y administra a los usuarios con permisos para autorizar solicitudes de modificación en masa.
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="page-card">
            <h5 class="fw-bold mb-3">Registrar Autorizante</h5>
            <form id="formAddAuthorizer">
                <div class="mb-3">
                    <label class="form-label">Seleccionar Usuario</label>
                    <select id="user_id_authorizer" class="form-select" required></select>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa-solid fa-user-plus me-1"></i> Registrar
                </button>
            </form>
        </div>
    </div>
    <div class="col-md-8">
        <div class="page-card">
            <h5 class="fw-bold mb-3">Lista de Usuarios Autorizantes</h5>
            <div class="table-responsive">
                <table class="table table-bordered align-middle w-100" id="tableAuthorizers">
                    <thead>
                        <tr>
                            <th>Usuario (Alias)</th>
                            <th>Nombre Completo</th>
                            <th>F. Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    let tableAuthorizers = null;
    let catalogsOptions = {};

    function initDataTables() {
        tableAuthorizers = $('#tableAuthorizers').DataTable({
            processing: true,
            ajax: '{{ route('authorizers.datatable') }}',
            columns: [
                {data: 'alias'},
                {data: 'nombre_completo'},
                {data: 'created_at', render: val => val ? val.substring(0, 19).replace('T', ' ') : ''},
                {
                    data: 'id',
                    orderable: false,
                    render: val => `
                        <button class="btn btn-sm btn-outline-danger btn-delete-authorizer" data-id="${val}">
                            <i class="fa-solid fa-trash"></i> Eliminar
                        </button>
                    `
                }
            ]
        });
    }

    async function loadOptions() {
        // Load available users from the options route in bulk modifications
        const res = await fetch('{{ route('bulk-modifications.options') }}');
        catalogsOptions = await res.json();

        const selectAuth = $('#user_id_authorizer');
        selectAuth.html('<option value="">Selecciona usuario...</option>');
        (catalogsOptions.available_users || []).forEach(u => {
            selectAuth.append(`<option value="${u.value}">${u.text}</option>`);
        });
        selectAuth.select2({ width: '100%' });
    }

    async function submitAddAuthorizer(e) {
        e.preventDefault();
        const userId = $('#user_id_authorizer').val();

        try {
            const res = await fetch('{{ route('authorizers.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ user_id: userId })
            });

            const json = await res.json();

            if (!res.ok || !json.ok) {
                throw new Error(json.message || 'Error al agregar autorizante.');
            }

            Swal.fire('Éxito', json.message, 'success');
            $('#user_id_authorizer').val(null).trigger('change');
            tableAuthorizers.ajax.reload();
            await loadOptions(); // reload available users
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        }
    }

    async function deleteAuthorizer(id) {
        Swal.fire({
            title: '¿Eliminar autorizante?',
            text: 'Este usuario ya no podrá autorizar solicitudes de modificación masiva.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then(async result => {
            if (result.isConfirmed) {
                try {
                    const res = await fetch(`/autorizantes/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    const json = await res.json();

                    if (!res.ok || !json.ok) {
                        throw new Error(json.message || 'Error al eliminar autorizante.');
                    }

                    Swal.fire('Éxito', json.message, 'success');
                    tableAuthorizers.ajax.reload();
                    await loadOptions(); // reload available users
                } catch (error) {
                    Swal.fire('Error', error.message, 'error');
                }
            }
        });
    }

    // Run setups
    (async () => {
        initDataTables();
        await loadOptions();
        $('#formAddAuthorizer').on('submit', submitAddAuthorizer);
        $(document).on('click', '.btn-delete-authorizer', function () {
            deleteAuthorizer($(this).data('id'));
        });
    })();
})();
</script>
@endpush
