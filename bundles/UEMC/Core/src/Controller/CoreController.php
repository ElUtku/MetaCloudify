<?php

namespace UEMC\Core\Controller;

use DateTime;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

use League\Flysystem\Filesystem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

use League\Flysystem\WhitespacePathNormalizer;

use UEMC\Core\Entity\Account;
use UEMC\Core\Entity\Metadata;
use UEMC\Core\Resources\ErrorTypes;
use UEMC\Core\Resources\FileStatus;
use UEMC\Core\Service\CloudException;
use UEMC\Core\Service\CloudService as Core;
use UEMC\Core\Service\UemcLogger;
use UEMC\OwnCloud\Service\CloudService as OwnCloudCore;
use UEMC\Ftp\Service\CloudService as FtpCore;
use UEMC\GoogleDrive\Service\CloudService as GoogleDriveCore;
use UEMC\OneDrive\Service\CloudService as OneDriveCore;


class CoreController extends AbstractController
{

    private Account $account;
    private Core $core;
    private SessionInterface $session;
    private Request $request;
    private EntityManagerInterface $em;
    private String $ruta;
    private String $path;
    private String $name;
    private String $accountId;
    private bool $isMove;
    

    public function __construct(RequestStack $requestStack, ManagerRegistry $doctrine)
    {
        $request = $requestStack->getCurrentRequest();
        $session = $requestStack->getSession();
        $this->ruta=$request->attributes->get('_route') ?? '';
        $this->session=$session;
        $this->request=$request;
        $this->em = $doctrine->getManager();
        $this->accountId = $request->get('accountId') ?? '';
        $this->path=$request->get('path') ?? '';
        $this->name=$request->get('name') ?? '';
        $this->isMove=false;
    }
    
    /**
     * 1º
     * Se escoge un tipo de core.
     *
     * @param string $cloud
     * @return void
     * @throws CloudException
     */
    private function createContext(string $cloud): void
    {
        $this->core = match ($cloud) {
            'onedrive' => new OneDriveCore(),
            'googledrive' => new GoogleDriveCore(),
            'owncloud' => new OwnCloudCore(),
            'ftp' => new FtpCore(),
            default => throw new CloudException(ErrorTypes::ERROR_CONTROLLER->getErrorMessage(),
                                                ErrorTypes::ERROR_CONTROLLER->getErrorCode()),
        };
        $this->core->setLogger(new UemcLogger());
        $this->core->setPathNormalizer(new WhitespacePathNormalizer());
        $this->account=new Account();
    }


    /**
     * 2º
     * Recuperar cuenta guardada en sesion
     *
     * @param String $accountId
     * @return Account
     * @throws CloudException
     */
    private function retriveAccount(String $accountId): Account
    {
        if($this->session->has('accounts'))
        {
            $account=$this->core->arrayToObject($this->session->get('accounts')[$accountId]);
            $this->core->testConection($account);
            return $account;
        }else
        {
            throw new CloudException(ErrorTypes::ERROR_OBTENER_USUARIO->getErrorMessage(),
                ErrorTypes::ERROR_OBTENER_USUARIO->getErrorCode());
        }
    }
    
    /**
     * 3º
     * Se recupera el filesystem si ya existe en sesión
     *
     * @param Account $account
     * @return Filesystem
     * @throws CloudException
     */
    private function retriveCore(Account $account): Filesystem
    {

        if($this->ruta !== 'login' and $this->ruta !== 'login_token' and $this->ruta !== 'loginWeb' )
        {
            return $this->core->constructFilesystem($account);
        } else{
            throw new CloudException(ErrorTypes::ERROR_CONSTRUIR_FILESYSTEM->getErrorMessage(),
                ErrorTypes::ERROR_CONSTRUIR_FILESYSTEM->getErrorCode());
        }
    }

    public function notFound(): Response
    {
        return new JsonResponse('La url solicitada no existe', Response::HTTP_NOT_FOUND);
    }

