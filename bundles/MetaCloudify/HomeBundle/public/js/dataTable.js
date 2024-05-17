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

let archivoEnCopia;
let pegar = false;
let copyStatus=false;
let moveStatus=false;
function crearTabla(data,account)
{
    let tabla=$('#explorer');

    /* -------------- BOTONES TABLA ---------------- */
    let buttonHome =
        {
            text: '<i class="bi bi-house me-2"></i>Inicio',
            className: 'btn btn-xs',
            action: function ()
            {
                tabla.off('click', 'td:nth-child(2) '); //Evento de buscarMetadatos
                desactivarBtnBuscarMetadata();

                tabla.DataTable().rows( { selected: true } ).deselect()
                recargasCuentas();
            }
        };
    let buttonBack =
        {
            text: '<i class="bi bi-arrow-return-left me-2"></i>Volver atrás',
            className: 'btn btn-xs',
            action: function ()
            {
                tabla.off('click', 'td:nth-child(2) '); //Evento de buscarMetadatos
                desactivarBtnBuscarMetadata();

                tabla.DataTable().rows( { selected: true } ).deselect()
                back(account);
            }
        };
    let buttonCrearCarpeta = {
        text: '<i class="bi bi-folder-fill me-2 "></i>Crear carpeta',
        className: 'btn btn-xs btn-crearCarpeta',
        action: function() {

            tabla.off('click', 'td:nth-child(2)');
            desactivarBtnBuscarMetadata();

            let accounts = getAccounts();

// Se determina el ID de la cuenta
            let accountIdPromise = account.pathActual === account.root && Object.keys(accounts).length !== 1 ?
                optionsSelectAccounId() :
                Promise.resolve(account.accountId);

            accountIdPromise.then(function(accountId) {
                $('#newNameButton').off('click').on('click', function() {
                    let newName = sanitizeText($("#newName").val()); // Sanitizar el valor del input
                    createDir(newName, accountId);
                });
                $('#newDirFileModal').modal('show');
            });
        }
    };
    let buttonCrearFichero = {
        text: '<i class="bi bi-file-text-fill me-2 "></i>Crear fichero',
        className: 'btn btn-xs btn-crearFichero',
        action: function() {

            tabla.off('click', 'td:nth-child(2) ');
            desactivarBtnBuscarMetadata();

            let accounts = getAccounts();

// Se determina el ID de la cuenta
            let accountIdPromise = account.pathActual === account.root && Object.keys(accounts).length !== 1
                ? optionsSelectAccounId()
                : Promise.resolve(account.accountId);

            accountIdPromise.then(function(accountId) {
                $('#newNameButton').off('click').on('click', function() {
                    let newName = sanitizeText($("#newName").val());
                    createFile(newName, accountId);
                });
                $('#newDirFileModal').modal('show');
            });
        }
    };
    let buttonSubirArchivo = {
        text: '<i class="bi bi-upload me-2"></i>Subir archivos',
        className: 'btn btn-xs btn-subir',
        action: function() {

            let accounts = getAccounts();
            let accountIdPromise = account.pathActual === account.root && Object.keys(accounts).length!==1
                                                    ? optionsSelectAccounId()
                                                    : Promise.resolve(account.accountId);

            accountIdPromise.then(function(accountId) {
                $('#formFile-explorer').off('change').on('change', function() {
                    upload(accountId);
                }).trigger('click');
            });
        }
    };
    let buttonVerMetadatos =
        {
            text: '<i class="bi bi-search me-2"></i>Ver Metadatos',
            className: 'btn-xs',
            action: function ()
            {

                let filaSeleccionada = tabla.DataTable().row({ selected: true }).data();

                if (filaSeleccionada) {
                    verModalMetadata(filaSeleccionada.path,filaSeleccionada.accountId).then();
                } else {
                    // Manejo para cuando no se ha seleccionado ninguna fila
                    console.log('No se ha seleccionado ninguna fila');
                }
            }
        };
    let buttonBuscarPorMetadatos =
        {
            text: '<i class="bi bi-binoculars me-2"></i>Buscar metadatos',
            className: 'btn-xs',
            action: function ()
            {
                $('#modalBuscarMetadatos').modal('show');

                tabla.off('click', 'td:nth-child(2) '); //Hay que desvincular el elemtno para que no se repita
                tabla.on('click', 'td:nth-child(2) ', function () {

                    let data = tabla.DataTable().row(this.parentNode).data();
                    $('#ruta-p-explorer').html(data.path);
                });

            }
        };
    let buttonEliminarArchivo =
        {
            text: '<i class="bi bi-trash me-2"></i>Elimiar',
            className: 'btn-xs',
            action: function ()
            {
                modalConfirmaEliminar().then(function(confirm) {
                    if (confirm)
                    {
                        let filasSeleccionadas = tabla.DataTable().rows({ selected: true }).data().toArray();
                        let filasIndices = tabla.DataTable().rows({ selected: true }).indexes();

                        filasSeleccionadas.forEach(function(fila, index) {
                            if (fila) {
                                dlt(fila.path, fila.accountId, filasIndices[index]);
                            } else {
                                console.log('No se ha seleccionado ninguna fila');
                            }
                        });
                    }
                });
            }
        };
    let buttonCopiarArchivo =
        {
            text: '<i class="bi bi-clipboard me-2"></i>Copiar archivo',
            className: 'btn btn-secondary btn-xs btn-copiar',
            action: function ()
            {

                if (pegar===false) {
                    let filaSeleccionada = tabla.DataTable().row({ selected: true }).data();
                    if(filaSeleccionada)
                    {
                        copyStatus=true;
                        pegar = true;

                        archivoEnCopia = filaSeleccionada;

                        activarBtnCopiar();
                    }
                }else if (pegar===true) {
                    copyStatus=false;
                    pegar = false;

                    let accounts = getAccounts();
                    if (account.pathActual===account.root && Object.keys(accounts).length!==1)
                    {
                        optionsSelectAccounId().then(function(accountId) {
                            copy(archivoEnCopia.path, archivoEnCopia.accountId, accountId);
                        });
                    } else
                    {
                        copy(archivoEnCopia.path, archivoEnCopia.accountId, account.accountId);
                    }

                    descativarBtnCopiar();

                } else {
                    console.log('No se ha seleccionado ninguna fila');
                }
                tabla.DataTable().rows( { selected: true } ).deselect()
            }
        };
    let buttonMoverArchivo =
        {
            text: '<i class="bi bi-arrows-move me-2"></i>Mover archivo',
            className: 'btn btn-secondary btn-xs btn-mover',
            action: function ()
            {

                if (pegar===false) {
                    let filaSeleccionada = tabla.DataTable().row({ selected: true }).data();
                    if(filaSeleccionada)
                    {
                        moveStatus=true;
                        archivoEnCopia = filaSeleccionada;
                        pegar = true;

                        activarBtnMover();
                    }
                }else if (pegar===true) {

                    moveStatus=false;
                    pegar = false;

                    let accounts = getAccounts();
                    if (account.pathActual===account.root && Object.keys(accounts).length!==1)
                    {
                        optionsSelectAccounId().then(function(accountId) {
                            move(archivoEnCopia.path, archivoEnCopia.accountId, accountId);
                        });
                    } else
                    {
                        move(archivoEnCopia.path, archivoEnCopia.accountId, account.accountId);
                    }

                    descartivarBtnMover();

                } else {
                    console.log('No se ha seleccionado ninguna fila');
                }

                tabla.DataTable().rows( { selected: true } ).deselect()
            }
        };
    let buttonCancelPaste =
        {
            text: '<i class="bi bi-x-circle me-2"></i>Cancelar',
            className: 'btn btn-secondary btn-xs btn-cancelar',
            enabled: false,
            action: function ()
            {
                copyStatus=false;
                moveStatus=false;
                archivoEnCopia = undefined;
                pegar = false;

                descativarBtnCopiar()

                descartivarBtnMover()

                tabla.DataTable().button('.btn-cancelar').disable();

                tabla.DataTable().rows( { selected: true } ).deselect()
            }
        };
    let definiciones=
        {
            "emptyTable": "No hay datos disponibles en la tabla",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ entradas",
            "infoEmpty": "Mostrando 0 a 0 de 0 entradas",
            "infoFiltered": "(filtrado de _MAX_ entradas totales)",
            "lengthMenu": "Mostrar _MENU_ entradas",
            "loadingRecords": "Cargando...",
            "processing": "Procesando...",
            "search": "Buscar:",
            "zeroRecords": "No se encontraron registros coincidentes",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            },
            "aria": {
                "sortAscending": ": activar para ordenar la columna en orden ascendente",
                "sortDescending": ": activar para ordenar la columna en orden descendente"
            }
        };

    tabla.addClass('tabla-creada');
    tabla.DataTable().destroy();
    tabla.DataTable({
        "language": definiciones,
        responsive: true,
        dom: "<'row'<'col-sm-12'B>>" +
            "<'row mt-2'<'col-sm-6 ruta-explorer'><'col-sm-6'f>>"+
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-5'i><'col-sm-7'p>>",
        buttons: [
            buttonHome,
            buttonBack,
            buttonCrearCarpeta,
            buttonCrearFichero,
            buttonSubirArchivo,
            buttonEliminarArchivo,
            buttonVerMetadatos,
            buttonBuscarPorMetadatos,
            buttonCopiarArchivo,
            buttonMoverArchivo,
            buttonCancelPaste,
        ],
        initComplete: function () { //Se modifica el bloque ruta- definido en dom:
            $('div.ruta-explorer').html('' +
                '<div id="divUpload" class="m-1 d-flex align-items-center">\n' +
                '<pre>Ruta: <div class="d-inline-flex" id="ruta-p-explorer"></div></pre>\n'+
                '</div>');
        },
        stateSave: true,
        info: false,
        ordering: true,
        select: 'single',
        order: [[4, 'desc']],
        paging: false,
        data: data,
        columns: [
            { title: '', //Simbolo carpeta o fichero
                data: 'type',
                defaultContent: '-',
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
                defaultContent: '-',
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
                defaultContent: '-',
                visible: false,
            },
            { title: 'Fecha',
                data: 'last_modified',
                className: 'align-middle',
                defaultContent: '-',
                visible: true,
                render:function (data)
                {
                    return formatDate(data);
                },
            },
            { title: 'Metadata',
                data: 'extra_metadata',
                defaultContent: '-',
                visible: false,
            },
            { title: 'Tamaño',
                data: 'file_size',
                defaultContent: '-',
                visible: true,
                width: '5%',
                className: 'text-start align-middle',
                render: function(data) {
                    return data ? formatBytes(data) : '-'; // Si data es null o undefined, devuelve '-', de lo contrario devuelve el tamaño
                }
            },
            { title: 'Propietario',
                visible: true,
                defaultContent: '-',
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
                            return '-';
                    }
                },
            }
        ]
    });

    /* -------------- ACCIONES ---------------- */

    tabla.off('dblclick', 'td:nth-child(2) span'); //Hay que desvincular el elemtno para que no se repita
    tabla.on('dblclick', 'td:nth-child(2) span', function () {

        let data = tabla.DataTable().row(this.parentNode).data();
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

    //Una vez recargada la tabla, se comprueba si exiten fichero apra mover o copiar
    if(copyStatus)
    {
        activarBtnCopiar();
    } else if(moveStatus)
    {
        activarBtnMover()
    }
}

function activarBtnCopiar()
{
    let tabla=$('#explorer').DataTable();
    let btnCopiar=$('.btn-copiar');
    let btnCancelar=$('.btn-cancelar');

    tabla.button('.btn-cancelar').enable();
    tabla.button('.btn-mover').disable();
    btnCopiar.removeClass('btn-secondary');
    btnCopiar.addClass('btn-success');

    btnCancelar.addClass('btn-danger');
    btnCancelar.removeClass('btn-secondary');
}

function descativarBtnCopiar(){
    let tabla=$('#explorer').DataTable();
    let btnCopiar=$('.btn-copiar');
    let btnCancelar=$('.btn-cancelar');

    tabla.button('.btn-cancelar').disable();
    tabla.button('.btn-mover').enable();

    btnCopiar.addClass('btn-secondary');
    btnCopiar.removeClass('btn-success');

    btnCancelar.removeClass('btn-danger');
    btnCancelar.addClass('btn-secondary');
}

function activarBtnMover()
{
    let tabla=$('#explorer').DataTable();
    let btnMover=$('.btn-mover');
    let btnCancelar=$('.btn-cancelar');

    tabla.button('.btn-cancelar').enable();
    tabla.button('.btn-copiar').disable();

    btnMover.removeClass('btn-secondary');
    btnMover.addClass('btn-warning');

    btnCancelar.addClass('btn-danger');
    btnCancelar.removeClass('btn-secondary');
}
function descartivarBtnMover()
{
    let tabla=$('#explorer').DataTable();
    let btnMover=$('.btn-mover');
    let btnCancelar=$('.btn-cancelar');

    tabla.button('.btn-cancelar').disable();
    tabla.button('.btn-copiar').enable();

    btnMover.addClass('btn-secondary');
    btnMover.removeClass('btn-warning');

    btnCancelar.removeClass('btn-danger');
    btnCancelar.addClass('btn-secondary');
}

function activarBtnBuscarMetadata()
{
    let tabla=$('#explorer').DataTable();

    tabla.button('.btn-subir').disable();
    tabla.button('.btn-crearCarpeta').disable();
    tabla.button('.btn-crearFichero').disable();
}
function desactivarBtnBuscarMetadata()
{
    let tabla=$('#explorer').DataTable();

    tabla.button('.btn-subir').enable();
    tabla.button('.btn-crearCarpeta').enable();
    tabla.button('.btn-crearFichero').enable();
}