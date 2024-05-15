function Account(accountId,controller,user,root,pathActual,parent)
{
    this.accountId=accountId;
    this.controller=controller;
    this.user=user;
    this.root=root;
    this.pathActual=pathActual ?? undefined;
    this.parent=parent ?? undefined;
    this.show=true;
}

$(document).ready(function() {

/* -------------- Texto superior body -------------*/
    let accounts=getAccounts();
    let textWelcome = $('#text-welcome');
    let textExplorer= $('#text-explorer');
    if(accounts!==null)
    {
        textWelcome.removeClass('d-block');
        textWelcome.addClass('d-none');
        textExplorer.removeClass('d-none');
        textExplorer.addClass('d-block');
    } else
    {
        textWelcome.removeClass('none');
        textWelcome.addClass('d-block');
        textExplorer.removeClass('d-block');
        textExplorer.addClass('d-none');
    }


    recargasCuentas();
    $('#fileupload-explorer').removeClass('d-none');
});

function mostrarOcultar(accountId)
{
    let account = getAccount(accountId);

    account.show = !account.show; //Se seta lo contrario de lo que tenga

    setAccount(account);

    location.reload();
}

function recargasCuentas()
{
    let accounts=getAccounts();
    ultimaAccion = 'volverRaiz'
    for (let accountId in accounts) {
        if (accounts.hasOwnProperty(accountId)) {
            let account = accounts[accountId];
            if(account.show)
                loadData(account.accountId, account.root);
        }
    }
}

function loadData(accountId,path) {

    path = cleanPath(typeof path !== 'undefined' ? path : '');

    let account = getAccount(accountId);

    $.data($('#explorer','account',account));

    $.ajax({
        url: account.controller+'/drive',
        method: 'GET',
        data: { path: path,
                accountId: accountId
        },
        dataType: 'json',
        beforeSend: function() {
            $('#loading-modal').modal('show');
        },
        success: function (data) {
            cargarDatos(account,path,data);
        },
        error: function (xhr) {
            console.error(xhr.status,xhr.responseJSON);
            mostrarModalErrores(xhr);
        },
        complete: function () {
            $('#loading-modal').modal('hide');
        }
    });
}

function createDir(name,accountId)
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
        beforeSend: function() {
            $('#loading-modal').modal('show');
        },
        success: function (data) {
            if(account.pathActual===account.root)
            {
                recargasCuentas();
            } else
            {
                cargarDatos(account,account.pathActual,data);
            }
        },
        error: function (xhr, status, error) {
            console.error(error);
            limpiarModalErrores();
            mostrarModalErrores(xhr);
        },
        complete: function () {
            $('#loading-modal').modal('hide');
        }
    });
}

function createFile(name,accountId)
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
        beforeSend: function() {
            $('#loading-modal').modal('show');
        },
        success: function (data) {
            if(account.pathActual===account.root)
            {
                recargasCuentas();
            } else
            {
                cargarDatos(account,account.pathActual,data);
            }
        },
        error: function (xhr, status, error) {
            console.error(error);
            limpiarModalErrores();
            mostrarModalErrores(xhr);
        },
        complete: function () {
            $('#loading-modal').modal('hide');
        }
    });
}

function dlt(path,accountId, fila)
{
    path=cleanPath(path);

    let account = getAccount(accountId);
    $.ajax({
        url: account.controller+"/drive/delete",
        method: 'DELETE',
        data: {
            path: dirname(path),
            name: basename(path),
            accountId: accountId
        },
        beforeSend: function() {
            $('#loading-modal').modal('show');
        },
        success: function () {
            let tabla=$('#explorer');
            tabla.DataTable().row(fila).remove().draw(false);
        },
        error: function (xhr, status, error) {
            console.error(error);
            limpiarModalErrores();
            mostrarModalErrores(xhr);
        },
        complete: function () {
            $('#loading-modal').modal('hide');
        }
    });
}