    public function frameworkError(): Response
    {
        return new JsonResponse('Error al procesar la solicitud', Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     *
     *  Login generico para autenticarse via web.
     *
     * @Route("/{cloud}/login", name="login", methods={"GET","POST"}) //GET es usado para solicitar la url OAUTH el resto de peticiones van por POST
     */
    public function login(String $cloud): Response
    {
        try {

            $this->createContext($cloud);

            $account=$this->core->login($this->session,$this->request);

            $accountExists = $this->em->getRepository(Account::class)->login($account);

            $account->setId($accountExists->getId());

            $accountId=$this->core->setSession($this->session,$account);

            $this->core->logger->info('LOGGIN | '.'AccountId:'.$accountId.' | id: '.
                $accountExists->getId().' | controller: '.$account->getCloud().
                ' | user:' . $account->getUser());

            return $this->redirectToRoute('_home_index');

        }catch (CloudException $e)
        {
            $this->core->logger->warning('LOGGIN ERROR | '.$e->getMessage());

            return new JsonResponse($e->getMessage(),$e->getCode());
        }
    }

    /**
     *
     *  Este login debe ser usado como endpoint para obtener el identificador de la cuenta en la sesion.
     *
     * @Route("/{cloud}/login/token", name="login_token", methods={"GET","POST"})
     */
    public function loginPost(String $cloud): Response
    {
        try {

            $this->createContext($cloud);

            $result=$this->core->loginPost($this->session,$this->request);

            if($result instanceof Account)
            {
                $accountExists=$this->em->getRepository(Account::class)->login($result);

                $result->setId($accountExists->getId());
                $accountId=$this->core->setSession($this->session,$result);

                $this->core->logger->info('LOGGIN | '.'AccountId:'.$accountId.' | id: '.
                    $accountExists->getId().' | controller: '.$result->getCloud().
                    ' | user:' . $result->getUser());

                return new JsonResponse('El identificador es ' .$accountId);
            } else
            {
                return new JsonResponse($result);
            }

        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getCode());
        }
    }

    /**
     *
     *  Este login proporciona una interfaz web para aquellas nubes que necesiten autentitcacion básica
     *
     * @Route("/{cloud}/login/web", name="login_web", methods={"GET"})
     */
    public function loginWeb(string $cloud) : Response
    {
        return match ($cloud) {
            'owncloud' => $this->render('@UEMCOwnCloudBundle/login.html.twig'),
            'ftp' => $this->render('@UEMCFtpBundle/login.html.twig'),
            default => new JsonResponse(ErrorTypes::ERROR_INDETERMINADO->getErrorMessage(),
                ErrorTypes::ERROR_INDETERMINADO->getErrorCode()),
        };
    }

    /**
     *
     *  Elimina una cuenta de la sesión.
     *
     * @Route("/{cloud}/logout", name="logout", methods={"GET"})
     */
    public function logout(String $cloud): Response
    {
        try {
            $this->createContext($cloud);

            $this->core->logout($this->session,$this->request);

            $this->core->logger->info('LOGOUT | '.' | id: '.
                $this->account->getCloud().
                ' | user:' . $this->account->getUser());
            return new JsonResponse($this->accountId.' - Sesion cerrada satisfactoriamente',Response::HTTP_OK);
        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getCode());
        }
    }

