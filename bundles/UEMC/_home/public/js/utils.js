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

//Se debe eliminar remote.php/dav/files/{user} para poder navegar por los directorios
function cleanOwncloudData(data)
{
    return data.map(function (elemento) {
        elemento.path = elemento.path.replace(/.*\/remote\.php\/dav\/files\/\w+\//g, '');
        return elemento;
    });
}

function dirname(path)// test/a/b/c -> [test],[a],[b],[c] -> [test],[a],[b] -> test/a/b
{
    let parent;
    try {
        path = path.replace(/\//g, '\\');
        parent = path.split('\\');
        parent.pop();
        parent = parent.join('\\');
    } catch //Si hay error es porque nos encotramos en la raiz
    {
        parent = '';
    }
    return parent;
}

function basename(path)
{
    return path.split(/[\\/]/).pop();
}
// Función para formatear bytes a MB
function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const dm = 2;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}


let ultimaAccion = '';
function manejarActualizacionTabla(data, account) {
    if (account.pathActual === '') {
        if (ultimaAccion === 'volverRaiz') {
            refrescarTabla(data, account, true);
        } else if (ultimaAccion === 'añadeCuentaRaiz'){
            // Actualiza el contenido de la tabla sin borrarla
            refrescarTabla(data, account, false);
        } else
        {
            // Borra y refresca la tabla completa
            refrescarTabla(data, account, true);
        }
    } else {
        // Refresca la tabla completa
        refrescarTabla(data, account, true);
    }

    // Actualiza la última acción del usuario
    if (account.pathActual === '' && ultimaAccion === 'volverRaiz') {
        ultimaAccion = 'añadeCuentaRaiz'
    }else if(ultimaAccion === 'añadeCuentaRaiz')
    {
        ultimaAccion = 'añadeCuentaRaiz'
    }else if(account.pathActual === ''){
        ultimaAccion = 'volverRaiz';
    } else {
        ultimaAccion = 'navegar';
    }
}
