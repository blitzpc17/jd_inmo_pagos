@extends('layouts.app')

@section('content')
<div class="page-card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="fw-bold mb-1">Reasignación de Oficinas</h3>
            <div class="text-muted">Reasigna masivamente las oficinas de las boletas.</div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form id="filterForm" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Tipo de Boleta</label>
                    <select class="form-select" id="filter_tipo" name="tipo">
                        <option value="cobranza">Cobranza (Cobros)</option>
                        <option value="proveedores">Boletas de Proveedores</option>
                        <option value="acreedores">Boletas de Acreedores</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Oficina Actual (Opcional)</label>
                    <select class="form-select select2-offices" id="filter_office_id" name="office_id">
                        <option value="">Todas las oficinas</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Buscar Entidad / Folio</label>
                    <input type="text" class="form-control" id="filter_search" placeholder="Escriba cliente, proveedor o folio..." autocomplete="off">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-search me-1"></i> Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Panel de Acciones Masivas -->
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3 bg-light p-3 rounded border d-none" id="bulkActionsPanel">
        <span class="fw-bold text-primary">Con los <span id="selectedCount">0</span> elementos seleccionados:</span>
        <div style="width: 250px;">
            <select class="form-select select2-offices" id="target_office_id">
                <option value="">Seleccione nueva oficina...</option>
            </select>
        </div>
        <button class="btn btn-success" id="btnReasignar">
            <i class="fa-solid fa-exchange-alt me-1"></i> Reasignar Oficina
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle w-100" id="tblBoletas">
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;">
                        <input class="form-check-input" type="checkbox" id="selectAll">
                    </th>
                    <th>Folio</th>
                    <th>Entidad (Cliente/Proveedor/Acreedor)</th>
                    <th>Oficina Actual</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    let table = null;
    let optionsCache = null;

    function initSelect2() {
        $('.select2-offices').select2({
            theme: 'bootstrap4',
            width: '100%',
            allowClear: true
        });
    }

    async function loadOptions() {
        try {
            const res = await fetch('/modificaciones-masivas/reasignar-oficinas/opciones');
            const json = await res.json();
            
            const filterSelect = document.getElementById('filter_office_id');
            const targetSelect = document.getElementById('target_office_id');

            let html = '<option value="">Seleccione...</option>';
            json.offices.forEach(item => {
                html += `<option value="${item.value}">${item.text}</option>`;
            });

            filterSelect.innerHTML = '<option value="">Todas las oficinas</option><option value="null">-- SIN OFICINA ASIGNADA --</option>' + html.replace('<option value="">Seleccione...</option>', '');
            targetSelect.innerHTML = html;

        } catch (err) {
            console.error('Error al cargar opciones', err);
        }
    }

    function updateBulkPanel() {
        const count = $('.row-checkbox:checked').length;
        document.getElementById('selectedCount').textContent = count;
        if (count > 0) {
            document.getElementById('bulkActionsPanel').classList.remove('d-none');
        } else {
            document.getElementById('bulkActionsPanel').classList.add('d-none');
        }
    }

    function initTable() {
        table = $('#tblBoletas').DataTable({
            ajax: {
                url: '/modificaciones-masivas/reasignar-oficinas/datatable',
                data: function(d) {
                    d.tipo = $('#filter_tipo').val();
                    d.office_id = $('#filter_office_id').val();
                    d.search_query = $('#filter_search').val();
                },
                dataSrc: 'data'
            },
            columns: [
                { 
                    data: null, 
                    orderable: false,
                    searchable: false,
                    render: function(data) {
                        return `<div class="text-center"><input class="form-check-input row-checkbox" type="checkbox" value="${data.id}"></div>`;
                    }
                },
                { data: 'folio' },
                { data: 'entidad' },
                { data: 'oficina_actual' },
                { data: 'total', render: $.fn.dataTable.render.number(',', '.', 2, '$') },
                { 
                    data: 'estado',
                    render: function(data) {
                        return `<span class="badge bg-secondary">${data}</span>`;
                    }
                },
                { 
                    data: null, 
                    orderable: false, 
                    searchable: false,
                    render: function(data) {
                        return `<button class="btn btn-sm btn-outline-primary btn-reassign-single" data-id="${data.id}">
                            <i class="fa-solid fa-edit"></i> Cambiar
                        </button>`;
                    }
                }
            ],
            pageLength: 25,
            order: [[1, 'desc']],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json'
            },
            drawCallback: function() {
                // Reset select all checkbox
                $('#selectAll').prop('checked', false);
                updateBulkPanel();
            }
        });

        // Select All behavior
        $('#selectAll').on('change', function() {
            $('.row-checkbox').prop('checked', this.checked);
            updateBulkPanel();
        });

        $('#tblBoletas tbody').on('change', '.row-checkbox', function() {
            if (!this.checked) {
                $('#selectAll').prop('checked', false);
            } else {
                if ($('.row-checkbox:checked').length === $('.row-checkbox').length) {
                    $('#selectAll').prop('checked', true);
                }
            }
            updateBulkPanel();
        });
    }

    document.getElementById('filterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    document.getElementById('btnReasignar').addEventListener('click', async function() {
        const targetOfficeId = document.getElementById('target_office_id').value;
        const tipo = document.getElementById('filter_tipo').value;
        
        if (!targetOfficeId) {
            Swal.fire('Atención', 'Selecciona la nueva oficina de destino.', 'warning');
            return;
        }

        const ids = [];
        $('.row-checkbox:checked').each(function() {
            ids.push($(this).val());
        });

        if (ids.length === 0) return;

        const result = await Swal.fire({
            title: '¿Confirmar Reasignación?',
            text: `Se moverán ${ids.length} boletas a la nueva oficina.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, reasignar'
        });

        if (!result.isConfirmed) return;

        try {
            const res = await fetch('/modificaciones-masivas/reasignar-oficinas/procesar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    tipo: tipo,
                    office_id: targetOfficeId,
                    ids: ids
                })
            });

            const json = await res.json();
            if (!res.ok) throw new Error(json.message || 'Error al procesar.');

            Swal.fire('¡Éxito!', json.message, 'success');
            
            // Clean selection
            $('#target_office_id').val('').trigger('change');
            table.ajax.reload();

        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        }
    });

    $('#tblBoletas').on('click', '.btn-reassign-single', async function() {
        const id = $(this).data('id');
        const tipo = document.getElementById('filter_tipo').value;
        
        // Use SweetAlert to select new office
        const optionsHtml = document.getElementById('target_office_id').innerHTML;
        
        const { value: officeId } = await Swal.fire({
            title: 'Cambiar Oficina',
            input: 'select',
            inputOptions: getOfficesObject(),
            inputPlaceholder: 'Seleccione nueva oficina',
            showCancelButton: true,
            inputValidator: (value) => {
                if (!value) {
                    return 'Debe seleccionar una oficina';
                }
            }
        });

        if (officeId) {
            try {
                const res = await fetch('/modificaciones-masivas/reasignar-oficinas/procesar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        tipo: tipo,
                        office_id: officeId,
                        ids: [id]
                    })
                });

                const json = await res.json();
                if (!res.ok) throw new Error(json.message || 'Error al procesar.');

                Swal.fire({ icon: 'success', title: 'Correcto', text: 'Se ha cambiado la oficina', timer: 1500, showConfirmButton: false });
                table.ajax.reload(null, false);
            } catch (error) {
                Swal.fire('Error', error.message, 'error');
            }
        }
    });

    function getOfficesObject() {
        const obj = {};
        $('#target_office_id option').each(function() {
            if ($(this).val()) {
                obj[$(this).val()] = $(this).text();
            }
        });
        return obj;
    }

    // Init
    initSelect2();
    loadOptions().then(() => {
        initTable();
    });

})();
</script>
@endpush
