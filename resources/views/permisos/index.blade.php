@extends('layouts.app')

@section('content')
<div class="row g-3">
    <div class="col-12">
        <div class="page-card">
            <h3 class="fw-bold mb-1">Permisos</h3>
            <div class="text-muted">Asignación por rol y permisos extra por usuario</div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="page-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-1">Permisos por rol</h5>
                    <div class="text-muted small">Base para todos los usuarios de ese rol</div>
                </div>
                <button class="btn btn-primary btn-sm" id="btnSaveRoleTree">Guardar</button>
            </div>

            <div class="mb-3">
                <label class="form-label">Rol</label>
                <select class="form-select" id="roleSelect"></select>
            </div>

            <div id="roleTree" class="border rounded p-2" style="min-height:420px;"></div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="page-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-1">Permisos extra por usuario</h5>
                    <div class="text-muted small">
                        Los módulos heredados por rol se muestran bloqueados. Aquí solo agregas o quitas permisos extra.
                    </div>
                </div>
                <button class="btn btn-primary btn-sm" id="btnSaveUserTree">Guardar</button>
            </div>

            <div class="mb-3">
                <label class="form-label">Usuario</label>
                <select class="form-select" id="userSelect"></select>
            </div>

            <div id="userTree" class="border rounded p-2" style="min-height:420px;"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const roleSelect = document.getElementById('roleSelect');
    const userSelect = document.getElementById('userSelect');

    async function loadRoles() {
        const res = await fetch('/permisos/roles/select');
        const items = await res.json();

        roleSelect.innerHTML = '<option value="">Seleccione...</option>';
        items.forEach(item => {
            roleSelect.innerHTML += `<option value="${item.value}">${item.text}</option>`;
        });
    }

    async function loadUsers() {
        const res = await fetch('/permisos/usuarios/select');
        const items = await res.json();

        userSelect.innerHTML = '<option value="">Seleccione...</option>';
        items.forEach(item => {
            userSelect.innerHTML += `<option value="${item.value}">${item.text}</option>`;
        });
    }

    async function buildTree(selector, url) {
        const res = await fetch(url);
        const data = await res.json();

        $(selector).jstree('destroy');
        $(selector).jstree({
            core: {
                data: data,
                check_callback: true,
                themes: {
                    responsive: true
                }
            },
            plugins: ['checkbox', 'wholerow', 'dnd', 'types'],
            checkbox: {
                keep_selected_style: false,
                three_state: true
            }
        });
    }

    roleSelect.addEventListener('change', async function () {
        if (!this.value) {
            $('#roleTree').jstree('destroy').empty();
            return;
        }
        await buildTree('#roleTree', `/permisos/roles/${this.value}/tree`);
    });

    userSelect.addEventListener('change', async function () {
        if (!this.value) {
            $('#userTree').jstree('destroy').empty();
            return;
        }
        await buildTree('#userTree', `/permisos/usuarios/${this.value}/tree`);
    });

    document.getElementById('btnSaveRoleTree').addEventListener('click', async function () {
        const roleId = roleSelect.value;
        if (!roleId) {
            Swal.fire({ icon: 'warning', title: 'Selecciona un rol' });
            return;
        }

        const selected = $('#roleTree').jstree(true).get_checked();

        const res = await fetch(`/permisos/roles/${roleId}/save`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ menu_ids: selected })
        });

        const json = await res.json();

        Swal.fire({
            icon: res.ok ? 'success' : 'error',
            title: res.ok ? 'Correcto' : 'Error',
            text: json.message || 'No se pudo guardar'
        });
    });

    document.getElementById('btnSaveUserTree').addEventListener('click', async function () {
        const userId = userSelect.value;
        if (!userId) {
            Swal.fire({ icon: 'warning', title: 'Selecciona un usuario' });
            return;
        }

        const selected = $('#userTree').jstree(true).get_checked();

        const res = await fetch(`/permisos/usuarios/${userId}/save`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ menu_ids: selected })
        });

        const json = await res.json();

        Swal.fire({
            icon: res.ok ? 'success' : 'error',
            title: res.ok ? 'Correcto' : 'Error',
            text: json.message || 'No se pudo guardar'
        });
    });

    loadRoles();
    loadUsers();
})();
</script>
@endpush