/**
 * Arrastrar archivos entre la lista local y la del servidor FTP.
 *
 * El JavaScript lo carga el framework en todas las paginas del modulo, asi
 * que lo primero es comprobar que la pagina es la del formulario de FTP.
 *
 * El servidor responde con el prefijo "OK|" o "ERR|" seguido del mensaje
 * cuando puede distinguir el resultado de la operacion; si la respuesta no
 * trae prefijo, se muestra como aviso informativo.
 *
 * Copyright (c) 2006 Stefan Petre (interfaz original, MIT/GPL)
 */
var module_name = "backup_restore";

/* Muestra el resultado de la operacion en el aviso de la pagina. El texto se
 * inserta como texto plano: el nombre del archivo lo pone el servidor. */
function ftp_mostrar_aviso(respuesta) {
    var caja = document.getElementById('ftp-message');
    if (!caja) return;

    var separador = respuesta.indexOf('|');
    var estado = separador > 0 ? respuesta.substring(0, separador) : 'INFO';
    var texto = separador > 0 ? respuesta.substring(separador + 1) : respuesta;

    var clase = 'iss-alert--info';
    if (estado === 'ERR') clase = 'iss-alert--error';
    else if (estado === 'OK') clase = 'iss-alert--success';

    while (caja.firstChild) caja.removeChild(caja.firstChild);

    var aviso = document.createElement('div');
    aviso.className = 'iss-alert ' + clase;
    var contenido = document.createElement('div');
    contenido.className = 'iss-alert__content';
    contenido.textContent = texto;
    aviso.appendChild(contenido);
    caja.appendChild(aviso);
}

$(document).ready((function() {

    if (!document.getElementById("sortable1")) return;

    $("ul.droptrue").sortable({
        connectWith: 'ul'
    });

    $("ul.droptrue2").sortable({
        connectWith: 'ul'
    });

    /* Un archivo soltado en la lista local se descarga del servidor FTP. */
    $("ul.droptrue").droppable({
        drop: function(event, ui) {
            var file = $(ui.draggable).attr("id");
            var order = '&action=downloadFTPServer&menu='+module_name+'&rawmode=yes&file='+encodeURIComponent(file)+'&lista=droptrue';
            $.post("index.php", order, function(theResponse){
                ftp_mostrar_aviso(theResponse);
            });
        }
    });

    /* Un archivo soltado en la lista del servidor se sube a el. */
    $("ul.droptrue2").droppable({
        drop: function(event, ui) {
            var file = $(ui.draggable).attr("id");
            var order = '&action=uploadFTPServer&menu='+module_name+'&rawmode=yes&file='+encodeURIComponent(file)+'&lista=droptrue2';
            $.post("index.php", order, function(theResponse){
                ftp_mostrar_aviso(theResponse);
            });
        }
    });

}));
