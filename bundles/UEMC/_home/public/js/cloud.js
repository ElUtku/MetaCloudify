function Account(accountId,controller,user,root,pathActual,parent)
{
    this.accountId=accountId;
    this.controller=controller;
    this.user=user;
    this.root=root;
    this.pathActual=pathActual ?? undefined;
    this.parent=parent ?? undefined;
}

$(document).ready(function() {

    /*--- Carga de selectes de los epxloradores ---*/
    loadSelects();
    $('#selectTabla1').change(function() {
        let accountId = $(this).children("option:selected").val();
        let path = '';

        loadData(accountId,path,'explorer1');

        $('#uploadexplorer1').removeClass('d-none');
    });

    $('#selectTabla2').change(function() {
        let accountId = $(this).children("option:selected").val();
        let path = '';

        loadData(accountId,path,'explorer2');

        $('#uploadexplorer2').removeClass('d-none');
    });

    /*--- Esconder y borrar modal de metadatos en cada interacción ---*/
    $('#modalMetadatos').on('hidden.bs.modal', function () {
        $('.mb-3.dynamic').remove(); // Eliminar todos los elementos con la clase 'mb-3' y 'dynamic'
    });

});

function loadData(accountId,path,explorer) {

    path = (typeof path !== 'undefined') ? path : '';

    let account = getAccount(accountId);
    $.data($('#'+explorer,'account',account));
    try {
        //test/a/b/c -> [test],[a],[b],[c] -> [test],[a],[b] -> test/a/b
        account.parent=path.split('\\');
        account.parent.pop();
        account.parent=account.parent.join('\\');
    } catch (e)
    {
        account.parent = '';
    }

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
            account.pathActual=path;
            setAccount(account);
            refrescarTabla(data,explorer,account);
            $("#ruta-p-"+explorer).html('Ruta: '+path);
        },
        error: function (xhr, status, error) {
            console.error(error);
        }
    });
}

function refrescarTabla(data,explorer,account)
{

    let tabla=$('#'+explorer)
    tabla.DataTable().destroy();
    tabla.DataTable({
        dom: 'Bfrtip', // 'B' option para activar los botones
        dom: "<'row'<'col-sm-6'B><'col-sm-3 ruta-" + explorer + "'><'col-sm-3'f>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-5'i><'col-sm-7'p>>",
        buttons: [
            { text: '<i class="bi bi-arrow-return-left me-2"></i>Volver atrás', className: 'btn', action: function (){back(explorer,account);} },
            { text: '<i class="bi bi-folder-fill me-2"></i>Crear carpeta', className: 'btn ', action: function () {$('#newDirFileModal').modal('show');$('#newNameButton').attr('onclick','createDir($(\'#newName\').val(),\''+account.accountId+'\',\''+explorer+'\')');}},
            { text: '<i class="bi bi-file-text-fill me-2"></i>Crear fichero', className: 'btn ', action: function () {$('#newDirFileModal').modal('show');$('#newNameButton').attr('onclick','createFile($(\'#newName\').val(),\''+account.accountId+'\',\''+explorer+'\')');}},
            { text: '<i class="bi bi-upload me-2"></i>Subir archivo', className: 'btn ', action: function () {
                $('#formFile-'+explorer).change(function() {
                    upload(account.accountId, explorer);
                });
                $('#formFile-'+explorer).trigger('click');
            }},
        ],
        initComplete: function () {
            $('div.ruta-'+explorer).html('' +
                '<div id="divUpload" class="m-1 d-flex align-items-center">\n' +
                   '<pre id="ruta-p-'+explorer+'" ></pre>\n'+
                '</div>');
        },
        stateSave: true,
        info: false,
        ordering: true,
        paging: false,
        data: data,
        columns: [
            { title: '',
                data: 'type',
                visible: true,
                width: '0.1%',
                className: 'dt-body-right',
                orderable: false,
                searchable: false,
                render: function (data) {
                    if (data === 'dir') {
                        return '<i class="bi bi-folder-fill"></i>';
                    } else if (data === 'file') {
                        return '<i class="bi bi-file-text-fill"></i>';
                    }
                }
            },
            { title: 'Nombre',
                data: 'path',
                visible: true,
                render: function (data) {
                    data=data.replace(/\//g, '\\');
                    let parts=data.split('\\');
                    let name = parts[parts.length - 1];
                    return name;
                }
            },
            { title: 'Visibility',
                data: 'visibility',
                visible: false,
            },
            { title: 'Fecha',
                data: 'last_modified',
                visible: true,
                render:function (data)
                {
                    return formatDate(data);
                },
            },
            { title: 'Metadata',
                data: 'extra_metadata',
                visible: false,
            },
            { title: 'Propietario',
                visible: true,
                render:function ()
                {
                    return account.user;
                },
            },
            { title: 'Acciones',
                orderable: false,
                searchable: false,
                width: '20%',
                data: 'path',
                render: function (data, type, row) {
                    data=data.replace('\\', '/');
                    return '<button class="btn btn-primary btn-editar me-2" onclick="editarModalMetadata(\'' + data + '\',\'' + account.accountId + '\');">Editar</button>'+
                           '<button class="btn btn-danger btn-eliminar" onclick="dlt(\'' + data + '\', \'' + account.accountId + '\', \'' + explorer + '\')">Eliminar</button>'
                }
            }
        ]
    });

    tabla.off('mouseenter', 'td:nth-child(2)'); //Hay que desvincular el elemtno para que no se repita
    tabla.off('mouseleave', 'td:nth-child(2)'); //Hay que desvincular el elemtno para que no se repita
    tabla.on('mouseenter', 'td:nth-child(2)', function() {
        $(this).addClass('text-primary').css('cursor', 'pointer'); // Cambiar color del texto y cursor al pasar el ratón sobre la celda
    }).on('mouseleave', 'td:nth-child(2)', function() {
        $(this).removeClass('text-primary').css('cursor', 'default'); // Restaurar color del texto y cursor al salir del ratón de la celda
    });

    tabla.off('click', 'td:nth-child(2)'); //Hay que desvincular el elemtno para que no se repita
    tabla.on('click', 'td:nth-child(2)', function () {

        let data = tabla.DataTable().row(this).data();
        if (data.type==='dir')
        {
           loadData(account.accountId,data.path.charAt(0) === '\\' ? data.path.slice(1) : data.path ,explorer);
        }else if (data.type==='file')
        {
            data.path=data.path.replace(/\//g, '\\');
            let parts=data.path.split('\\');
            let name = parts[parts.length - 1];
            download(data.path,name,account.accountId);
        }
    });
}
function createDir(name,accountId,explorer)
{
    let account = getAccount(accountId);
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
            loadData(account.accountId,account.pathActual,explorer);
        },
        error: function (xhr, status, error) {
            console.error(error);
        }
    });
}