    /**
     *
     * Lista todos los archvios de una ruta.
     *
     * @Route("/{cloud}/drive", name="drive", methods={"GET"})
     */
    public function drive(String $cloud): Response
    {
        try {

            $this->createContext($cloud);
            $this->account=$this->retriveAccount($this->accountId);
            $this->core->setFilesystem($this->retriveCore($this->account));


            $contentInDirectory=$this->core->listDirectory($this->path)->toArray();

            $account = $this->em->getRepository(Account::class)->getAccount($this->account);

            // ------- Se añade a cada archivo sus metadatos (si los tiene) ------
            foreach ($contentInDirectory as $archive)
            {
                $item=json_decode(json_encode($archive),true); //Se convierte a un objeto modificable
                $path=$item['path'];

                // ---- Si el archivo no esta registrado en nuestra base de datos, se ignora
                if ($this->em->getRepository(Metadata::class)->findByExactPathAndAccountNull($account,dirname($path),basename($path)))
                {

                    $archivoTipado=$this->core->getTypeOfArchive($archive); //Se define si es carpeta o fichero
                    $metadata = $this->core->getBasicMetadata($archivoTipado,$account);

                    $extraMetadata=$this->em->getRepository(Metadata::class)->getCloudMetadata($metadata);

                    $item['extra_metadata'] = [
                        'virtual_name' => $extraMetadata->getVirtualName(),
                        'virtual_path' => $extraMetadata->getVirtualPath(),
                        'author' => $extraMetadata->getAuthor(),
                        'visibility' => $extraMetadata->getVisibility(),
                        'status' => $extraMetadata->getStatus(),
                        'extra' => $extraMetadata->getExtra(),
                    ];
                }

                $archivesWhitMetadata[]=$item;
            }

            $this->core->logger->info('DRIVE | '.' | id: '.
                $account->getId().' | controller: '.$account->getCloud().
                ' | user:' . $account->getUser());

            return new JsonResponse($archivesWhitMetadata??$contentInDirectory,Response::HTTP_OK);

        }catch (CloudException $e)
        {
            $this->addFlash('error','CODE: '.$e->getCode(). ' - MESSAGE: '.$e->getMessage());

            return new JsonResponse($e->getMessage(),$e->getCode());
        }

    }

