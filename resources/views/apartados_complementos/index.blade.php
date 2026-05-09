@extends('layouts.app')

@section('content')
<div class="page-card mb-3">
    <div class="d-flex justify-content-between align-items-center gap-2">
        <div>
            <h3 class="fw-bold mb-1">Complementos de apartados</h3>
            <div class="text-muted">Pagos adicionales vinculados a apartados vigentes</div>
        </div>

        <button class="btn btn-primary" id="btnNuevoComplemento">
            <i class="fa-solid fa-plus me-1"></i> Nuevo complemento
        </button>
    </div>
</div>

<div class="page-card">
    <div class="table-responsive">
        <table class="table table-bordered align-middle w-100" id="tblComplementos">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Apartado</th>
                    <th>Cobro</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Lotificación</th>
                    <th>Tipo</th>
                    <th>Forma pago</th>
                    <th>Monto</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="modalComplemento" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="formComplemento">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo complemento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Apartado</label>
                            <select class="form-select select2-comp" id="reservation_id" name="reservation_id"></select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Tipo cobro</label>
                            <select class="form-select select2-comp" id="charge_type_id" name="charge_type_id"></select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Forma pago</label>
                            <select class="form-select select2-comp" id="payment_method_id" name="payment_method_id"></select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Monto</label>
                            <input type="number" step="0.01" class="form-control" id="monto" name="monto">
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
@endsection

@push('scripts')
<script>
(() => {
    const modal = new bootstrap.Modal(document.getElementById('modalComplemento'));
    const form = document.getElementById('formComplemento');
    let table = null;
    let optionsCache = null;

    function initSelect2() {
        $('.select2-comp').select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $('#modalComplemento')
        });
    }

    async function loadOptions() {
        if (optionsCache) return optionsCache;

        const res = await fetch('/apartados-complementos/options');
        optionsCache = await res.json();

        fillSelect('reservation_id', optionsCache.reservations);
        fillSelect('charge_type_id', optionsCache.charge_types);
        fillSelect('payment_method_id', optionsCache.payment_methods);

        return optionsCache;
    }

    function fillSelect(id, items) {
        const el = document.getElementById(id);
        el.innerHTML = '<option value="">Seleccione...</option>';
        items.forEach(item => {
            el.innerHTML += `<option value="${item.value}">${item.text}</option>`;
        });
        $(el).trigger('change');
    }

    function resetForm() {
        form.reset();
        $('.select2-comp').val(null).trigger('change');
    }

    function initTable() {
        table = $('#tblComplementos').DataTable({
            ajax: { url: '/apartados-complementos/datatable', dataSrc: 'data' },
            columns: [
                { data: null, render: (_, __, ___, meta) => meta.row + 1 },
                { data: 'apartado_referencia' },
                { data: 'cobro_referencia' },
                { data: 'fecha_emision' },
                { data: 'cliente' },
                { data: 'lotificacion' },
                { data: 'tipo_cobro' },
                { data: 'forma_pago' },
                { data: 'monto' }
            ],
            pageLength: 10,
            order: [],
            language: { url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' }
        });
    }

    async function openNew() {
        await loadOptions();
        resetForm();
        modal.show();
    }

    async function saveItem(e) {
        e.preventDefault();

        const formData = new FormData(form);

        try {
            const res = await fetch('/apartados-complementos', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
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
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: err.message
            });
        }
    }

    document.getElementById('btnNuevoComplemento').addEventListener('click', openNew);
    form.addEventListener('submit', saveItem);

    initSelect2();
    initTable();
})();
</script>
@endpush