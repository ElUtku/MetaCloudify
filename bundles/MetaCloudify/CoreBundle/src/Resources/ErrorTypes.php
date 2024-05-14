<?php

namespace MetaCloudify\CoreBundle\Resources;

enum ErrorTypes: string
{
    case ERROR_INDETERMINADO  = 'Error indeterminado.';
    case ERROR_INICIO_SESION = 'Error al iniciar sesion.';
    case ERROR_STATE_OAUTH2 = 'Error en el state de oauth2 al iniciar sesion.';
    case ERROR_CONSTRUIR_OBJETO = 'Error al construir el objeto.';
    case ERROR_CONSTRUIR_FILESYSTEM= 'Error al construir el filesystem.';
    case ERROR_OBTENER_USUARIO= 'Error al obtener la inforamcion del usuario.';
    case DIRECTORIO_NO_EXISTE ='La ruta especificada no existe.';
    case ERROR_UPLOAD ='El arhivo no ha podido ser escrito en el servidor.';
    case BAD_CONTENT ='Error al leer el contenido del archivo.';
    case ERROR_LIST_CONTENT='Error al listar al recuperar los archivos.';
    case DIRECTORY_YA_EXISTE='El directorio especificado ya existe.';
    case FICHERO_YA_EXISTE='El archivo especificado ya existe.';
    case ERROR_CREAR_DIRECTORIO='Error al crear el directorio.';
    case ERROR_CREAR_FICHERO='Error al crear el fichero.';
    case ERROR_BORRAR='Error al borrar.';
    case ERROR_DESCARGA='Error al descargar.';
    case ERROR_LOGOUT='Error al cerrar la sesión.';
    case ERROR_SAVE_SESSION='Error al guardar la sesión.';
    case ERROR_CONTROLLER='Error al detectar el controlador.';
    case ERROR_LOG_ACCOUNT ='Error al guardar la cuenta en la base de datos.';
    case ERROR_GET_NATIVE_METADATA = 'Error al cargar los metadatos nativos.';
    case ERROR_LOG_METADATA = 'Error al guardar los metadatos en la base de datos';
    case NO_SUCH_FILE_OR_DIRECTORY ='La ruta no es un directorio ni un archivo.';
    case ERROR_DELETE_MULTIPLE_FILES = 'Imposible maborrar todos los ficheros que contien la carpeta en la BD.';
    case ERROR_GET_METADATA = 'Error al obtener los metadatos de la base de datos.';
    case ERROR_COPY = 'Error al copiar los archivos.';
    case ERROR_MOVE = 'Error al mover los archivos';
    case TOKEN_EXPIRED = 'El token de la sesión actual ha caducado';
    case URL_FAIL = 'La url no existe';
    case ERROR_CREDENTIALS = 'Las credenciales introducidas no son correctas.';
    case ARCHIVO_LIMITE_TAMANO = 'El tamaño del archivo es demasiado grande.';
    case BAD_JSON = 'Los datos proporcionados no tienen un formato correcto.';
    case NO_CUENTAS_SESION = 'No hay cuentas en la sesión.';

