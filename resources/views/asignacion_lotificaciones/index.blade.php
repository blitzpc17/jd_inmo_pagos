@extends('layouts.app')

@section('content')
<div class="page-card mb-3">
    <h3 class="fw-bold mb-1">Asignación de lotificaciones</h3>
    <div class="text-muted">Define visibilidad por rol y extras por usuario</div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="page-card h-100">
            <h5 class="fw-bold mb-3">Asignación por rol</h5>

            <div class="mb-3">
                <label class="form-label">Rol</label>
                <select class="form-select select2-assign" id="role_id"></select>
            </div>

            <div class="d-flex gap-2 mb-3">
                <button class="btn btn-outline-success btn-sm" id="btnRoleAll" type="button">Asignar todas</button>
                <button class="btn btn-outline-danger btn-sm" id="btnRoleNone" type="button">Eliminar todas</button>
            </div>

            <div class="mb-3">
                <label class="form-label">Lotificaciones</label>
                <select class="form-select" id="role_development_ids" multiple size="18"></select>
            </div>

            <button class="btn btn-primary" id="btnSaveRole" type="button">Guardar rol</button>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="page-card h-100">
            <h5 class="fw-bold mb-3">Asignación por usuario</h5>

            <div class="mb-3">
                <label class="form-label">Usuario</label>
                <select class="form-select select2-assign" id="user_id"></select>
            </div>

            <div class="alert alert-light border small">
                Las lotificaciones ya asignadas por rol se muestran abajo como referencia. Aquí solo asignas extras por usuario.
            </div>

            <div class="mb-2">
                <label class="form-label">Ya tiene por rol</label>
                <select class="form-select" id="role_assigned_ids" multiple size="6" disabled></select>
            </div>

            <div class="d-flex gap-2 mb-3">
                <button class="btn btn-outline-success btn-sm" id="btnUserAll" type="button">Asignar todas</button>
                <button class="btn btn-outline-danger btn-sm" id="btnUserNone" type="button">Eliminar todas</button>
            </div>

            <div class="mb-3">
                <label class="form-label">Extras por usuario</label>
                <select class="form-select" id="user_development_ids" multiple size="10"></select>
            </div>

            <button class="btn btn-primary" id="btnSaveUser" type="button">Guardar usuario</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    let cache = null;

    function initSelect2() {
        $('.select2-assign').select2({
            theme: 'bootstrap4',
            width: '100%'
        });
    }

    function fillSelect(id, items, withPlaceholder = true) {
        const el = document.getElementById(id);
        if (!el) return;

        if (el.multiple) {
            el.innerHTML = '';
        } else {
            el.innerHTML = withPlaceholder ? '<option value="">Seleccione...</option>' : '';
        }

        (items || []).forEach(item => {
            el.innerHTML += `<option value="${item.value}">${item.text}</option>`;
        });

        if (!el.multiple) {
            $(el).trigger('change.select2');
        }
    }

    async function loadOptions() {
        if (cache) return cache;

        const res = await fetch('/asignacion-lotificaciones/options');
        const json = await res.json();
        cache = json;

        fillSelect('role_id', json.roles || []);
        fillSelect('user_id', json.users || []);
        fillSelect('role_development_ids', json.developments || [], false);
        fillSelect('role_assigned_ids', json.developments || [], false);
        fillSelect('user_development_ids', json.developments || [], false);

        return cache;
    }

    function clearSelections() {
        $('#role_development_ids option').prop('selected', false);
        $('#role_assigned_ids option').prop('selected', false);
        $('#user_development_ids option').prop('selected', false);
    }

    async function loadRoleAssignments(roleId) {
        $('#role_development_ids option').prop('selected', false);
        if (!roleId) return;

        const res = await fetch(`/asignacion-lotificaciones/role/${roleId}`);
        const json = await res.json();

        (json.assigned || []).forEach(id => {
            $(`#role_development_ids option[value="${id}"]`).prop('selected', true);
        });
    }

    async function loadUserAssignments(userId) {
        $('#user_development_ids option').prop('selected', false);
        $('#role_assigned_ids option').prop('selected', false);

        if (!userId) return;

        const res = await fetch(`/asignacion-lotificaciones/user/${userId}`);
        const json = await res.json();

        (json.role_assigned || []).forEach(id => {
            $(`#role_assigned_ids option[value="${id}"]`).prop('selected', true);
        });

        (json.assigned || []).forEach(id => {
            $(`#user_development_ids option[value="${id}"]`).prop('selected', true);
        });
    }

    async function saveRole() {
        const roleId = $('#role_id').val();
        if (!roleId) {
            return Swal.fire({ icon: 'warning', title: 'Selecciona un rol' });
        }

        const ids = ($('#role_development_ids').val() || []);
        const payload = { development_ids: ids.map(x => parseInt(x, 10)) };

        try {
            const res = await fetch(`/asignacion-lotificaciones/role/${roleId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const json = await res.json();
            if (!res.ok) throw new Error(json.message || 'No se pudo guardar');

            Swal.fire({
                icon: 'success',
                title: 'Correcto',
                text: json.message,
                timer: 1500,
                showConfirmButton: false
            });
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: err.message
            });
        }
    }

    async function saveUser() {
        const userId = $('#user_id').val();
        if (!userId) {
            return Swal.fire({ icon: 'warning', title: 'Selecciona un usuario' });
        }

        const ids = ($('#user_development_ids').val() || []);
        const payload = { development_ids: ids.map(x => parseInt(x, 10)) };

        try {
            const res = await fetch(`/asignacion-lotificaciones/user/${userId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const json = await res.json();
            if (!res.ok) throw new Error(json.message || 'No se pudo guardar');

            Swal.fire({
                icon: 'success',
                title: 'Correcto',
                text: json.message,
                timer: 1500,
                showConfirmButton: false
            });
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: err.message
            });
        }
    }

    document.getElementById('role_id').addEventListener('change', function () {
        loadRoleAssignments(this.value);
    });

    document.getElementById('user_id').addEventListener('change', function () {
        loadUserAssignments(this.value);
    });

    document.getElementById('btnRoleAll').addEventListener('click', function () {
        $('#role_development_ids option').prop('selected', true);
    });

    document.getElementById('btnRoleNone').addEventListener('click', function () {
        $('#role_development_ids option').prop('selected', false);
    });

    document.getElementById('btnUserAll').addEventListener('click', function () {
        $('#user_development_ids option').prop('selected', true);
    });

    document.getElementById('btnUserNone').addEventListener('click', function () {
        $('#user_development_ids option').prop('selected', false);
    });

    document.getElementById('btnSaveRole').addEventListener('click', saveRole);
    document.getElementById('btnSaveUser').addEventListener('click', saveUser);

    loadOptions().then(() => {
        initSelect2();
        clearSelections();
    });
})();
</script>
@endpush