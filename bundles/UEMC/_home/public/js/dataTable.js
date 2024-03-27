$(document).ready(function() {

// Carga de selectes de los exploradores
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

});

function loadSelects() {
// Obtener cuentas del almacenamiento local
    let storedAccounts = JSON.parse(sessionStorage.getItem('storedAccounts'));

    let selectTabla1=$('#selectTabla1');
    let selectTabla2=$('#selectTabla2');

// Limpiar opciones de los select
    selectTabla1.empty();
    selectTabla2.empty();

// Agregar opciones al select de cada tabla
    if (storedAccounts && typeof storedAccounts === 'object') {

        selectTabla1.append($('<option>').text('Seleccionar').attr('selected', true).attr('disabled', true));
        selectTabla2.append($('<option>').text('Seleccionar').attr('selected', true).attr('disabled', true));

        let accountsArray = Object.values(storedAccounts);
        accountsArray.forEach(function(account) {
            let option = $('<option>').val(account.accountId).text(account.user + ' - ' + account.controller);
            selectTabla1.append(option);
            selectTabla2.append(option.clone()); // Clonar opción para el segundo select
        });

    } else {

        selectTabla1.append($('<option>').text('No hay cuentas disponibles'));
        selectTabla2.append($('<option>').text('No hay cuentas disponibles'));

    }
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
            { text: '<i class="bi bi-arrow-return-left me-2"></i>Volver atrás', className: 'btn btn-md', action: function ()
                {
                    back(explorer,account);
                }},
            { text: '<i class="bi bi-folder-fill me-2"></i>Crear carpeta', className: 'btn btn-md', action: function ()
                {
                    $('#newDirFileModal').modal('show');
                    $('#newNameButton').attr('onclick','createDir($(\'#newName\').val(),\''+account.accountId+'\',\''+explorer+'\')');
                }},
            { text: '<i class="bi bi-file-text-fill me-2"></i>Crear fichero', className: 'btn btn-md', action: function ()
                {
                    $('#newDirFileModal').modal('show');
                    $('#newNameButton').attr('onclick','createFile($(\'#newName\').val(),\''+account.accountId+'\',\''+explorer+'\')');
                }},
            { text: '<i class="bi bi-upload me-2"></i>Subir archivo', className: 'btn btn-md', action: function ()
                {
                    let formFile=$('#formFile-'+explorer);

                    formFile.change(function() {
                        upload(account.accountId, explorer);
                    });
                    formFile.trigger('click');
                }},
        ],
        initComplete: function () { //Se modifica el bloque ruta- definido en dom:
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
            { title: '', //Simbolo carpeta o fichero
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
                render: function (data) {
                    data=data.replace('\\', '/');
                    return '<button class="btn btn-primary btn-sm btn-editar me-2" onclick="editarModalMetadata(\'' + data + '\',\'' + account.accountId + '\');">Editar</button>'+
                        '<button class="btn btn-danger btn-sm btn-eliminar" onclick="dlt(\'' + data + '\', \'' + account.accountId + '\', \'' + explorer + '\')">Eliminar</button>'
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