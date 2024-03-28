function Account(accountId,controller,user,root,pathActual,parent)
{
    this.accountId=accountId;
    this.controller=controller;
    this.user=user;
    this.root=root;
    this.pathActual=pathActual ?? undefined;
    this.parent=parent ?? undefined;
}

function loadData(accountId,path,explorer) {
    $('#loading-modal').modal('show');

    path = (typeof path !== 'undefined') ? path : '';
    path=path.replace(/\//g, '\\');

    let account = getAccount(accountId);

    $.data($('#'+explorer,'account',account));

    $.ajax({
        url: account.controller+'/drive',
        method: 'GET',
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
            account.parent=dirname(path);

            setAccount(account);

            refrescarTabla(data,explorer,account);
            $("#ruta-p-"+explorer).html('Ruta: '+path);
        },
        error: function (xhr, status, error) {
            console.error(error);
        },
        complete: function () {
            $('#loading-modal').modal('hide');
        }
    });
}

function createDir(name,accountId,explorer)
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
        success: function () {
            // Actualiza dinámicamente el contenido en la página
            loadData(account.accountId,account.pathActual,explorer);
        },
        error: function (xhr, status, error) {
            console.error(error);
        },
        complete: function () {
            $('#loading-modal').modal('hide');
        }
    });
}

function createFile(name,accountId,explorer)
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
        success: function () {
            // Actualiza dinámicamente el contenido en la página
            loadData(account.accountId,account.pathActual,explorer);
        },
        error: function (xhr, status, error) {
            console.error(error);
        },
        complete: function () {
            $('#loading-modal').modal('hide');
        }
    });
}

function dlt(path,accountId,explorer)
{
    $('#loading-modal').modal('show');

    let account = getAccount(accountId);
    $.ajax({
        url: account.controller+"/drive/delete",
        method: 'DELETE',
        data: {
            path: path,
            accountId: accountId
        },
        success: function () {
            loadData(accountId,account.pathActual,explorer);
        },
        error: function (xhr, status, error) {
            console.error(error);
        },
        complete: function () {
            $('#loading-modal').modal('hide');
        }
    });
}

function upload(accountId, explorer) {
    let account = getAccount(accountId);

    let fileupload = $('#fileupload-' + explorer);
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
        done: function () {
            $('#loading-progress-bar').modal('hide');
            loadData(accountId, account.pathActual, explorer);
        },
        fail: function (e, data) {
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
        files: $('#formFile-' + explorer)[0].files
    });
}



function download(path,name,accountId)
{
    let account = getAccount(accountId);

    $.ajax({
        url: account.controller+"/drive/download",
        method: 'GET',
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

function back(explorer, account)
{
    loadData(account.accountId,account.parent,explorer);
}

function getArchiveMetadata(accountId, path) {
    let account = getAccount(accountId);
    let metadata = null;

    $('#loading-modal').modal('show');

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
        },
        complete: function () {
            $('#loading-modal').modal('hide');
        }
    });

    return metadata;
}

function guardarMetadata(path, accountId)
{
    $('#loading-modal').modal('show');

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
        success: function () {
                console.log('ok');

        },
        error: function (xhr, status, error) {
            console.error(error);
        },
        complete: function () {
            $('#loading-modal').modal('hide');
        }
    });
}

