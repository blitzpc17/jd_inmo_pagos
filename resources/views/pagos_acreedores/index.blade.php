@extends('layouts.app')

@section('content')
<div class="page-card mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold mb-1">Boletas acreedor</h3>
            <div class="text-muted">Alta de deuda por acreedor</div>
        </div>
        <button class="btn btn-primary" id="btnNuevaBoletaAcreedor">
            <i class="fa-solid fa-plus me-1"></i> Nueva boleta
        </button>
    </div>
</div>

<div class="page-card">
    <div class="table-responsive">
        <table class="table table-bordered align-middle w-100" id="tblPagosAcreedores">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Referencia</th>
                    <th>Acreedor</th>
                    <th>Total</th>
                    <th>Meses</th>
                    <th>Mensualidad</th>
                    <th>Pagado</th>
                    <th>Debe</th>
                    <th>Estado pago</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="modalBoletaAcreedor" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formBoletaAcreedor">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva boleta de acreedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Acreedor</label>
                            <select class="form-select select2-acreedor" id="creditor_id" name="creditor_id"></select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Total</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="total" name="total">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Meses</label>
                            <input type="number" min="1" class="form-control" id="meses" name="meses">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Observación</label>
                            <textarea class="form-control" id="observacion" name="observacion" rows="3"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cerrar</button>
                    <button class="btn btn-primary" type="submit">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetalleBoletaAcreedor" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle boleta acreedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-3"><label class="form-label">Referencia</label><input type="text" class="form-control" id="dba_ref" readonly></div>
                    <div class="col-md-3"><label class="form-label">Acreedor</label><input type="text" class="form-control" id="dba_acreedor" readonly></div>
                    <div class="col-md-2"><label class="form-label">Total</label><input type="text" class="form-control" id="dba_total" readonly></div>
                    <div class="col-md-2"><label class="form-label">Meses</label><input type="text" class="form-control" id="dba_meses" readonly></div>
                    <div class="col-md-2"><label class="form-label">Mensualidad</label><input type="text" class="form-control" id="dba_mensualidad" readonly></div>

                    <div class="col-md-3"><label class="form-label">Pagado</label><input type="text" class="form-control" id="dba_pagado" readonly></div>
                    <div class="col-md-3"><label class="form-label">Debe</label><input type="text" class="form-control" id="dba_debe" readonly></div>
                    <div class="col-md-3"><label class="form-label">Debería llevar</label><input type="text" class="form-control" id="dba_deberia" readonly></div>
                    <div class="col-md-3"><label class="form-label">Estado pago</label><input type="text" class="form-control" id="dba_estado_pago" readonly></div>

                    <div class="col-md-12"><label class="form-label">Observación</label><textarea class="form-control" id="dba_observacion" rows="2" readonly></textarea></div>
                </div>

                <div class="page-card">
                    <h6 class="fw-bold mb-3">Abonos registrados</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Fecha recibido</th>
                                    <th>Forma de pago</th>
                                    <th>Cantidad</th>
                                    <th>Usuario registro</th>
                                </tr>
                            </thead>
                            <tbody id="detalleBoletaAcreedorItemsBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const modal = new bootstrap.Modal(document.getElementById('modalBoletaAcreedor'));
    const modalDetalle = new bootstrap.Modal(document.getElementById('modalDetalleBoletaAcreedor'));
    const form = document.getElementById('formBoletaAcreedor');
    let table = null;
    let optionsCache = null;

    function initSelect2() {
        $('.select2-acreedor').select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $('#modalBoletaAcreedor')
        });
    }

    function fillSelect(id, items) {
        const el = document.getElementById(id);
        el.innerHTML = '<option value="">Seleccione...</option>';
        items.forEach(item => {
            el.innerHTML += `<option value="${item.value}">${item.text}</option>`;
        });
        $(el).trigger('change');
    }

    async function loadOptions() {
        if (optionsCache) return optionsCache;
        const res = await fetch('/pagos-acreedores/options');
        optionsCache = await res.json();
        fillSelect('creditor_id', optionsCache.creditors || []);
        return optionsCache;
    }

    function resetForm() {
        form.reset();
        $('.select2-acreedor').val(null).trigger('change');
    }

    function initTable() {
        table = $('#tblPagosAcreedores').DataTable({
            ajax: { url: '/pagos-acreedores/datatable', dataSrc: 'data' },
            columns: [
                { data: null, render: (_, __, ___, meta) => meta.row + 1 },
                { data: 'numero_referencia' },
                { data: 'acreedor' },
                { data: 'total' },
                { data: 'meses' },
                { data: 'mensualidad' },
                { data: 'total_pagado' },
                { data: 'saldo_pendiente' },
                { data: 'estado_pago_badge', orderable: false, searchable: false },
                { data: 'acciones', orderable: false, searchable: false }
            ],
            pageLength: 10,
            order: [],
            language: {
                processing: "Procesando...",
                lengthMenu: "Mostrar _MENU_ registros",
                zeroRecords: "No se encontraron resultados",
                emptyTable: "No hay datos disponibles",
                info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                infoEmpty: "Mostrando 0 a 0 de 0 registros",
                infoFiltered: "(filtrado de _MAX_ registros totales)",
                search: "Buscar:",
                loadingRecords: "Cargando...",
                paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" }
            }
        });
    }

    async function openNew() {
        await loadOptions();
        resetForm();
        modal.show();
    }

    async function saveItem(e) {
        e.preventDefault();

        const payload = {
            creditor_id: document.getElementById('creditor_id').value,
            total: document.getElementById('total').value,
            meses: document.getElementById('meses').value,
            observacion: document.getElementById('observacion').value
        };

        try {
            const res = await fetch('/pagos-acreedores', {
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

            modal.hide();
            table.ajax.reload(null, false);

            Swal.fire({
                icon: 'success',
                title: 'Correcto',
                text: json.message,
                timer: 1600,
                showConfirmButton: false
            });
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        }
    }

    async function viewItem(id) {
        const res = await fetch(`/pagos-acreedores/${id}`);
        const json = await res.json();

        document.getElementById('dba_ref').value = json.data.numero_referencia || '';
        document.getElementById('dba_acreedor').value = json.data.acreedor || '';
        document.getElementById('dba_total').value = json.data.total || '';
        document.getElementById('dba_meses').value = json.data.meses || '';
        document.getElementById('dba_mensualidad').value = json.data.mensualidad || '';
        document.getElementById('dba_pagado').value = json.data.total_pagado || '';
        document.getElementById('dba_debe').value = json.data.saldo_pendiente || '';
        document.getElementById('dba_deberia').value = json.data.deberia_llevar || '';
        document.getElementById('dba_estado_pago').value = json.data.estado_pago || '';
        document.getElementById('dba_observacion').value = json.data.observacion || '';

        const tbody = document.getElementById('detalleBoletaAcreedorItemsBody');
        tbody.innerHTML = '';

        (json.data.items || []).forEach((item, index) => {
            tbody.innerHTML += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${item.fecha_recibido ?? ''}</td>
                    <td>${item.forma_pago ?? ''}</td>
                    <td>${item.cantidad ?? ''}</td>
                    <td>${item.usuario_registro ?? ''}</td>
                </tr>
            `;
        });

        modalDetalle.show();
    }

    document.getElementById('btnNuevaBoletaAcreedor').addEventListener('click', openNew);
    form.addEventListener('submit', saveItem);

    $('#tblPagosAcreedores').on('click', '.btn-view', function () {
        viewItem(this.dataset.id);
    });

    initSelect2();
    initTable();
})();
</script>
@endpush