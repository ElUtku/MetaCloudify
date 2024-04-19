
$(document).ready(function() {

// Esconder y borrar modal de metadatos en cada interacción
    $('#modalEditarMetadatos').on('hidden.bs.modal', function () {
        $('.mb-3.dynamic').remove(); // Eliminar todos los elementos con la clase 'mb-3' y 'dynamic'
    });

});

function editarModalMetadata() {

    let modalVerMetadatos = $('#modalVerMetadatos');
    let modalEditarMetadatos = $('#modalEditarMetadatos');
    let contenidoModalEditarMetadatos = $('#modalEditarMetadatos .modal-body');

    let path = modalVerMetadatos.data('path');
    let accountId=modalVerMetadatos.data('accountId');
    modalEditarMetadatos.data('path', path);
    modalEditarMetadatos.data('accountId', accountId);

    let metadata = lastMetadataArchive??getArchiveMetadata(accountId, path);

// Limpia el contenido existente en el modal body
    contenidoModalEditarMetadatos.empty();

// Campo select para 'visibility'
    let visibilityFormGroup = $('<div class="form-group"></div>');
    let labelSelect = $('<label for="visibility" class="form-label">Visibility</label>');
    let visibilitySelect = $('<select class="form-select mb-3" id="visibility" name="visibility">\
                                <option value="private">Private</option>\
                                <option value="public">Public</option>\
                              </select>');
    visibilitySelect.val(metadata.visibility); //Se añade el valor actual
    visibilityFormGroup.append(labelSelect);
    visibilityFormGroup.append(visibilitySelect);
    contenidoModalEditarMetadatos.append(visibilityFormGroup);

// Campo input para 'author'
    let authorFormGroup = $('<div class="form-group"></div>');
    let labelInput = $('<label for="author" class="form-label">Author</label>');
    let authorInput = $('<input type="text" class="form-control mb-3" id="author" name="author">');
    authorInput.val(metadata.extra_metadata.author); //Se añade el valor actual
    authorFormGroup.append(labelInput);
    authorFormGroup.append(authorInput);
    contenidoModalEditarMetadatos.append(authorFormGroup);

// Itera sobre los campos de 'extra' en 'extra_metadata' y crea inputs para cada uno
    if (metadata.extra_metadata.extra &&
        metadata.extra_metadata.extra !== 'null' &&
        metadata.extra_metadata.extra !== 'undefined') {
        metadata.extra_metadata.extra = JSON.parse(metadata.extra_metadata.extra);
        for (let key in metadata.extra_metadata.extra) {
            if (metadata.extra_metadata.extra.hasOwnProperty(key)) {
                let extraFormGroup = $('<div class="form-group"></div>');
                let headerFormGroup = $('<div class="d-flex align-items-center justify-content-between"></div>')
                let labelExtra = $('<label for="' + key + '" class="form-label">' + key + '</label>');
                let input = $('<input type="text" class="form-control mb-3" id="' + key + '" name="' + key + '">');
                input.val(metadata.extra_metadata.extra[key]);//Se añade el valor actual
                headerFormGroup.append(labelExtra);
                headerFormGroup.append(crearBotonEliminar(input));
                extraFormGroup.append(headerFormGroup);
                extraFormGroup.append(input);
                contenidoModalEditarMetadatos.append(extraFormGroup);
            }
        }
    }

// Muestra el modal de metadatos
    modalEditarMetadatos.modal('show');
}


function agregarNuevoCampo() {

    let contenidoModalEditarMetadatos=$('#modalEditarMetadatos .modal-body');

    let nuevoCampoNombre=$('#nuevoCampoNombre');
    let nuevoValorCampo=$('#nuevoValorCampo');

// Se obtiene el nombre y el valor del nuevo campo del modal
    let nombreCampo = sanitizeText(nuevoCampoNombre.val());
    let valorCampo = sanitizeText(nuevoValorCampo.val());

// Se crea el campo input para el nuevo campo y agregarlo al modal de metadatos
    let newFormGroup = $('<div class="form-group"></div>');
    let headerFormGroup = $('<div class="d-flex align-items-center justify-content-between"></div>')
    let labelInput=$('<label for="' + nombreCampo + '" class="form-label">' + nombreCampo + '</label>');
    let nuevoCampoInput = $('<input type="text" class="form-control mb-3" id="' + nombreCampo + '" name="' + nombreCampo + '">');
    nuevoCampoInput.val(valorCampo);

    headerFormGroup.append(labelInput)
    headerFormGroup.append(crearBotonEliminar(nuevoCampoInput));

    newFormGroup.append(headerFormGroup);
    newFormGroup.append(nuevoCampoInput);

    contenidoModalEditarMetadatos.append(newFormGroup);

// Cerrar el modal de nuevo campo
    $('#modalNuevoCampo').modal('hide');

// Se limpian los campos del modal de nuevo campo para la próxima vez
    nuevoCampoNombre.val('');
    nuevoValorCampo.val('');
}

