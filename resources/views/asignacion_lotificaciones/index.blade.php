@extends('layouts.app')

@section('content')
<style>
    .assignment-list {
        max-height: 430px;
        overflow-y: auto;
        border: 1px solid #dee2e6;
        border-radius: .85rem;
        background: #fff;
    }

    .assignment-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .65rem .85rem;
        border-bottom: 1px solid #eef1f4;
        cursor: pointer;
    }

    .assignment-item:last-child {
        border-bottom: none;
    }

    .assignment-item:hover {
        background: rgba(13, 110, 253, .045);
    }

    .assignment-item.assignment-disabled {
        cursor: not-allowed;
        background: #f8f9fa;
        opacity: .85;
    }

    .assignment-title {
        font-weight: 600;
        color: #1f2937;
    }

    .assignment-badge {
        font-size: .72rem;
        border-radius: 999px;
        padding: .22rem .55rem;
        white-space: nowrap;
    }

    .assignment-badge-role {
        background: #e0f2fe;
        color: #075985;
    }

    .assignment-badge-extra {
        background: #eef2ff;
        color: #3730a3;
    }

    .assignment-badge-free {
        background: #f3f4f6;
        color: #4b5563;
    }

    .assignment-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .4rem;
    }

    .assignment-empty {
        padding: 1rem;
        text-align: center;
        color: #6b7280;
    }

    html.dark .assignment-list,
    body.dark .assignment-list,
    [data-bs-theme="dark"] .assignment-list {
        background: #111827;
        border-color: #374151;
    }

    html.dark .assignment-item,
    body.dark .assignment-item,
    [data-bs-theme="dark"] .assignment-item {
        border-color: #1f2937;
    }

    html.dark .assignment-item.assignment-disabled,
    body.dark .assignment-item.assignment-disabled,
    [data-bs-theme="dark"] .assignment-item.assignment-disabled {
        background: #1f2937;
    }

    html.dark .assignment-title,
    body.dark .assignment-title,
    [data-bs-theme="dark"] .assignment-title {
        color: #f9fafb;
    }
</style>

