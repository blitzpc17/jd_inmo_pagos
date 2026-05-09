$(function () {
    $('#btnNew').on('click', function () {
        recordId.val('');
        $('#catalogModalTitle').text(`Nuevo registro - ${cfg.title}`);
        buildForm();
        form[0].reset();
        modal.show();
    });

    $(document).on('click', '.btnEdit', function () {
        const id = $(this).data('id');
        buildForm();
        $.get(endpoint(`${id}/edit`), function (resp) {
            recordId.val(resp.data.id);
            Object.keys(resp.data).forEach(key => {
                $(`#${key}`).val(resp.data[key]).trigger('change');
            });
            $('#catalogModalTitle').text(`Editar registro - ${cfg.title}`);
            modal.show();
        });
    });

    form.on('submit', function (e) {
        e.preventDefault();

        $('.is-invalid').removeClass('is-invalid');
        $('[id^="error_"]').text('');

        const id = recordId.val();
        const method = id ? 'PUT' : 'POST';
        const url = id ? endpoint(id) : endpoint();
        const payload = {};

        cfg.fields.forEach(field => {
            if (field !== 'id') {
                payload[field] = $(`#${field}`).val();
            }
        });

        $.ajax({
            url,
            method,
            data: payload,
            success: function (resp) {
                modal.hide();
                table.ajax.reload(null, false);
                alert(resp.message);
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors || {};
                    Object.keys(errors).forEach(field => {
                        $(`#${field}`).addClass('is-invalid');
                        $(`#error_${field}`).text(errors[field][0]);
                    });
                }
            }
        });
    });

    $(document).on('click', '.btnDelete', function () {
        const id = $(this).data('id');
        if (!confirm('¿Deseas dar de baja este registro?')) return;

        $.ajax({
            url: endpoint(id),
            method: 'DELETE',
            success: function (resp) {
                table.ajax.reload(null, false);
                alert(resp.message);
            }
        });
    });
});