// Función para crear un botón de eliminar
function crearBotonEliminar(input) {
    let botonEliminar = $('<button type="button" class="btn btn-danger btn-sm p-1 border-0 mb-2"><i class="bi bi-trash"></i></button>');
    botonEliminar.click(function() {
        input.closest('.form-group').remove();
    });
    return botonEliminar;
}

function extraerMetadatosModal(){

    let formData = {};

// Bandera para verificar si se han encontrado campos adicionales aparte de 'author' y 'visibility'
    let extraFieldsFound = false;

// Iterar sobre los elementos del formulario en el modal (se seleccionan los inputs y el select)
    $('#modalEditarMetadatos .modal-body input, #modalEditarMetadatos .modal-body select').each(function() {
        let fieldName = $(this).attr('name');
        let fieldValue = sanitizeText($(this).val()??'');

        if (fieldName === 'author' || fieldName === 'visibility') {
            formData[fieldName] = fieldValue;
        } else {
            // Se agrega el campo al objeto extra con su respectivo nombre y valor
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
    return formData;
}

function verModalMetadata(path, accountId) {
    let modalVerMetadatos = $('#modalVerMetadatos');
    modalVerMetadatos.data('path', path);
    modalVerMetadatos.data('accountId', accountId);

    let contenidoModalVerMetadatos = modalVerMetadatos.find('.modal-body');
    let metadata = getArchiveMetadata(accountId, path);

    let author = metadata.extra_metadata.author ?? '-';
    let arrayExtra = [
        `<li><b>Author</b>: ${author}</li>`
    ];


    let extraObjeto = {};
    if (metadata.extra_metadata.extra && metadata.extra_metadata.extra !== "null") {
        extraObjeto = JSON.parse(metadata.extra_metadata.extra);
    }

    Object.keys(extraObjeto).forEach(key => {
        let value = extraObjeto[key] ?? '-';
        arrayExtra.push(`<li><b>${key}</b>: ${value}</li>`);
    });

    contenidoModalVerMetadatos.html(
        `<ul>${arrayExtra.join('')}</ul>`
    );
    modalVerMetadatos.modal('show');
}

function optionsSelectAccounId()
{
    let accounts = getAccounts();
    let select = $('#accountSelect');
    select.empty(); // Limpiar opciones anteriores


    for (var accountId in accounts) {
        if (accounts.hasOwnProperty(accountId)) {
            var account = accounts[accountId];
            select.append('<option value="' + account.accountId + '">' + account.user + ' - '+  account.controller +'</option>');
        }
    }


    // Mostrar el modal y devolver una promesa
    return new Promise(function(resolve, reject) {
        $('#accountModal').modal('show');

        // Resolver la promesa cuando el usuario confirme la selección
        $('#confirmAccountBtn').on('click', function() {
            $('#accountModal').modal('hide');
            resolve(selectAccountId());
        });
    });
}

function selectAccountId() {
    return sanitizeText($('#accountSelect').val());
}

function mostrarModalErrores(xhr)
{
    let errorMessage;
    if (xhr.responseJSON) {
        errorMessage = xhr.responseJSON;
    } else
    {
        errorMessage = xhr;
    }
    let errorElement = $('<li>').text(errorMessage);
    errorElement.appendTo('#errorContent');
    $('#errorModal').modal('show').modal('handleUpdate');
}

function limpiarModalErrores()
{
    $('#errorContent').html('');
}

function mostrarModalSuccess(data)
{
    var successElement = $('<li>').text(data);
    successElement.appendTo('#successContent');
    $('#successModal').modal('show');
}
function limpiarModalSuccess()
{
    $('#successContent').html('');
}
