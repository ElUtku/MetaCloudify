<?php

namespace UEMC\Core\Resources;

enum ErrorTypes: string
{
    case ERROR_INDETERMINADO  = 'Error indeterminado.';
    case ERROR_INICIO_SESION = 'Error al iniciar sesion.';
    case ERROR_STATE_OAUTH2 = 'Error en el state de oauth2 al iniciar sesion.';
    case ERROR_CONSTRUIR_OBJETO = 'Error al construir el objeto.';
    case ERROR_CONSTRUIR_FILESYSTEM= 'Error al construir el filesystem.';
    case ERROR_OBTENER_USUARIO= 'Error al obtener la inforamcion del usuario.';
    case DIRECTORIO_NO_EXISTE ='El directorio especificado no existe.';
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
    case ERROR_ADD_ACCOUNT ='Error al guardar la cuenta en la base de datos.';
    /**
     * @return int
     */
    public function getErrorCode(): int
    {
        return match ($this) {
            ErrorTypes::ERROR_INDETERMINADO => 600,
            ErrorTypes::ERROR_INICIO_SESION => 601,
            ErrorTypes::ERROR_STATE_OAUTH2 => 602,
            ErrorTypes::ERROR_CONSTRUIR_OBJETO => 603,
            ErrorTypes::ERROR_CONSTRUIR_FILESYSTEM => 604,
            ErrorTypes::ERROR_OBTENER_USUARIO => 605,
            ErrorTypes::DIRECTORIO_NO_EXISTE => 606,
            ErrorTypes::ERROR_UPLOAD => 607,
            ErrorTypes::BAD_CONTENT => 608,
            ErrorTypes::ERROR_LIST_CONTENT => 609,
            ErrorTypes::DIRECTORY_YA_EXISTE => 610,
            ErrorTypes::FICHERO_YA_EXISTE => 611,
            ErrorTypes::ERROR_CREAR_DIRECTORIO => 612,
            ErrorTypes::ERROR_CREAR_FICHERO => 613,
            ErrorTypes::ERROR_BORRAR => 614,
            ErrorTypes::ERROR_DESCARGA => 615,
            ErrorTypes::ERROR_LOGOUT => 616,
            ErrorTypes::ERROR_SAVE_SESSION => 617,
            ErrorTypes::ERROR_CONTROLLER => 618,
            ErrorTypes::ERROR_ADD_ACCOUNT => 630,
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
            ErrorTypes::DIRECTORIO_NO_EXISTE => 'El directorio especificado no existe',
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
            ErrorTypes::ERROR_ADD_ACCOUNT => 'Error al guardar la cuenta en base de datos',
        };
    }
}
