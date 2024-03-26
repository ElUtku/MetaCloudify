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
        elemento.path = elemento.path.replace(/remote\.php\/dav\/files\/\w+\//g, '');
        return elemento;
    });
}

function editarModalMetadata(path, accountId) {
    let metadata = getArchiveMetadata(accountId, path);

    let modalMetadatos=$('#modalMetadatos');
    let contenidoMetadatos=$('#modalMetadatos .modal-body');

    modalMetadatos.data('path', path);
    modalMetadatos.data('accountId', accountId);

    // Limpia el contenido existente en el modal body
    contenidoMetadatos.empty();

    // Campo select para 'visibility'
    let labelSelect=$('<div>\
                            <label for="visibility" class="form-label">Visibility</label>\
                       </div>');
    let visibilitySelect = $('<select class="form-select mb-3"" id="visibility" name="visibility">\
                                <option value="private">Private</option>\
                                <option value="public">Public</option>\
                              </select>');
    visibilitySelect.val(metadata.visibility); //Se añade el valor actual
    contenidoMetadatos.append(labelSelect); //Se añaden al body
    contenidoMetadatos.append(visibilitySelect);

    // Campo input para 'author'
    let labelInput=$('<div>\
                            <label for="author" class="form-label">Author</label>\
                      </div>');
    let authorInput = $('<input type="text" class="form-control mb-3" id="author" name="author">');
    authorInput.val(metadata.extra_metadata.author); //Se añade el valor actual
    contenidoMetadatos.append(labelInput); //Se añaden al body
    contenidoMetadatos.append(authorInput);

    // Itera sobre los campos de 'extra' en 'extra_metadata' y crea inputs para cada uno
    if(metadata.extra_metadata.extra &&
        (metadata.extra_metadata.extra!=='null' || metadata.extra_metadata.extra!=='undefined'))
    {
        metadata.extra_metadata.extra=JSON.parse(metadata.extra_metadata.extra);
        for (let key in metadata.extra_metadata.extra) {
            if (metadata.extra_metadata.extra.hasOwnProperty(key)) {
                let labelExtra=$('<div>\
                                        <label for="' + key + '" class="form-label">' + key + '</label>\
                                  </div>');
                let input = $('<input type="text" class="form-control mb-3" id="' + key + '" name="' + key + '">');
                input.val(metadata.extra_metadata.extra[key]);//Se añade el valor actual
                contenidoMetadatos.append(labelExtra); //Se añaden al body
                contenidoMetadatos.append(input);
            }
        }
    }

    // Muestra el modal de metadatos
    modalMetadatos.modal('show');
}

// Función para agregar un nuevo campo al modal de metadatos
function agregarNuevoCampo() {
    let contenidoMetadatos=$('#modalMetadatos .modal-body');


    // Obtener el nombre y el valor del nuevo campo del modal
    let nombreCampo = $('#nuevoCampoNombre').val();
    let valorCampo = $('#nuevoCampoValor').val();

    // Crear el campo input para el nuevo campo y agregarlo al modal de metadatos
    let labelInput=$('<div>\
                          <label for="' + nombreCampo + '" class="form-label">' + nombreCampo + '</label>\
                      </div>');
    let nuevoCampoInput = $('<input type="text" class="form-control mb-3" id="' + nombreCampo + '" name="' + nombreCampo + '">');
    nuevoCampoInput.val(valorCampo);

    contenidoMetadatos.append(labelInput)
    contenidoMetadatos.append(nuevoCampoInput);

    // Cerrar el modal de nuevo campo
    $('#modalNuevoCampo').modal('hide');

    // Limpiar los campos del modal de nuevo campo para la próxima vez
    $('#nuevoCampoNombre').val('');
    $('#nuevoCampoValor').val('');
}