function upload(accountId) {
    let account = getAccount(accountId);
    const formData = new FormData();
    let files = $('#formFile-explorer')[0].files;
    let progressBar = $('#progress-bar');
    let progressText = $('#progress-text');

    // Agregar archivos al objeto FormData
    $.each(files, function(i, file) {
        formData.append('file-' + i, file);
    });

    formData.append('path', account.pathActual);
    formData.append('accountId', accountId);

    $.ajax({
        url: account.controller + '/drive/upload',
        type: 'POST',
        data: formData,
        dataType: 'json',
        processData: false,
        contentType: false,
        success: function(data) {
            if (account.pathActual === account.root) {
                recargasCuentas();
            } else {
                cargarDatos(account, account.pathActual, data);
            }
            $('#loading-progress-bar').modal('hide');
        },
        error: function(xhr) {
            limpiarModalErrores();
            mostrarModalErrores(xhr);
            $('#loading-progress-bar').modal('hide');
        },
        xhr: function() {
            let xhr = $.ajaxSettings.xhr();
            if (xhr.upload) {
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        let progress = (e.loaded / e.total) * 100;
                        progressBar.css('width', progress + '%').attr('aria-valuenow', progress);
                        progressText.text(formatBytes(e.loaded) + ' de ' + formatBytes(e.total) + ' (' + Math.round(progress) + '%)');
                    }
                }, false);
            }
            return xhr;
        },
        beforeSend: function() {
            $('#loading-progress-bar').modal('show');
        },
        complete: function() {
            $('#loading-progress-bar').modal('hide');
        }
    });
}

function download(path, name, accountId) {

    path=cleanPath(path);

    let account = getAccount(accountId);

    $.ajax({
        url: account.controller + "/drive/download",
        method: 'GET',
        xhrFields: {
            responseType: 'blob'
        },
        data: {
            path: path,
            name: name,
            accountId: accountId
        },
        beforeSend: function() {
            $('#loading-modal').modal('show');
        },
        success: function (data) {
            var blob = new Blob([data]);
            var link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = name;
            link.click();
        },
        error: function (xhr, status, error) {
            console.error(error);
            limpiarModalErrores();
            mostrarModalErrores(xhr);
        },
        complete: function () {
            $('#loading-modal').modal('hide');
        }
    });
}


function logout(accountId)
{
    let account = getAccount(accountId)

    $.ajax({
        url: account.controller+'/logout',
        method: 'GET',
        data: {
            accountId: accountId
        },
        beforeSend: function() {
            $('#loading-modal').modal('show');
        },
        success: function () {
            deleteSessionAccount(accountId);
            location.reload();
        },
        error: function (xhr) {
            limpiarModalErrores();
            mostrarModalErrores(xhr);
        },
        complete: function() {
            $('#loading-modal').modal('hide');
        },
    });
}

function back(account)
{
    if(account.pathActual!== '')
    {
        if(account.parent === '')
        {
            recargasCuentas();
        } else
        {
            loadData(account.accountId,account.parent);
        }
    }
}

let lastMetadataArchive = null;
function getArchiveMetadata(accountId, path) {
    return new Promise(function(resolve) {
        path = cleanPath(path);
        let account = getAccount(accountId);

        $.ajax({
            url: account.controller + '/drive/getArchive',
            method: 'GET',
            data: {
                path: path,
                accountId: accountId,
            },
            beforeSend: function() {
                $('#loading-modal').modal('show');
            },
            success: function(data) {
                $('#loading-modal').modal('hide');
                lastMetadataArchive=data;
                resolve(data);
            },
            error: function(xhr, status, error) {
                $('#loading-modal').modal('hide');
                console.error(error);
                limpiarModalErrores();
                mostrarModalErrores(xhr);
            },
        });
    });
}

function guardarMetadata(path, accountId)
{
    path=cleanPath(path);

    let account = getAccount(accountId);
    let formData = extraerMetadatosModal();
    $.ajax({
        url: account.controller+'/drive/editMetadata',
        method: 'PATCH',
        data: {
            path: path,
            accountId: accountId,
            metadata: formData,
        },
        beforeSend: function() {
            $('#loading-modal').modal('show');
        },
        success: function (data) {
            console.log(data);
            limpiarModalSuccess();
            mostrarModalSuccess(data);
        },
        error: function (xhr, status, error) {
            console.error(error);
            limpiarModalErrores();
            mostrarModalErrores(xhr);
            lastMetadataArchive = null;
        },
        complete: function() {
            $('#loading-modal').modal('show');
        },
    });
}


