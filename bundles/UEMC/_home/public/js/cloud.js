var parent;
var controller;
var pathActual;


$(document).ready(function() {

});




function loadData(path) {
    pathActual=path;

    try {
        parent=path.split('\\');
        parent.pop();
        parent=parent.join('\\');
    } catch (e)
    {
        parent = '';
    }

    var ruta = $("#path");
    ruta.val(path);
    var divRuta = $("#divRuta");
    divRuta.addClass('d-flex');
    divRuta.removeClass('d-none');

    $.ajax({
        url: controller,
        method: 'POST',
        data: { path: path },
        dataType: 'json',
        success: function (data) {
            if(controller.indexOf("owncloud")!==-1 )
            {
                data=cleanOwncloudData(data);
            }

            updatePageContent(data);

            $('#divUpload').removeClass('d-none').addClass('d-block');
        },
        error: function (xhr, status, error) {
            console.error(error);
        }
    });
}

//Se debe eliminar remote.php/dav/files/{user} para poder navegar por los directorios
function cleanOwncloudData(data)
{
    return data.map(function (elemento) {
        elemento.path = elemento.path.replace(/remote\.php\/dav\/files\/\w+\//g, '');
        return elemento;
    });
}

function updatePageContent(data) {
    var container = $('#explorer1');
    container.empty();

    $.each(data, function(index, item) {
        item.path=item.path.replace(/\//g, '\\');
        var parts=item.path.split('\\');
        var name = parts[parts.length - 1];

        var row = $('<div class="row d-inline-block no-select underline-on-hover" role="button" data-bs-toggle="tooltip" title="' + item.path + '"></div>');

        if (item.type === 'dir') {
            row.append('<i class="bi bi-folder-fill"></i>');
        } else if (item.type === 'file') {
            row.append('<i class="bi bi-file-text-fill"></i>');
        }

        row.append(name); // Se agrega el nombre del fichero/directorio

        // Si se hace click en un elemento seleccionado se recarga la pagina
        row.on('dblclick', function() {
            if (item.type==='dir')
            {
                loadData(item.path.charAt(0) === '\\' ? item.path.slice(1) : item.path);
            }else if (item.type==='file')
            {
                download(item.path);
            }
        });
        row.on('contextmenu', function(event) {
            event.preventDefault();

            var contextMenu = $('#contextMenu');
            var clickedElement = {
                'path': item.path,
                'name': name
        }; //Se guarda el elemento sobre el que se hizo click

            contextMenu.removeClass('d-none').addClass('d-block');
            contextMenu.css({
                left: event.clientX-27 ,
                top: event.clientY-50
            });


            // Se agrega un evento de clic fuera del menú
            $(document).on('click.menuClose', function(event) {
                // Se verifica si el clic fue fuera del menú contextual
                if (!contextMenu.is(event.target) && contextMenu.has(event.target).length === 0) {
                    contextMenu.addClass('d-none').removeClass('d-block');
                    // Se quita el evento de clic fuera del menú una vez que se ha ejecutado
                    $(document).off('click.menuClose');
                }
            });

            //Se ejecuta si se hace click sobre eliminar cuando el meu contextual esta desplegado para un elemento
            $('#buttonDlt').on('click', function() {
                event.preventDefault();
                dlt(clickedElement); // Enviar el objeto como parámetro a la función dlt()
            });

        });
        container.append(row);
    });

}

function createDir(name)
{
    $.ajax({
        url: controller+"/createDir",
        method: 'POST',
        data: {
            path: pathActual,
            name: name
        },
        success: function () {
            // Actualiza dinámicamente el contenido en la página
            loadData(pathActual);
        },
        error: function (xhr, status, error) {
            console.error(error);
        }
    });
}

function createFile(name)
{
    $.ajax({
        url: controller+"/createFile",
        method: 'POST',
        data: {
            path: pathActual,
            name: name
        },
        success: function () {
            // Actualiza dinámicamente el contenido en la página
            loadData(pathActual);
        },
        error: function (xhr, status, error) {
            console.error(error);
        }
    });
}

function dlt(data)
{
    $.ajax({
        url: controller+"/delete",
        method: 'POST',
        data: {
            path: data.path,
            name: data.name
        },
        success: function () {
            // Actualiza dinámicamente el contenido en la página
            loadData(pathActual);
        },
        error: function (xhr, status, error) {
            console.error(error);
        }
    });

    //Quitar el context Menu
    $('#contextMenu').addClass('d-none').removeClass('d-block');
    $(document).off('click.menuClose');
}

function upload()
{
    let fileupload = $('#fileupload');
    fileupload.fileupload({
        url: controller+'/upload',
        dataType: 'json',
        formData: { path: pathActual },
        done: function () {
            loadData(pathActual);
        },
        /*progressall: function (e, data) {
            // Actualiza la barra de progreso
            var progress = parseInt(data.loaded / data.total * 100, 10);
            $('#progress').css('width', progress + '%');
        },*/
        fail: function (e, data) {
            console.log('Error al cargar el archivo:', data.errorThrown);
        }
    });

    // Desactiva el envío automático del formulario
    fileupload.prop('action', '');

    // Inicia la carga del archivo
    fileupload.fileupload('send', { files: $('#formFile')[0].files });
}

function download(path)
{
    console.log(path);
    $.ajax({
        url: controller+"/download",
        method: 'POST',
        data: {
            path: path
        },
        success: function (data) {

            //Se obtiene el nombre del archivo a descargar
            var pathSplit = path.split('\\');
            var name = pathSplit[pathSplit.length - 1];

            //Se descarga el archivo
            var blob = new Blob([data], { type: 'application/octet-stream' });
            var link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = name;
            document.body.appendChild(link);
            link.click();
            // Se limpia el enlace después de la descarga
            document.body.removeChild(link);
        },
        error: function (xhr, status, error) {
            console.error(error);
        }
    });
}
