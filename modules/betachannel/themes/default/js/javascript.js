function prepararBoton(s)
{
    $("#" + s).on('change', function () {
        var $input = $(this);
        var arrAction = {
            menu:        'betachannel',
            rawmode:     'yes',
            action:      'update_' + s
        };
        arrAction["new_" + s] = ($input.is(":checked") ? 1 : 0)
        if (arrAction["new_" + s] != $("#old" + s).val()) {
            $.post('index.php?menu=betachannel&rawmode=yes', arrAction,
            function (resultado) {
                var arrData = resultado['message'];
                if (arrData['result']) {
                    // Recordar nuevo valor
                    $("#old" + s).val(($input.is(":checked") ? 1 : 0));
                } else {
                    // Restaurar valor anterior
                    if ($("#old" + s).val() == 1) {
                        $("#" + s).prop("checked", true);
                    } else {
                        $("#" + s).prop("checked", false);
                    }
                }

                // Mostrar mensajes con la familia de avisos del tema.
                var contenedor = $("#iss-betachannel-alert");
                if (contenedor.length) {
                    contenedor.html(
                        '<div class="iss-alert iss-alert--' + (arrData['result'] ? 'info' : 'error') + '" id="message_error">' +
                        '<div class="iss-alert__title">' + arrData['message_title'] + '</div>' +
                        '<div class="iss-alert__content">' + arrData['message'] + '</div>' +
                        '<div class="iss-alert__dismiss"><i class="ti ti-x" onclick="hide_message_error();"></i></div>' +
                        '</div>');
                }
            });
        }
    });
}

$(document).ready(function () {
    prepararBoton("beta_channel_status");
});