function buscadorMetadatos()
{
    let formData = extraerBuscarMetadatosModal();

    $.ajax({
        url: 'searchMetadata',
        method: 'GET',
        data: {
            filters: formData,
        },
        beforeSend: function() {
            $('#loading-modal').modal('show');
        },
        success: function (data) {
            if(data.length>0)
            {
                $.each(data, function(index, file) {
                    let account=getAccount(file.accountId)
                    let isFirstIteration = index === 0;
                    if (isFirstIteration) {
                        refrescarTabla([file], account, true);
                    } else {
                        refrescarTabla([file], account, false);
                    }
                })
            } else
            {
                let tabla=$('#explorer');
                tabla.DataTable().clear().draw();
                limpiarModalErrores();
                mostrarModalErrores('No se han encontrado resultados.');
            }

            activarBtnBuscarMetadata(); //Se desactivan los botnoes subir y crear si la tabla se crea y hay ficheors para mostrar

        },
        error: function (xhr,status,error) {
            let tabla=$('#explorer');
            tabla.DataTable().clear().draw();
            console.error(error);
            limpiarModalErrores();
            mostrarModalErrores(xhr);
        }, complete: function ()
        {
            $('#loading-modal').modal('hide');
        }
    });
}


function copy(sourcePath,sourceAccountId,destinationAccountId)
{
    sourcePath=cleanPath(sourcePath);

    let account1 = getAccount(sourceAccountId);
    let account2= getAccount(destinationAccountId);

    $.ajax({
        url: account1.controller+'/copy',
        method: 'POST',
        data: {
            sourcePath: sourcePath,
            destinationPath: account2.pathActual,
            accountId1: sourceAccountId,
            accountId2: destinationAccountId,
            destinationCloud: account2.controller
        },
        beforeSend: function() {
            $('#loading-modal').modal('show');
        },
        success: function (data) {
            desactivarBtnBuscarMetadata();
            //let tabla=$('#explorer');
            //tabla.DataTable.off('click', 'td:nth-child(2) '); //Evento de buscarMetadatos

            if(account2.pathActual===account2.root)
            {
                recargasCuentas();
            } else
            {
                cargarDatos(account2, account2.pathActual,data);
            }
        },
        error: function (xhr, status, error) {
            console.error(error);
            limpiarModalErrores();
            mostrarModalErrores(xhr);
        },complete: function () {
            $('#loading-modal').modal('hide');
        }
    });
}

function move(sourcePath,sourceAccountId,destinationAccountId)
{
    sourcePath=cleanPath(sourcePath);

    let account1 = getAccount(sourceAccountId);
    let account2= getAccount(destinationAccountId);

    $.ajax({
        url: account1.controller+'/move',
        method: 'PUT',
        data: {
            sourcePath: sourcePath,
            destinationPath: account2.pathActual,
            accountId1: sourceAccountId,
            accountId2: destinationAccountId,
            destinationCloud: account2.controller
        },
        beforeSend: function() {
            $('#loading-modal').modal('show');
        },
        success: function (data) {
            desactivarBtnBuscarMetadata();
            //let tabla=$('#explorer');
            //tabla.DataTable.off('click', 'td:nth-child(2) '); //Evento de buscarMetadatos

            if(account2.pathActual===account2.root)
            {
                recargasCuentas();
            } else
            {
                cargarDatos(account2, account2.pathActual,data);
            }
        },
        error: function (xhr, status, error) {
            console.error(error);
            limpiarModalErrores();
            mostrarModalErrores(xhr);
        },complete: function () {
            $('#loading-modal').modal('hide');
        }
    });
}

function cargarDatos(account,path,data)
{
    path=cleanPath(path)
    if(account.controller === 'owncloud' )
    {
        data=cleanDataPaths(data);
    }
    account.pathActual=path;
    account.parent=dirname(path);

    setAccount(account);

    manejarActualizacionTabla(data,account);

    $("#ruta-p-explorer").html(path);
}

