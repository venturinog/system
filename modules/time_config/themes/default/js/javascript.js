$(document).ready(function() {
    // El formato tiene que llevar el año de cuatro digitos: es el que
    // espera translateDate() para convertir la fecha a yyyy-mm-dd.
    $("#datepicker").datepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: 'dd M yyyy'
    });
});
