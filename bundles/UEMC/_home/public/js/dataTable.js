function refrescarTabla(data,account,cleanAll)
{
    //Se añade a cada fila el account que le corresponde
    data.forEach(function(element) {
        element.accountId = account.accountId;
    });

    let tabla=$('#explorer');
    tabla.data('account',account);

    /* -------------- TABLA ---------------- */
    if (!tabla.hasClass('tabla-creada') || cleanAll) {
        crearTabla(data,account);
    } else {
        tabla.DataTable().rows.add(data).draw();
    }
}

function crearTabla(data,account)
{
    let tabla=$('#explorer');

    /* -------------- BOTONES TABLA ---------------- */
    let buttonBack =
        {
            text: '<i class="bi bi-arrow-return-left me-2"></i>Volver atrás',
            className: 'btn btn-xs',
            action: function ()
            {
                back(account);
            }
        }
    let buttonCrearCarpeta =
        {
            text: '<i class="bi bi-folder-fill me-2"></i>Crear carpeta',
            className: 'btn btn-xs',
            action: function ()
            {
                $('#newDirFileModal').modal('show');
                $('#newNameButton').attr('onclick','createDir($(\'#newName\').val(),\''+account.accountId+'\')');
            }
        }
    let buttonCrearFichero =
        {
            text: '<i class="bi bi-file-text-fill me-2"></i>Crear fichero',
            className: 'btn btn-xs',
            action: function ()
            {
                $('#newDirFileModal').modal('show');
                $('#newNameButton').attr('onclick','createFile($(\'#newName\').val(),\''+account.accountId+'\')');
            }
        }
    let buttonSubirArchivo =
        {
            text: '<i class="bi bi-upload me-2"></i>Subir archivo',
            className: 'btn btn-xs',
            action: function ()
            {
                //Off desvincula el boton cada vez que se recrea la tabla
                $('#formFile-explorer').off('change').on('change', function() {
                    upload(account.accountId);
                });
                $('#formFile-explorer').trigger('click');
            }
        }
    let buttonEditarArchivo =
        {
            text: '<i class="bi bi-pencil-square me-2"></i>Editar archivo',
            className: 'btn-xs',
            action: function ()
            {
                let filaSeleccionada = tabla.DataTable().row({ selected: true }).data();

                if (filaSeleccionada) {
                    editarModalMetadata(filaSeleccionada.path,filaSeleccionada.accountId);
                    console.log('Fila seleccionada:', filaSeleccionada);
                } else {
                    // Manejo para cuando no se ha seleccionado ninguna fila
                    console.log('No se ha seleccionado ninguna fila');
                }
            }
        }
    let buttonEliminarArchivo =
        {
            text: '<i class="bi bi-trash me-2"></i>Elimiar archivo',
            className: 'btn-xs',
            action: function ()
            {
                let filaSeleccionada = tabla.DataTable().row({ selected: true }).data();

                if (filaSeleccionada) {
                    dlt(filaSeleccionada.path,filaSeleccionada.accountId);
                    console.log('Fila seleccionada:', filaSeleccionada);
                } else {
                    // Manejo para cuando no se ha seleccionado ninguna fila
                    console.log('No se ha seleccionado ninguna fila');
                }
            }
        }
    let buttonCopiarArchivo =
        {
            text: '<i class="bi bi-clipboard me-2"></i>Copiar archivo',
            className: 'btn btn-secondary btn-xs',
            action: function ()
            {
                let filaSeleccionada = tabla.DataTable().row({ selected: true }).data();

                if (filaSeleccionada) {
                    copy(filaSeleccionada.path,filaSeleccionada.accountId);
                    console.log('Fila seleccionada:', filaSeleccionada);
                } else {
                    // Manejo para cuando no se ha seleccionado ninguna fila
                    console.log('No se ha seleccionado ninguna fila');
                }
            }
        }
    let buttonMoverArchivo =
        {
            text: '<i class="bi bi-arrows-move me-2"></i>Mover archivo',
            className: 'btn btn-secondary btn-xs',
            action: function ()
            {
                let filaSeleccionada = tabla.DataTable().row({ selected: true }).data();

                if (filaSeleccionada) {
                    move(filaSeleccionada.path,filaSeleccionada.accountId);
                    console.log('Fila seleccionada:', filaSeleccionada);
                } else {
                    // Manejo para cuando no se ha seleccionado ninguna fila
                    console.log('No se ha seleccionado ninguna fila');
                }
            }
        }

    tabla.addClass('tabla-creada');
    tabla.DataTable().destroy();
    tabla.DataTable({
        dom: 'Bfrtip', // 'B' option para activar los botones
        dom: "<'row'<'col-sm-12'B>>" +
            "<'row mt-2'<'col-sm-6 ruta-explorer'><'col-sm-6'f>>"+
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-5'i><'col-sm-7'p>>",
        buttons: [
            buttonBack,
            buttonCrearCarpeta,
            buttonCrearFichero,
            buttonSubirArchivo,
            buttonEliminarArchivo,
            buttonEditarArchivo,
            buttonCopiarArchivo,
            buttonMoverArchivo,
        ],
        initComplete: function () { //Se modifica el bloque ruta- definido en dom:
            $('div.ruta-explorer').html('' +
                '<div id="divUpload" class="m-1 d-flex align-items-center">\n' +
                '<pre id="ruta-p-explorer" ></pre>\n'+
                '</div>');
        },
        stateSave: true,
        info: false,
        ordering: true,
        select: {
            style: 'multi+shift',
            selector: 'td:first-child'
        },
        order: [[3, 'desc']],
        paging: false,
        data: data,
        columns: [
            {
                data: null,
                orderable: false,
                render: DataTable.render.select()
            },
            { title: '', //Simbolo carpeta o fichero
                data: 'type',
                visible: true,
                width: '0.1%',
                className: 'dt-body-right align-middle',
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
                className: 'dt-body-left align-middle',
                visible: true,
                render: function (data) {
                    data=data.replace(/\//g, '\\');
                    let parts=data.split('\\');
                    let name = parts[parts.length - 1];
                    return '<span class="btn p-0 border-0">'+name+'</span>';
                }
            },
            { title: 'Visibility',
                data: 'visibility',
                visible: false,
            },
            { title: 'Fecha',
                data: 'last_modified',
                className: 'align-middle',
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
                class: 'align-middle',
                render:function ()
                {
                    let account = tabla.data('account');
                    switch (account.controller)
                    {
                        case 'onedrive':
                            return '<i class="bi bi-microsoft me-2"></i>'+account.user;
                        case 'googledrive':
                            return '<i class="bi bi-google me-2"></i>'+account.user;
                        case 'ftp':
                            return '<i class="bi bi-hdd-rack me-2"></i>'+account.user;
                        case 'owncloud':
                            return '<i class="bi bi-clouds me-2"></i>'+account.user;
                        default:
                            return '--';
                    }
                },
            }
        ]
    });

    /* -------------- ACCIONES ---------------- */

    tabla.off('click', 'td:nth-child(3)'); //Hay que desvincular el elemtno para que no se repita
    tabla.on('click', 'td:nth-child(3)', function () {

        let data = tabla.DataTable().row(this).data();
        if (data.type==='dir')
        {
            loadData(data.accountId,data.path.charAt(0) === '\\' ? data.path.slice(1) : data.path );
        }else if (data.type==='file')
        {
            data.path=data.path.replace(/\//g, '\\');
            let parts=data.path.split('\\');
            let name = parts[parts.length - 1];
            download(data.path,name,data.accountId);
        }
    });
}