<div class="page-card mb-3">
    <h3 class="fw-bold mb-1">Asignación de lotificaciones</h3>
    <div class="text-muted">Define visibilidad por rol y lotificaciones extras por usuario</div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="page-card h-100">
            <h5 class="fw-bold mb-3">Asignación por rol</h5>

            <div class="mb-3">
                <label class="form-label">Rol</label>
                <select class="form-select select2-assign" id="role_id"></select>
            </div>

            <div class="alert alert-light border small">
                Al seleccionar un rol, se cargarán las lotificaciones y se mostrarán marcadas las que ya tiene asignadas.
            </div>

            <div class="assignment-actions mb-3">
                <button class="btn btn-outline-success btn-sm" id="btnRoleAll" type="button">
                    <i class="fa-solid fa-check-double me-1"></i> Asignar todas
                </button>

                <button class="btn btn-outline-danger btn-sm" id="btnRoleNone" type="button">
                    <i class="fa-solid fa-xmark me-1"></i> Quitar todas
                </button>
            </div>

            <div class="assignment-list" id="roleDevelopmentList">
                <div class="assignment-empty">Selecciona un rol para cargar lotificaciones.</div>
            </div>

            <div class="mt-3 d-flex justify-content-between align-items-center gap-2">
                <div class="small text-muted" id="roleCounter">0 seleccionadas</div>

                <button class="btn btn-primary" id="btnSaveRole" type="button">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Guardar rol
                </button>
            </div>
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
                Al seleccionar un usuario, se cargarán las lotificaciones.
                Las heredadas por rol aparecerán marcadas y deshabilitadas.
            </div>

            <div class="assignment-actions mb-3">
                <button class="btn btn-outline-success btn-sm" id="btnUserAll" type="button">
                    <i class="fa-solid fa-check-double me-1"></i> Asignar todas extras
                </button>

                <button class="btn btn-outline-danger btn-sm" id="btnUserNone" type="button">
                    <i class="fa-solid fa-xmark me-1"></i> Quitar extras
                </button>
            </div>

            <div class="assignment-list" id="userDevelopmentList">
                <div class="assignment-empty">Selecciona un usuario para cargar lotificaciones.</div>
            </div>

            <div class="mt-3 d-flex justify-content-between align-items-center gap-2">
                <div class="small text-muted" id="userCounter">0 extras seleccionadas</div>

                <button class="btn btn-primary" id="btnSaveUser" type="button">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Guardar usuario
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    let cache = null;
    let developmentsCache = null;

    let roleAssigned = [];
    let userAssigned = [];
    let userRoleAssigned = [];

    function initSelect2() {
        $('.select2-assign').select2({
            theme: 'bootstrap4',
            width: '100%'
        });
    }

    function fillSelect(id, items, withPlaceholder = true) {
        const el = document.getElementById(id);
        if (!el) return;

        el.innerHTML = withPlaceholder ? '<option value="">Seleccione...</option>' : '';

        (items || []).forEach(item => {
            el.innerHTML += `<option value="${item.value}">${item.text}</option>`;
        });

        $(el).trigger('change.select2');
    }

    async function loadOptions() {
        if (cache) return cache;

        const res = await fetch('/asignacion-lotificaciones/options', {
            headers: {
                'Accept': 'application/json'
            }
        });

        const json = await res.json();
        cache = json;

        fillSelect('role_id', json.roles || []);
        fillSelect('user_id', json.users || []);

        renderRoleEmpty();
        renderUserEmpty();

        return cache;
    }

    async function loadDevelopments() {
        if (developmentsCache) return developmentsCache;

        const res = await fetch('/asignacion-lotificaciones/developments', {
            headers: {
                'Accept': 'application/json'
            }
        });

        const json = await res.json();

        developmentsCache = json.developments || [];

        return developmentsCache;
    }

    function normalizeIds(ids) {
        return (ids || [])
            .map(id => parseInt(id, 10))
            .filter(id => !Number.isNaN(id));
    }

    function isChecked(collection, id) {
        return normalizeIds(collection).includes(parseInt(id, 10));
    }

    function developmentRows() {
        return developmentsCache || [];
    }

    function renderRoleEmpty() {
        roleAssigned = [];
        $('#roleDevelopmentList').html('<div class="assignment-empty">Selecciona un rol para cargar lotificaciones.</div>');
        $('#roleCounter').text('0 seleccionadas');
    }

    function renderUserEmpty() {
        userAssigned = [];
        userRoleAssigned = [];
        $('#userDevelopmentList').html('<div class="assignment-empty">Selecciona un usuario para cargar lotificaciones.</div>');
        $('#userCounter').text('0 extras seleccionadas');
    }

    function renderRoleList() {
        const box = document.getElementById('roleDevelopmentList');
        const developments = developmentRows();

        if (!$('#role_id').val()) {
            renderRoleEmpty();
            return;
        }

        if (!developments.length) {
            box.innerHTML = '<div class="assignment-empty">No hay lotificaciones activas.</div>';
            updateCounters();
            return;
        }

        box.innerHTML = developments.map(dev => {
            const checked = isChecked(roleAssigned, dev.value) ? 'checked' : '';
            const badge = checked
                ? '<span class="assignment-badge assignment-badge-role">Asignada</span>'
                : '<span class="assignment-badge assignment-badge-free">Pendiente</span>';

            return `
                <div class="assignment-item">
                    <label class="mb-0 flex-grow-1" for="role_dev_${dev.value}" style="cursor:pointer;">
                        <div class="assignment-title">${dev.text}</div>
                        <div class="small text-muted">ID ${dev.value}</div>
                    </label>

                    <div class="d-flex align-items-center gap-2">
                        ${badge}
                        <input class="form-check-input chk-role-development"
                               type="checkbox"
                               id="role_dev_${dev.value}"
                               value="${dev.value}"
                               ${checked}>
                    </div>
                </div>
            `;
        }).join('');

        updateCounters();
    }

    function renderUserList() {
        const box = document.getElementById('userDevelopmentList');
        const developments = developmentRows();

        if (!$('#user_id').val()) {
            renderUserEmpty();
            return;
        }

        if (!developments.length) {
            box.innerHTML = '<div class="assignment-empty">No hay lotificaciones activas.</div>';
            updateCounters();
            return;
        }

        box.innerHTML = developments.map(dev => {
            const devId = parseInt(dev.value, 10);

            const inheritedByRole = isChecked(userRoleAssigned, devId);
            const assignedByUser = isChecked(userAssigned, devId);

            const checked = (inheritedByRole || assignedByUser) ? 'checked' : '';
            const disabled = inheritedByRole ? 'disabled' : '';

            const badge = inheritedByRole
                ? '<span class="assignment-badge assignment-badge-role">Por rol</span>'
                : (assignedByUser
                    ? '<span class="assignment-badge assignment-badge-extra">Extra usuario</span>'
                    : '<span class="assignment-badge assignment-badge-free">Disponible</span>'
                );

            const disabledClass = inheritedByRole ? 'assignment-disabled' : '';

            return `
                <div class="assignment-item ${disabledClass}">
                    <label class="mb-0 flex-grow-1" for="user_dev_${dev.value}" style="cursor:${inheritedByRole ? 'not-allowed' : 'pointer'};">
                        <div class="assignment-title">${dev.text}</div>
                        <div class="small text-muted">ID ${dev.value}</div>
                    </label>

                    <div class="d-flex align-items-center gap-2">
                        ${badge}
                        <input class="form-check-input chk-user-development"
                               type="checkbox"
                               id="user_dev_${dev.value}"
                               value="${dev.value}"
                               ${checked}
                               ${disabled}>
                    </div>
                </div>
            `;
        }).join('');

        updateCounters();
    }

    function updateCounters() {
        const roleCount = $('.chk-role-development:checked').length;

        const userInheritedCount = $('.chk-user-development:disabled:checked').length;
        const userExtraCount = $('.chk-user-development:checked:not(:disabled)').length;
        const userAvailableCount = $('.chk-user-development:not(:disabled)').length;

        $('#roleCounter').text(`${roleCount} seleccionada${roleCount === 1 ? '' : 's'}`);

        if ($('#user_id').val()) {
            $('#userCounter').text(
                `${userInheritedCount} por rol / ${userExtraCount} extra${userExtraCount === 1 ? '' : 's'} / ${userAvailableCount} disponible${userAvailableCount === 1 ? '' : 's'}`
            );
        }
    }

    async function loadRoleAssignments(roleId) {
        roleAssigned = [];

        if (!roleId) {
            renderRoleEmpty();
            return;
        }

        await loadDevelopments();

        const res = await fetch(`/asignacion-lotificaciones/role/${roleId}`, {
            headers: {
                'Accept': 'application/json'
            }
        });

        const json = await res.json();

        roleAssigned = normalizeIds(json.assigned || []);

        renderRoleList();
    }

    async function loadUserAssignments(userId) {
        userAssigned = [];
        userRoleAssigned = [];

        if (!userId) {
            renderUserEmpty();
            return;
        }

        await loadDevelopments();

        const res = await fetch(`/asignacion-lotificaciones/user/${userId}`, {
            headers: {
                'Accept': 'application/json'
            }
        });

        const json = await res.json();

        userAssigned = normalizeIds(json.assigned || []);
        userRoleAssigned = normalizeIds(json.role_assigned || []);

        renderUserList();
    }

    function getCheckedRoleIds() {
        return $('.chk-role-development:checked')
            .map((_, el) => parseInt(el.value, 10))
            .get();
    }

    function getCheckedUserIds() {
        return $('.chk-user-development:checked:not(:disabled)')
            .map((_, el) => parseInt(el.value, 10))
            .get();
    }

    async function saveRole() {
        const roleId = $('#role_id').val();

        if (!roleId) {
            return Swal.fire({
                icon: 'warning',
                title: 'Selecciona un rol'
            });
        }

        const payload = {
            development_ids: getCheckedRoleIds()
        };

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

            if (!res.ok) {
                throw new Error(json.message || 'No se pudo guardar');
            }

            roleAssigned = payload.development_ids;
            renderRoleList();

            const selectedUserId = $('#user_id').val();
            if (selectedUserId) {
                await loadUserAssignments(selectedUserId);
            }

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
            return Swal.fire({
                icon: 'warning',
                title: 'Selecciona un usuario'
            });
        }

        const payload = {
            development_ids: getCheckedUserIds()
        };

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

            if (!res.ok) {
                throw new Error(json.message || 'No se pudo guardar');
            }

            userAssigned = payload.development_ids;
            renderUserList();

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

    $('#role_id').on('change', function () {
        loadRoleAssignments(this.value);
    });

    $('#user_id').on('change', function () {
        loadUserAssignments(this.value);
    });

    $('#roleDevelopmentList').on('change', '.chk-role-development', function () {
        roleAssigned = getCheckedRoleIds();
        renderRoleList();
    });

    $('#userDevelopmentList').on('change', '.chk-user-development', function () {
        userAssigned = getCheckedUserIds();
        renderUserList();
    });

    $('#btnRoleAll').on('click', async function () {
        if (!$('#role_id').val()) {
            return Swal.fire({
                icon: 'warning',
                title: 'Selecciona un rol'
            });
        }

        await loadDevelopments();

        $('.chk-role-development').prop('checked', true);

        roleAssigned = getCheckedRoleIds();

        renderRoleList();
    });

    $('#btnRoleNone').on('click', function () {
        if (!$('#role_id').val()) {
            return Swal.fire({
                icon: 'warning',
                title: 'Selecciona un rol'
            });
        }

        $('.chk-role-development').prop('checked', false);

        roleAssigned = [];

        renderRoleList();
    });

    $('#btnUserAll').on('click', async function () {
        if (!$('#user_id').val()) {
            return Swal.fire({
                icon: 'warning',
                title: 'Selecciona un usuario'
            });
        }

        await loadDevelopments();

        $('.chk-user-development:not(:disabled)').prop('checked', true);

        userAssigned = getCheckedUserIds();

        renderUserList();
    });

    $('#btnUserNone').on('click', function () {
        if (!$('#user_id').val()) {
            return Swal.fire({
                icon: 'warning',
                title: 'Selecciona un usuario'
            });
        }

        $('.chk-user-development:not(:disabled)').prop('checked', false);

        userAssigned = [];

        renderUserList();
    });

    $('#btnSaveRole').on('click', saveRole);
    $('#btnSaveUser').on('click', saveUser);

    loadOptions().then(() => initSelect2());
})();
</script>
@endpush