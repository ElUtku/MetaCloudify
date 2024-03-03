function Account(accountId,controller,user,root,pathActual,parent)
{
    this.accountId=accountId;
    this.controller=controller;
    this.user=user;
    this.root=root;
    this.pathActual=pathActual ?? undefined;
    this.parent=parent ?? undefined;
}

let accountE1;

$(document).ready(function() {

});

function loadData(accountId,path) {

    path = (typeof path !== 'undefined') ? path : '';

    let account = getAccount(accountId);
    accountE1=account;
    try {
        //test/a/b/c -> [test],[a],[b],[c] -> [test],[a],[b] -> test/a/b
        account.parent=path.split('\\');
        account.parent.pop();
        account.parent=account.parent.join('\\');
    } catch (e)
    {
        account.parent = '';
    }

    let ruta = path;
    $("#path").val(path);
    let divRuta = $("#divRuta");
    divRuta.addClass('d-flex');
    divRuta.removeClass('d-none');

    $.ajax({
        url: account.controller+'/drive',
        method: 'POST',
        data: { path: path,
                accountId: accountId
        },
        dataType: 'json',
        success: function (data) {
            if(account.controller.indexOf("owncloud")!==-1 )
            {
                data=cleanOwncloudData(data);
            }
            account.pathActual=ruta;
            updatePageContent(data,accountId,account);

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

function formatDate(timestamp)
{
    let fecha = new Date();
    fecha.setTime(timestamp * 1000);

    let opcionesDeFormato = {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    };

    let format = new Intl.DateTimeFormat('es-ES', opcionesDeFormato);

   return  format.format(fecha);
}
function updatePageContent(data,accountId,account) {
    $('#explorer').removeClass('d-none');

    let table=$('#tableBody');
    table.empty();

    $.each(data, function(index, item) {

        item.path=item.path.replace(/\//g, '\\');
        let parts=item.path.split('\\');
        let name = parts[parts.length - 1];

        let fecha = formatDate(item.last_modified);

        let col = $('<tr></tr>');

            if (item.type === 'dir') {
                col.append('<td role="button"  class="no-select underline-on-hover" data-bs-toggle="tooltip" title="' + item.path + '"><i class="bi bi-folder-fill me-2"></i>'+name+'</td>');
            } else if (item.type === 'file') {
                col.append('<td role="button"  class="no-select underline-on-hover" data-bs-toggle="tooltip" title="' + item.path + '"><i class="bi bi-file-text-fill me-2"></i>'+name+'</td>');
            }

            col.append('<td>' + fecha + '</td>');

            switch(account.controller) {
                case 'ftp':
                    col.append('<td title="' + account.controller + '"><i class="bi bi-hdd-rack me-2"></i>'+account.user+'</td>');
                    break;
                case 'onedrive':
                    col.append('<td title="' + account.controller + '"><i class="bi bi-microsoft me-2"></i>'+account.user+'</td>');
                    break;
                case 'googledrive':
                    col.append('<td title="' + account.controller + '"><i class="bi bi-google me-2"></i>'+account.user+'</td>');
                    break;
                case 'owncloud':
                    col.append('<td title="' + account.controller + '"><i class="bi bi-clouds me-2"></i>'+account.user+'</td>');
                    break;
                default:
            }


        // Si se hace click en un elemento seleccionado se recarga la pagina
        col.on('dblclick', function() {
            if (item.type==='dir')
            {
                loadData(accountId,item.path.charAt(0) === '\\' ? item.path.slice(1) : item.path);
            }else if (item.type==='file')
            {
                download(item.path,name,accountId);
            }
        });
        col.on('contextmenu', function(event) {
            event.preventDefault();

            let contextMenu = $('#contextMenu');
            let clickedElement = {
                'path': item.path,
                'name': name,
                'id': accountId
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
        table.append(col);
    });

}

function createDir(name)
{
    let account = accountE1;

    $.ajax({
        url: account.controller+"/drive/createDir",
        method: 'POST',
        data: {
            path: account.pathActual,
            name: name,
            accountId: account.accountId
        },
        success: function () {
            // Actualiza dinámicamente el contenido en la página
            loadData(account.accountId,account.pathActual);
        },
        error: function (xhr, status, error) {
            console.error(error);
        }
    });
}

function createFile(name)
{
    let account = accountE1;

    $.ajax({
        url: account.controller+"/drive/createFile",
        method: 'POST',
        data: {
            path: account.pathActual,
            name: name,
            accountId: account.accountId
        },
        success: function () {
            // Actualiza dinámicamente el contenido en la página
            loadData(account.accountId,account.pathActual);
        },
        error: function (xhr, status, error) {
            console.error(error);
        }
    });
}

function dlt(data)
{
    let account = accountE1;

    $.ajax({
        url: account.controller+"/drive/delete",
        method: 'POST',
        data: {
            path: data.path,
            name: data.name,
            accountId: account.accountId
        },
        success: function () {
            // Actualiza dinámicamente el contenido en la página
            loadData(account.accountId,account.pathActual);
        },
        error: function (xhr, status, error) {
            console.error(error);
        }
    });

    //Quitar el context Menu
    $('#contextMenu').addClass('d-none').removeClass('d-block');
    $(document).off('click.menuClose');
}

function upload(accountId)
{
    let account = getAccount(accountId)

    let fileupload = $('#fileupload');
    fileupload.fileupload({
        url: account.controller+'/drive/upload',
        dataType: 'json',
        formData: { path: account.pathActual,
                    accountId: accountId
        },
        done: function () {
            loadData(accountId,account.pathActual);
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

function download(path,name,accountId)
{
    let account = getAccount(accountId)

    $.ajax({
        url: account.controller+"/drive/download",
        method: 'POST',
        data: {
            path: path,
            name: name,
            accountId: accountId
        },
        success: function (data) {

            //Se obtiene el nombre del archivo a descargar
            let pathSplit = path.split('\\');
            let name = pathSplit[pathSplit.length - 1];

            //Se descarga el archivo
            let blob = new Blob([data], { type: 'application/octet-stream' });
            let link = document.createElement('a');
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

function logout(accountId)
{
    let account = getAccount(accountId)

    $.ajax({
        url: account.controller+'/logout',
        method: 'POST',
        data: {
            accountId: accountId
        },
        success: function () {
            sessionStorage.removeItem('storedAccounts');
            location.reload();
        },
        error: function (xhr, status, error) {
            console.error(error);
        }
    });
}

function back()
{
    loadData(accountE1.accountId,accountE1.parent);
}
