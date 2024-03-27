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

    path = (typeof path !== 'undefined') ? path : '';
    path=path.replace(/\//g, '\\');

    let account = getAccount(accountId);

    $.data($('#'+explorer,'account',account));

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
            account.parent=dirname(path);

            setAccount(account);

            refrescarTabla(data,explorer,account);
            $("#ruta-p-"+explorer).html('Ruta: '+path);
        },
        error: function (xhr, status, error) {
            console.error(error);
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
            loadData(accountId,account.pathActual,explorer);
        },
        error: function (xhr, status, error) {
            console.error(error);
        }
    });
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
        fail: function (e, data) {
            console.log('Error al cargar el archivo:', data.errorThrown);
        }
    });

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

function guardarMetadata(path, accountId)
{
    let account = getAccount(accountId);
    let formData = extraerMetadatosModal();
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

