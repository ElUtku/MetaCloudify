
$(document).ready(function() {

// Esconder y borrar modal de metadatos en cada interacción
    $('#modalMetadatos').on('hidden.bs.modal', function () {
        $('.mb-3.dynamic').remove(); // Eliminar todos los elementos con la clase 'mb-3' y 'dynamic'
    });

});

function editarModalMetadata(path, accountId) {
    let metadata = getArchiveMetadata(accountId, path);

    let modalMetadatos = $('#modalMetadatos');
    let contenidoModalMetadatos = $('#modalMetadatos .modal-body');

    modalMetadatos.data('path', path);
    modalMetadatos.data('accountId', accountId);

// Limpia el contenido existente en el modal body
    contenidoModalMetadatos.empty();

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
    contenidoModalMetadatos.append(visibilityFormGroup);

// Campo input para 'author'
    let authorFormGroup = $('<div class="form-group"></div>');
    let labelInput = $('<label for="author" class="form-label">Author</label>');
    let authorInput = $('<input type="text" class="form-control mb-3" id="author" name="author">');
    authorInput.val(metadata.extra_metadata.author); //Se añade el valor actual
    authorFormGroup.append(labelInput);
    authorFormGroup.append(authorInput);
    contenidoModalMetadatos.append(authorFormGroup);

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
                extraFormGroup.append(input);
                contenidoModalMetadatos.append(headerFormGroup);
                contenidoModalMetadatos.append(extraFormGroup);
            }
        }
    }

// Muestra el modal de metadatos
    modalMetadatos.modal('show');
}


function agregarNuevoCampo() {

    let contenidoModalMetadatos=$('#modalMetadatos .modal-body');

// Se obtiene el nombre y el valor del nuevo campo del modal
    let nombreCampo = $('#nuevoCampoNombre').val();
    let valorCampo = $('#nuevoCampoValor').val();

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

    contenidoModalMetadatos.append(newFormGroup);

// Cerrar el modal de nuevo campo
    $('#modalNuevoCampo').modal('hide');

// Se limpian los campos del modal de nuevo campo para la próxima vez
    $('#nuevoCampoNombre').val('');
    $('#nuevoCampoValor').val('');
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
    $('#modalMetadatos .modal-body input, #modalMetadatos .modal-body select').each(function() {
        let fieldName = $(this).attr('name');
        let fieldValue = $(this).val();

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