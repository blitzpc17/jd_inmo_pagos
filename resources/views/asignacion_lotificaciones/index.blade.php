@extends('layouts.app')

@section('content')
<div class="page-card mb-3">
    <h3 class="fw-bold mb-1">Asignación de lotificaciones</h3>
    <div class="text-muted">Define visibilidad por rol y extras por usuario</div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="page-card h-100">
            <h5 class="fw-bold mb-3">Asignación por rol</h5>

            <div class="mb-3">
                <label class="form-label">Rol</label>
                <select class="form-select" id="role_id"></select>
            </div>

            <div class="d-flex gap-2 mb-3">
                <button class="btn btn-outline-success btn-sm" id="btnRoleAll">Asignar todas</button>
                <button class="btn btn-outline-danger btn-sm" id="btnRoleNone">Eliminar todas</button>
            </div>

            <div class="mb-3">
                <label class="form-label">Lotificaciones</label>
                <select class="form-select" id="role_development_ids" multiple size="16"></select>
            </div>

            <button class="btn btn-primary" id="btnSaveRole">Guardar rol</button>
        </div>
    </div>

    <div class="col-md-6">
        <div class="page-card h-100">
            <h5 class="fw-bold mb-3">Asignación por usuario</h5>

            <div class="mb-3">
                <label class="form-label">Usuario</label>
                <select class="form-select" id="user_id"></select>
            </div>

            <div class="alert alert-light border small">
                Las lotificaciones ya asignadas por rol se muestran abajo como referencia. Aquí solo asignas extras por usuario.
            </div>

            <div class="mb-2">
                <label class="form-label">Ya tiene por rol</label>
                <select class="form-select" id="role_assigned_ids" multiple size="6" disabled></select>
            </div>

            <div class="d-flex gap-2 mb-3">
                <button class="btn btn-outline-success btn-sm" id="btnUserAll">Asignar todas</button>
                <button class="btn btn-outline-danger btn-sm" id="btnUserNone">Eliminar todas</button>
            </div>

            <div class="mb-3">
                <label class="form-label">Extras por usuario</label>
                <select class="form-select" id="user_development_ids" multiple size="9"></select>
            </div>

            <button class="btn btn-primary" id="btnSaveUser">Guardar usuario</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    let cache = null;

    function fillSelect(id, items, placeholder = true) {
        const el = document.getElementById(id);
        if (!el) return;

        if (el.multiple) {
            el.innerHTML = '';
        } else {
            el.innerHTML = placeholder ? '<option value="">Seleccione...</option>' : '';
        }

        items.forEach(item => {
            el.innerHTML += `<option value="${item.value}">${item.text}</option>`;
        });
    }

    async function loadOptions() {
        if (cache) return cache;
        const res = await fetch('/asignacion-lotificaciones/options');
        cache = await res.json();

        fillSelect('role_id', cache.roles);
        fillSelect('user_id', cache.users);
        fillSelect('role_development_ids', cache.developments, false);
        fillSelect('user_development_ids', cache.developments, false);
        fillSelect('role_assigned_ids', cache.developments, false);

        return cache;
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

        (json.assigned || []).forEach(id => {
            $(`#user_development_ids option[value="${id}"]`).prop('selected', true);
        });

        (json.role_assigned || []).forEach(id => {
            $(`#role_assigned_ids option[value="${id}"]`).prop('selected', true);
        });
    }

    async function saveRole() {
        const roleId = $('#role_id').val();
        if (!roleId) {
            return Swal.fire({ icon: 'warning', title: 'Selecciona un rol' });
        }

        const ids = $('#role_development_ids').val() || [];
        const formData = new FormData();
        ids.forEach(v => formData.append('development_ids[]', v));

        const res = await fetch(`/asignacion-lotificaciones/role/${roleId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: formData
        });

        const json = await res.json();
        Swal.fire({
            icon: res.ok ? 'success' : 'error',
            title: res.ok ? 'Correcto' : 'Error',
            text: json.message || 'No se pudo guardar'
        });
    }

    async function saveUser() {
        const userId = $('#user_id').val();
        if (!userId) {
            return Swal.fire({ icon: 'warning', title: 'Selecciona un usuario' });
        }

        const ids = $('#user_development_ids').val() || [];
        const formData = new FormData();
        ids.forEach(v => formData.append('development_ids[]', v));

        const res = await fetch(`/asignacion-lotificaciones/user/${userId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: formData
        });

        const json = await res.json();
        Swal.fire({
            icon: res.ok ? 'success' : 'error',
            title: res.ok ? 'Correcto' : 'Error',
            text: json.message || 'No se pudo guardar'
        });
    }

    document.getElementById('role_id').addEventListener('change', function() {
        loadRoleAssignments(this.value);
    });

    document.getElementById('user_id').addEventListener('change', function() {
        loadUserAssignments(this.value);
    });

    document.getElementById('btnRoleAll').addEventListener('click', function() {
        $('#role_development_ids option').prop('selected', true);
    });

    document.getElementById('btnRoleNone').addEventListener('click', function() {
        $('#role_development_ids option').prop('selected', false);
    });

    document.getElementById('btnUserAll').addEventListener('click', function() {
        $('#user_development_ids option').prop('selected', true);
    });

    document.getElementById('btnUserNone').addEventListener('click', function() {
        $('#user_development_ids option').prop('selected', false);
    });

    document.getElementById('btnSaveRole').addEventListener('click', saveRole);
    document.getElementById('btnSaveUser').addEventListener('click', saveUser);

    loadOptions();
})();
</script>
@endpush