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
    $('#loading-modal').modal('show');

    path = (typeof path !== 'undefined') ? path : '';
    path=path.replace(/\//g, '\\');

    let account = getAccount(accountId);

    $.data($('#explorer','account',account));

    $.ajax({
        url: account.controller+'/drive',
        method: 'GET',
        data: { path: path,
                accountId: accountId
        },
        dataType: 'json',
        success: function (data) {
            cargarDatos(account,path,data);
        },
        error: function (xhr, status, error) {
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
    $('#loading-modal').modal('show');

    let account = getAccount(accountId);
    $.ajax({
        url: account.controller+"/drive/createDir",
        method: 'POST',
        data: {
            path: account.pathActual,
            name: name,
            accountId: account.accountId
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
        },
        complete: function () {
            $('#loading-modal').modal('hide');
        }
    });
}

function createFile(name,accountId)
{
    $('#loading-modal').modal('show');

    let account = getAccount(accountId);
    $.ajax({
        url: account.controller+"/drive/createFile",
        method: 'POST',
        data: {
            path: account.pathActual,
            name: name,
            accountId: account.accountId
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
        },
        complete: function () {
            $('#loading-modal').modal('hide');
        }
    });
}

function dlt(path,accountId)
{
    $('#loading-modal').modal('show');

    let account = getAccount(accountId);
    $.ajax({
        url: account.controller+"/drive/delete",
        method: 'DELETE',
        data: {
            path: dirname(path),
            name: basename(path),
            accountId: accountId
        },
        success: function (data) {
            if(account.pathActual===account.root)
            {
                recargasCuentas();
            } else
            {
                cargarDatos(account,dirname(path),data);
            }
        },
        error: function (xhr, status, error) {
            console.error(error);
        },
        complete: function () {
            $('#loading-modal').modal('hide');
        }
    });
}

function upload(accountId) {
    let account = getAccount(accountId);

    let fileupload = $('#fileupload-explorer');
    let progressBar = $('#progress-bar');
    let progressText = $('#progress-text');
    let uploadedSize = 0;
    let totalSize = 0;

    fileupload.fileupload({
        url: account.controller + '/drive/upload',
        dataType: 'json',
        method: 'POST',
        formData: {
            path: account.pathActual,
            accountId: accountId
        },
        success: function (data) {
            if(account.pathActual===account.root)
            {
                recargasCuentas();
            } else
            {
                cargarDatos(account,account.pathActual,data);
            }
            $('#loading-progress-bar').modal('hide');
        },
        error: function (e, data) {
            console.log('Error al cargar el archivo:', data.errorThrown);
            $('#loading-progress-bar').modal('hide');
        },
        progressall: function (e, data) { //Este evento procesa muestra el modal y el porcentajee
            $('#loading-progress-bar').modal('show');

            totalSize = data.total;

// Se calcula el porcentaje
            let progress = parseInt(data.loaded / data.total * 100, 10);
// Se actualiza la barra de progreso
            progressBar.css('width', progress + '%').attr('aria-valuenow', progress);
// Se actualiza el texto de progreso
            progressText.text(formatBytes(uploadedSize) + ' de ' + formatBytes(totalSize) + ' (' + progress + '%)');
        },
        progress: function (e, data) { //Este evento procesa los mb/s que se estan subiendo
            uploadedSize = data.loaded;

            let progress = parseInt(data.loaded / data.total * 100, 10);

            progressBar.css('width', progress + '%').attr('aria-valuenow', progress);

            progressText.text(formatBytes(uploadedSize) + ' de ' + formatBytes(totalSize) + ' (' + progress + '%)');
        }
    });

    // Inicia la carga del archivo
    fileupload.fileupload('send', {
        files: $('#formFile-explorer')[0].files
    });
}



function download(path, name, accountId) {
    $('#loading-modal').modal('show');

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
        success: function (data) {
            var blob = new Blob([data]);
            var link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = name;
            link.click();
        },
        error: function (xhr, status, error) {
            console.error(error);
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
        success: function () {
            sessionStorage.removeItem('storedAccounts');
            location.reload();
        },
        error: function (xhr, status, error) {
            console.error(error);
        }
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

function getArchiveMetadata(accountId, path) {
    let account = getAccount(accountId);
    let metadata = null;

    $.ajax({
        url: account.controller + '/drive/getArchive',
        method: 'GET',
        async: false, // Debe ser sincrónico para que el modal pueda leer los datos devueltos
        data: {
            path: path,
            accountId: accountId,
        },
        success: function (data) {
            metadata = data;
        },
        error: function (xhr, status, error) {
            console.error(error);
            metadata = null;
        }
    });

    return metadata;
}

function guardarMetadata(path, accountId)
{
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
        success: function (data) {
            console.log(data);
            mostrarModalSuccess(data);
        },
        error: function (xhr, status, error) {
            console.error(error);
        }
    });
}

function copy(sourcePath,sourceAccountId,destinationAccountId)
{
    let account1 = getAccount(sourceAccountId);
    let account2= getAccount(destinationAccountId);

    $('#loading-modal').modal('show');

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
        success: function () {
            if(account2.pathActual===account2.root)
            {
                recargasCuentas();
            } else
            {
                loadData(account2.accountId, account2.pathActual);
            }
        },
        error: function (xhr, status, error) {
            console.error(error);
        },complete: function () {
            $('#loading-modal').modal('hide');
        }
    });
}

function move(sourcePath,sourceAccountId,destinationAccountId)
{
    let account1 = getAccount(sourceAccountId);
    let account2= getAccount(destinationAccountId);

    $('#loading-modal').modal('show');

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
        success: function () {
            dlt(sourcePath,sourceAccountId);
            if(account2.pathActual===account2.root)
            {
                recargasCuentas();
            } else
            {
                loadData(account2.accountId, account2.pathActual);
            }
        },
        error: function (xhr, status, error) {
            console.error(error);
        },complete: function () {
            $('#loading-modal').modal('hide');
        }
    });
}

function cargarDatos(account,path,data)
{
    if(account.controller.indexOf("owncloud")!==-1 )
    {
        data=cleanOwncloudData(data);
    }
    account.pathActual=path;
    account.parent=dirname(path);

    setAccount(account);

    manejarActualizacionTabla(data,account);

    $("#ruta-p-explorer").html('Ruta: '+path);
}