function createFile(name,accountId,explorer)
{
    let account = getAccount(accountId);
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
            loadData(account.accountId,account.pathActual,explorer);
        },
        error: function (xhr, status, error) {
            console.error(error);
        }
    });
}

function dlt(path,accountId,explorer)
{
    let account = getAccount(accountId);
    $.ajax({
        url: account.controller+"/drive/delete",
        method: 'POST',
        data: {
            path: path,
            accountId: accountId
        },
        success: function () {
            // Actualiza dinámicamente el contenido en la página
            loadData(accountId,account.pathActual,explorer);
        },
        error: function (xhr, status, error) {
            console.error(error);
        }
    });

    //Quitar el context Menu
    $('#contextMenu').addClass('d-none').removeClass('d-block');
    $(document).off('click.menuClose');
}

function upload(accountId,explorer)
{
    let account = getAccount(accountId)

    let fileupload = $('#fileupload-'+explorer);
    fileupload.fileupload({
        url: account.controller+'/drive/upload',
        dataType: 'json',
        formData: { path: account.pathActual,
                    accountId: accountId
        },
        done: function () {
            loadData(accountId,account.pathActual,explorer);
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
    //fileupload.prop('action', '');

    // Inicia la carga del archivo
    fileupload.fileupload('send', { files: $('#formFile-'+explorer)[0].files });
}

function download(path,name,accountId)
{
    let account = getAccount(accountId);

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

function back(explorer, account)
{
    loadData(account.accountId,account.parent,explorer);
}

function guardarMetadata(path, accountId)
{

    //----------------Detectar y establcer campos----------------
// Crear un objeto formData para almacenar los datos del formulario
    let formData = {};

// Bandera para verificar si se han encontrado campos adicionales aparte de 'author' y 'visibility'
    let extraFieldsFound = false;

// Iterar sobre los elementos del formulario en el modal (se seleccionan los inputs y el select)
    $('#modalMetadatos .modal-body input, #modalMetadatos .modal-body select').each(function() {
        let fieldName = $(this).attr('name');
        let fieldValue = $(this).val();

        // Verificar si el campo es 'author' o 'visibility'
        if (fieldName === 'author' || fieldName === 'visibility') {
            // Agregar el campo directamente al objeto formData
            formData[fieldName] = fieldValue;
        } else {
            // Agregar el campo al objeto extra con su respectivo nombre y valor
            if (!formData.hasOwnProperty('extra')) {
                formData.extra = {};
                extraFieldsFound = true; // Se encontraron campos adicionales
            }
            formData.extra[fieldName] = fieldValue;
        }
    });

// Si no se encontraron campos adicionales, establecer extra en null
    if (!extraFieldsFound) {
        formData.extra = null;
    }

// Convertir el objeto formData a JSON
    formData = JSON.stringify(formData);

    //----------------Realizar llamada----------------

    let account = getAccount(accountId);
    $.ajax({
        url: account.controller+'/drive/editMetadata',
        method: 'POST',
        data: {
            path: path,
            accountId: accountId,
            metadata: formData,
        },
        success: function () {
                console.log('ok');
        },
        error: function (xhr, status, error) {
            console.error(error);
        }
    });
}

function getArchiveMetadata(accountId,path)
{
    let account = getAccount(accountId);
    let metadata=null;
    $.ajax({
        url: account.controller+'/drive/getArchive',
        method: 'POST',
        async:false,
        data: {
            path: path,
            accountId: accountId,
        },
        success: function (data) {
            metadata=data;
        },
        error: function (xhr, status, error) {
            console.error(error);
            metadata=null;
        }
    });
    return metadata;
}