    /**
     * @return int
     */
    public function getErrorCode(): int
    {
        return match ($this) {
            ErrorTypes::ERROR_INDETERMINADO => 500,
            ErrorTypes::ERROR_INICIO_SESION => 401,
            ErrorTypes::ERROR_STATE_OAUTH2 => 401,
            ErrorTypes::ERROR_CONSTRUIR_OBJETO => 500,
            ErrorTypes::ERROR_CONSTRUIR_FILESYSTEM => 500,
            ErrorTypes::ERROR_OBTENER_USUARIO => 500,
            ErrorTypes::DIRECTORIO_NO_EXISTE => 404,
            ErrorTypes::ERROR_UPLOAD => 500,
            ErrorTypes::BAD_CONTENT => 400,
            ErrorTypes::ERROR_LIST_CONTENT => 500,
            ErrorTypes::DIRECTORY_YA_EXISTE => 409,
            ErrorTypes::FICHERO_YA_EXISTE => 409,
            ErrorTypes::ERROR_CREAR_DIRECTORIO => 500,
            ErrorTypes::ERROR_CREAR_FICHERO => 500,
            ErrorTypes::ERROR_BORRAR => 500,
            ErrorTypes::ERROR_DESCARGA => 500,
            ErrorTypes::ERROR_LOGOUT => 500,
            ErrorTypes::ERROR_SAVE_SESSION => 500,
            ErrorTypes::ERROR_CONTROLLER => 500,
            ErrorTypes::NO_SUCH_FILE_OR_DIRECTORY => 404,
            ErrorTypes::ERROR_LOG_ACCOUNT => 500,
            ErrorTypes::ERROR_GET_NATIVE_METADATA => 500,
            ErrorTypes::ERROR_LOG_METADATA => 500,
            ErrorTypes::ERROR_DELETE_MULTIPLE_FILES => 500,
            ErrorTypes::ERROR_GET_METADATA => 500,
            ErrorTypes::ERROR_COPY => 500,
            ErrorTypes::ERROR_MOVE => 500,
            ErrorTypes::TOKEN_EXPIRED => 401,
            ErrorTypes::URL_FAIL => 404,
            ErrorTypes::ERROR_CREDENTIALS => 401,
            ErrorTypes::ARCHIVO_LIMITE_TAMANO =>413,
            ErrorTypes::BAD_JSON => 400,
            ErrorTypes::NO_CUENTAS_SESION => 401
        };
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return match ($this) {
            ErrorTypes::ERROR_INDETERMINADO => 'Error indeterminado',
            ErrorTypes::ERROR_INICIO_SESION => 'Error al iniciar sesion',
            ErrorTypes::ERROR_STATE_OAUTH2 => 'Error en el state de oauth2 al iniciar sesion',
            ErrorTypes::ERROR_CONSTRUIR_OBJETO => 'Error al construir el objeto',
            ErrorTypes::ERROR_CONSTRUIR_FILESYSTEM => 'Error al construir el filesystem',
            ErrorTypes::ERROR_OBTENER_USUARIO => 'Error al obtener la inforamcion del usuario',
            ErrorTypes::DIRECTORIO_NO_EXISTE => 'La ruta especificada no existe',
            ErrorTypes::ERROR_UPLOAD => 'El arhivo no ha podido ser escrito en el servidor',
            ErrorTypes::BAD_CONTENT => 'Error al leer el contenido del archivo',
            ErrorTypes::ERROR_LIST_CONTENT => 'Error al listar al recuperar los archivos',
            ErrorTypes::DIRECTORY_YA_EXISTE => 'El directorio especificado ya existe',
            ErrorTypes::FICHERO_YA_EXISTE => 'El archivo especificado ya existe',
            ErrorTypes::ERROR_CREAR_DIRECTORIO => 'Error al crear el directorio',
            ErrorTypes::ERROR_CREAR_FICHERO => 'Error al crear el fichero',
            ErrorTypes::ERROR_BORRAR => 'Error al borrar',
            ErrorTypes::ERROR_DESCARGA => 'Error al descargar',
            ErrorTypes::ERROR_LOGOUT => 'Error al cerrar la sesion',
            ErrorTypes::ERROR_SAVE_SESSION => 'Error al guardar la sesion',
            ErrorTypes::ERROR_CONTROLLER => 'Error al detectar el controlador',
            ErrorTypes::ERROR_LOG_ACCOUNT => 'Error al guardar la cuenta en base de datos',
            ErrorTypes::ERROR_GET_NATIVE_METADATA => 'Error al cargar los metadatos nativos',
            ErrorTypes::ERROR_LOG_METADATA => 'Error al guardar los metadatos en la base de datos',
            ErrorTypes::NO_SUCH_FILE_OR_DIRECTORY => 'La ruta no es un directorio ni un archivo',
            ErrorTypes::ERROR_DELETE_MULTIPLE_FILES => 'Imposible maborrar todos los ficheros que contien la carpeta en la BD',
            ErrorTypes::ERROR_GET_METADATA => 'Error al obtener los metadatos de la base de datos',
            ErrorTypes::ERROR_COPY => 'Error al copiar los archivos.',
            ErrorTypes::ERROR_MOVE => 'Error al mover los archivos.',
            ErrorTypes::TOKEN_EXPIRED => 'El token de la sesión actual ha caducado.',
            ErrorTypes::URL_FAIL => 'La url no existe.',
            ErrorTypes::ERROR_CREDENTIALS => 'Las credenciales introducidas no son correctas.',
            ErrorTypes::ARCHIVO_LIMITE_TAMANO => 'El tamaño del archivo es demasiado grande',
            ErrorTypes::BAD_JSON => 'Los datos proporcionados no tienen un formato correcto',
            ErrorTypes::NO_CUENTAS_SESION => 'No hay cuentas en la sesion'

        };
    }
}
