$(document).ready(function() {
    $("#newName, #nuevoValorCampo, #nuevoCampoNombre").on('input', sanitizeInput);

    $('#cambiarTema').click(function(){
        let htmlElement = $('html');
        let buttonIcon = $(this).find('i');
        let currentTheme = htmlElement.attr('data-bs-theme');
        let logoHorizontalBlanco = $('#logoHorizontalBlanco');
        let logoNubeBlanco = $('#logoNubeBlanco');

        if(currentTheme === 'dark'){
            htmlElement.attr('data-bs-theme', 'light');
            buttonIcon.removeClass('bi-sun').addClass('bi-moon-fill');
            logoHorizontalBlanco.addClass('filter-invert');
            logoNubeBlanco.addClass('filter-invert');
        } else {
            htmlElement.attr('data-bs-theme', 'dark');
            buttonIcon.removeClass('bi-moon-fill').addClass('bi-sun');
            logoHorizontalBlanco.removeClass('filter-invert');
            logoNubeBlanco.removeClass('filter-invert');
        }
    });
});

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
        elemento.path = cleanOwncloudPath(elemento.path);
        return elemento;
    });
}

function cleanOwncloudPath(path)
{
    //Limpiamos la ruta si es de owncloud
    path = path.substring(path.indexOf("remote.php/dav/files/") + "remote.php/dav/files/".length);

    path=path.replace(/^.*?\//, "");
    return path;
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

function entradaSanitizada(text) {

// Se eliminan caracteres especiales excepto letras, números, guion bajo, punto, coma y guion
    var sanitizedText = text.replace(/[^a-zA-Z0-9_.,\-]/g, '');

// Si no se condigue escapar se codifican caracteres especiales para evitar XSS
    sanitizedText = encodeURIComponent(sanitizedText);

//Se valida la longitud máxima estableicdad por la BD
    if (sanitizedText.length > 255) {
        sanitizedText = sanitizedText.slice(0, 255); // Cortar el texto si excede la longitud máxima
    }

    return sanitizedText;
}

function sanitizeInput(event) {
    let input = $(this);
    let text = input.val();

// Se elimina caracteres no permitidos
    let sanitizedText = text.replace(/[^a-zA-Z0-9_.,\-]/g, '');

// Se actualiza el valor del input con el texto sanitizado
    input.val(sanitizedText);

    /*limpiarModalErrores();
    mostrarModalErrores("No se permite introducir caracteres especiales");*/
}