    /**
     *
     * Descarga el archivo pasado en la ruta
     *
     * @Route("/{cloud}/drive/download", name="download", methods={"GET"})
     */
    public function download(String $cloud): Response
    {
        try {
            
            $this->createContext($cloud);
            $this->account=$this->retriveAccount($this->accountId);
            $this->core->setFilesystem($this->retriveCore($this->account));


            $this->core->logger->info('DOWNLOAD | '.' file: '.$this->path.'\\'.$this->name.
               ' | controller: '.$this->account->getCloud().
                ' | user:' . $this->account->getUser());

            return $this->core->download($this->path,$this->name); // Tipo Resonse
        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getCode());
        }
    }

    /**
     *
     * Crea una carpeta con el nombre y en la ruta especificados
     *
     * @Route("/{cloud}/drive/createDir", name="createDir", methods={"POST"})
     */
    public function createDir(String $cloud): Response
    {
        try {

            $this->createContext($cloud);
            $this->account=$this->retriveAccount($this->accountId);
            $this->core->setFilesystem($this->retriveCore($this->account));
            
            $this->core->createDir($this->path,$this->name);

            $this->em->getRepository(Metadata::class)->store(
                new Metadata(
                    $this->name,
                    null,
                    $this->path,
                    null,
                    'dir',
                    null,
                    null,
                    new DateTime(),
                    null,
                    null,
                    FileStatus::NEW->value,
                    null,
                    $this->em->getRepository(Account::class)->getAccount($this->account)
                ));

            $this->core->logger->info('CREATE_DIR | '.' dir: '.$this->path.'\\'.$this->name.
                ' | controller: '.$this->account->getCloud().
                ' | user:' . $this->account->getUser());

            return $this->drive($cloud);

        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getCode());
        }
    }

    /**
     *
     * Crea un fichero con el nombre y en la ruta especificados
     *
     * @Route("/{cloud}/drive/createFile", name="createFile", methods={"POST"})
     */
    public function createFile(String $cloud): Response
    {
        try{

            $this->createContext($cloud);
            $this->account=$this->retriveAccount($this->accountId);
            $this->core->setFilesystem($this->retriveCore($this->account));
            
            $this->core->createFile($this->path,$this->name);

            $this->em->getRepository(Metadata::class)->store(
                new Metadata($this->name,
                    null,
                    $this->path,
                    null,
                    'file',
                    0,
                    pathinfo($this->name, PATHINFO_EXTENSION),
                    new DateTime(),
                    null,
                    null,
                    FileStatus::NEW->value,
                    null,$this->em->getRepository(Account::class)->getAccount($this->account)
                ));

            $this->core->logger->info('CREATE_FILE | '.' file: '.$this->path.'\\'.$this->name.
                ' | controller: '.$this->account->getCloud().
                ' | user:' . $this->account->getUser());

            return $this->drive($cloud);

        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getCode());
        }
    }

    /**
     *
     * Elimina el archivo que se encuentre en la ruta especificada
     *
     * @Route("/{cloud}/drive/delete", name="delete", methods={"DELETE"})
     */
    public function delete(String $cloud): Response
    {
        try {

            $this->createContext($cloud);
            $this->account=$this->retriveAccount($this->accountId);
            $this->core->setFilesystem($this->retriveCore($this->account));
            
            $fullPath=rtrim($this->path.'/'.$this->name, '/');

            $accountBD = $this->em->getRepository(Account::class)->getAccount($this->account);
            $archivo=$this->core->getArchivo(str_replace('\\', '/', ($fullPath)));

            /* --- Se obtiene y configura los metadatadatos del archivo. Si no existen registros
                   previos se crean y si existen se modifican --- */

            $metadata = $this->core->getBasicMetadata($archivo,$accountBD);

            $metadata->setName(basename($fullPath));
            $metadata->setPath(dirname($fullPath));
            $metadata->setStatus(FileStatus::DELETED->value);

            $this->em->getRepository(Metadata::class)->store($metadata);
            $this->em->getRepository(Metadata::class)->deleteDirectory($metadata);

            $this->core->delete($fullPath);

            $this->core->logger->info('DELETE | '.' file: '.$fullPath.
                ' | controller: '.$this->account->getCloud().
                ' | user:' . $this->account->getUser());

            if($this->isMove)
            {
                return new JsonResponse('',Response::HTTP_OK);
            }else
            {
                return $this->drive($cloud);
            }

        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getCode());
        }
    }

    /**
     *
     * Envia al cliente el archivo en crudo solicitado en la ruta
     *
     * @Route("/{cloud}/drive/upload", name="upload", methods={"POST"})
     */
    public function upload(String $cloud): Response
    {
        try {

            $accountId = $this->request->get('accountId') ?? null;
            
            $this->createContext($cloud);
            $this->account=$this->retriveAccount($this->accountId);
            $this->core->setFilesystem($this->retriveCore($this->account));

            // Se obtiene el contenido del fichero en forma UploadedFile
            $content=$this->core->getUploadedFile($this->request->files->get('content'));

            $sourcePath=$content->getPathname();
            $destinationPath=$this->request->get('path');
            
            $this->core->upload($destinationPath,$content);

// Se distingue entre colocar el archivo en root o en un directorio
            if(!empty($destinationPath))
            {
                $uploadPath=$destinationPath.'\\'.$content->getClientOriginalName();
            } else{
                $uploadPath=$content->getClientOriginalName();
            }

            $archivo=$this->core->getArchivo(str_replace('\\', '/', ($uploadPath)));

            $metadata = $this->core->getBasicMetadata($archivo,$this->em->getRepository(Account::class)->getAccount($this->account));

            $metadata->setName($content->getClientOriginalName());
            $metadata->setPath($destinationPath);
            $metadata->setStatus(FileStatus::NEW->value);

            $this->em->getRepository(Metadata::class)->store($metadata);

            $this->core->logger->info('UPLOAD | '.' file: '.$destinationPath.'\\'.$content->getClientOriginalName().
                ' | controller: '.$this->account->getCloud().
                ' | user:' . $this->account->getUser());

            return $this->drive($cloud);
        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getCode());
        }
    }

    /**
     *
     * Envia los datos y metadatos del archivo especificado en la ruta
     *
     * @Route("/{cloud}/drive/getArchive", name="getArchive", methods={"GET"})
     */
    public function getArchive(String $cloud): Response
    {
        try {



            $this->createContext($cloud);
            $this->account=$this->retriveAccount($this->accountId);
            $this->core->setFilesystem($this->retriveCore($this->account));

            $account = $this->em->getRepository(Account::class)->getAccount($this->account);

            $fileMetadata=$this->em->getRepository(Metadata::class)->findByExactPathAndAccountNull($account,dirname($this->path),basename($this->path));

            $file=$this->core->getAnArchive($this->path);
            if ($fileMetadata)
            {
                $file['visibility'] = $fileMetadata->getVisibility()??$file['visibility'];
                $file['extra_metadata']['author'] = $fileMetadata->getAuthor();
                $file['extra_metadata']['extra'] = $fileMetadata->getExtra();
            }

           /* $this->core->logger->info('GET_ARCHIVE | '.' archive: '.$this->path.
                ' | controller: '.$this->account->getCloud().
                ' | user:' . $this->account->getUser()); */

            return new JsonResponse($file,Response::HTTP_OK);
        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getCode());
        }

    }

    /**
     *
     * Guarda los nuevos metadatos de un archvio en base de datos
     *
     * @Route("/{cloud}/drive/editMetadata", name="editMetadata", methods={"PUT","PATCH"})
     */
    public function editMetadata(String $cloud): Response
    {
        try {

            $this->createContext($cloud);
            $this->account=$this->retriveAccount($this->accountId);
            $this->core->setFilesystem($this->retriveCore($this->account));

            $account = $this->em->getRepository(Account::class)->getAccount($this->account);

            $metadata=json_decode($this->request->get('metadata'),true);

            $file=$this->em->getRepository(Metadata::class)->findByExactPathAndAccountNull($account,dirname($this->path),basename($this->path));
            if($file)
            {
                $file->setAuthor($metadata['author']);
                $file->setVisibility($metadata['visibility']);
                $file->setExtra(json_encode($metadata['extra']));
                $file->setStatus(FileStatus::MODIFIED->value);
            } else //Si $file no existe es probable que sea una primera modificación de un fichero no indexado
            {
                $fileMetadata=$this->core->getAnArchive($this->path);
                $file = new Metadata(
                    basename($fileMetadata['path']),
                    $fileMetadata['extra_metadata']['id']??null,
                    dirname($this->core->cleanOwncloudPath($fileMetadata['path'])),
                    $fileMetadata['extra_metadata']['virtual_path']??null,
                    $fileMetadata['type'],
                    $fileMetadata['file_size']??null,
                    $fileMetadata['mime_type']??null,
                    (new DateTime())->setTimestamp($fileMetadata['last_modified']),
                    $metadata['author']??null,
                    $metadata['visibility']??$fileMetadata['visibility'],
                    FileStatus::MODIFIED->value,
                    json_encode($metadata['extra']??null),
                    $account);
            }

            $this->em->getRepository(Metadata::class)->store($file);

            $this->core->logger->info('EDIT_METADATA | '.' archive: '.$this->path.
                ' | controller: '.$this->account->getCloud().
                ' | user:' . $this->account->getUser());

            return new JsonResponse('Metadatos editados correctamente',Response::HTTP_OK);
        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(),$e->getCode());
        }
    }

    /**
     *
     * Copia los ficheros de un filesystem a otro
     *
     * @Route("/{cloud}/copy", name="copy", methods={"POST"})
     */
    public function copy(String $cloud): Response
    {
        try {

//Configuramos todos los parametros que vamos a necesitar
            $accountId1 = $this->request->get('accountId1') ?? null;
            $accountId2 = $this->request->get('accountId2') ?? null;
            
            $sourceFullPath=$this->request->get('sourcePath'); // aa/a.txt
            $destinationDirectoryPath=$this->request->get('destinationPath'); // algun lugar/aa/
            $destinationFullPath=$destinationDirectoryPath.'/'.basename($sourceFullPath); // algun lugar/aa/a.txt
            $destinationCloud=$this->request->get('destinationCloud');

            $this->createContext($cloud);
            $sourceAccount=$this->retriveAccount($accountId1);
            $sourceFileSystem=$this->retriveCore($sourceAccount);

            $this->createContext($destinationCloud);
            $destinationAccount=$this->retriveAccount($accountId2);
            $destinationFileSystem=$this->retriveCore($destinationAccount);

            try {
                $sourceAccountBD = $this->em->getRepository(Account::class)->getAccount($sourceAccount);
                $destinationAccountBD=$this->em->getRepository(Account::class)->getAccount($destinationAccount);
            } catch (NonUniqueResultException $e) {
                throw new CloudException(ErrorTypes::ERROR_OBTENER_USUARIO->getErrorMessage().' - '.$e->getMessage(),
                    ErrorTypes::ERROR_OBTENER_USUARIO->getErrorCode());
            }

//Obtenemos los metadatos del archivo original
            $this->createContext($cloud);
            $this->core->setFilesystem($sourceFileSystem);
            $originalFile=$this->core->getArchivo($sourceFullPath);
            $originalMetadataFile=$this->core->getBasicMetadata($originalFile,$sourceAccountBD);
            $originalCloudMetadataFile=$this->em->getRepository(Metadata::class)->getCloudMetadata($originalMetadataFile);

//Copiamos a la cuenta destino el archivo
            $this->core->copy($sourceFileSystem,$destinationFileSystem,$sourceFullPath,$destinationDirectoryPath);

//Copiamos los metadatos del archivo original al de destino
            $this->createContext($destinationCloud);
            $this->core->setFilesystem($destinationFileSystem);
            $destiantionFile=$this->core->getArchivo($destinationFullPath);
            $destiantionMetadataFile=$this->core->getBasicMetadata($destiantionFile,$destinationAccountBD);
            $this->em->getRepository(Metadata::class)->copyMetadata($destiantionMetadataFile,$originalCloudMetadataFile);

            $this->core->logger->info('COPY | origen: '.$cloud.'::'.$sourceFullPath.' | destination: '.$destinationCloud.'::'.$destinationFullPath);

            if($this->isMove)
            {
                return new JsonResponse('', response::HTTP_OK);
            }else
            {
                $this->accountId = $accountId2;
                $this->path = $destinationDirectoryPath;
                return $this->drive($destinationCloud);
            }

        }catch (CloudException $e)
        {
            return new JsonResponse($e->getMessage(), response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     *
     * Mueve los ficheros de un filesystem a otro
     *
     * @Route("/{cloud}/move", name="move", methods={"PUT","PATCH"})
     */
    public function move(String $cloud): Response
    {
        try {
            $this->isMove=true;

            $responseCopy = $this->copy($cloud);
//Si la copia es correcta se procede a eliminar el archivo de origen
            if ($responseCopy->getStatusCode() === 200) {

                $accountId1 = $this->request->get('accountId1') ?? null;

                $sourceFullPath = $this->request->get('sourcePath'); // aa/a.txt

                $this->accountId = $accountId1;
                $this->path = dirname($sourceFullPath);
                $this->name = basename($sourceFullPath);

                $responseDelete = $this->delete($cloud);

                if ($responseDelete->getStatusCode() === 200) {
//Si la eliminacion es correcta se procede a listar los arvhivos de la ruta actual
                    $accountId2 = $this->request->get('accountId2') ?? null;
                    $destinationCloud = $this->request->get('destinationCloud');
                    $destinationDirectoryPath = $this->request->get('destinationPath'); // algun lugar/aa/

                    $this->accountId = $accountId2;
                    $this->path = $destinationDirectoryPath;
                    return $this->drive($destinationCloud);

                } else {
                    return $responseDelete;
                }

            } else {
                return $responseCopy;
            }

        }catch(CloudException $e){
            return new JsonResponse($e->getMessage(), response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}