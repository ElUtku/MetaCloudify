<?php

namespace UEMC\GoogleDrive\Service;

// include 'vendor/autoload.php';

use DateTime;
use Exception;
use Google_Client;
use Google\Service\Drive;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\Visibility;
use League\OAuth2\Client\Provider\Google;
use Masbug\Flysystem\GoogleDriveAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Yaml\Yaml;

use UEMC\Core\Entity\Account;
use UEMC\Core\Entity\Metadata;
use UEMC\Core\Resources\CloudTypes;
use UEMC\Core\Resources\ErrorTypes;
use UEMC\Core\Service\CloudService as Core;
use UEMC\Core\Service\CloudException;

class CloudService extends Core
{

    private GoogleDriveAdapter $adapter;

    /**
     * @return GoogleDriveAdapter
     */
    public function getAdapter(): GoogleDriveAdapter
    {
        return $this->adapter;
    }

    /**
     * @param GoogleDriveAdapter $adapter
     */
    public function setAdapter(GoogleDriveAdapter $adapter): void
    {
        $this->adapter = $adapter;
    }




    /**
     * @param SessionInterface $session
     * @param Request $request
     * @return Account
     * @throws CloudException
     */
    public function login(SessionInterface $session, Request $request): Account
    {
        $config = Yaml::parseFile(__DIR__.'\..\Resources\config\googledrive.yaml');

        $provider = new Google([
            'clientId'     => $config['clientId'],
            'clientSecret' => $config['clientSecret'],
            'redirectUri'  => $config['redirectUri']
        ]);
        $options = [ 'scope' => $config['scopes'] ];

        $code=$request->get('code');
        $state=$request->get('state');
        $oauth2state=$session->get('oauth2state');

        if (empty($code)) { // Se obtiene el codigo de autorizacion si no lo tenemos.

            $authUrl = $provider->getAuthorizationUrl($options);
            $oauth2state=$provider->getState();
            $session->set('oauth2state',$oauth2state);
            header('Location: '.$authUrl);
            exit();

        } elseif (empty($state) || ($state !== $oauth2state)) { // Si el codigo de estado esta vacio o coincide es
                                                                // porque ha sido modificado.
                                                                // Por seguridad borramos la sesion.
            $session->remove('oauth2state');
            throw new CloudException(ErrorTypes::ERROR_STATE_OAUTH2->getErrorMessage(), ErrorTypes::ERROR_STATE_OAUTH2->getErrorCode());

        } else { //Si el codigo es correcto solicitamos un token de acceso y gurdamos la cuenta en la sesion
            try {

                $session->remove('oauth2state');

                $token = $provider->getAccessToken('authorization_code', [
                    'code' => $request->get('code')
                ]);

                $user=$provider->getResourceOwner($token)->toArray(); //Se obtiene el usuario y se transforma en objeto
                $account=$this->arrayToObject($user);

                $account->setLastIp($request->getClientIp());
                $account->setLastSession(new DateTime());
                $account->setToken($token);
                $account->setCloud(CloudTypes::GoogleDrive->value);

                return $account;

            } catch (Exception $e) {
                throw new CloudException(ErrorTypes::ERROR_INICIO_SESION->getErrorMessage().' - '.$e->getMessage(),
                                        ErrorTypes::ERROR_INICIO_SESION->getErrorCode());
            }

        }
    }

    /**
     * @param SessionInterface $session
     * @param Request $request
     * @return Account
     * @throws CloudException
     */
    public function loginPost(SessionInterface $session, Request $request): Account
    {
        try {

            $config = Yaml::parseFile(__DIR__.'\..\Resources\config\googledrive.yaml');

            $provider = new Google([
                'clientId'     => $config['clientId'],
                'clientSecret' => $config['clientSecret'],
                'redirectUri'  => $config['redirectUri']
            ]);

            $token=$request->get('token');

            $user=$provider->getResourceOwner($token)->toArray(); //Se obtiene el usuario y se transforma en objeto
            $account=$this->arrayToObject($user);

            $account->setLastIp($request->getClientIp());
            $account->setLastSession(new DateTime);
            $account->setToken($token);
            $account->setCloud(CloudTypes::GoogleDrive->value);

            return $account;
        } catch (Exception $e)
        {
            throw new CloudException(ErrorTypes::ERROR_INICIO_SESION->getErrorMessage().' - '.$e->getMessage(),
                ErrorTypes::ERROR_INICIO_SESION->getErrorCode());
        }
    }

    /**
     * Convierte un array al objeto de la clase
     *
     * @param $array | Array con los parametros de la cuenta (Existen dos verisones, si se invoca desde getUserInfo los
     *                 parametros tienen un nombre y si se invoca desde sesion, tienen otro.)
     * @return Account
     * @throws CloudException
     */
    function arrayToObject($array): Account
    {
        try {
            $account = new Account();
            $account->setCloud(CloudTypes::GoogleDrive->value);
            $account->setUser($array['name'] ?? $array['user']?? '');
            $account->setEmail($array['email'] ?? '');
            $account->setOpenid($array['sub'] ?? $array['openid']?? '');
            $account->setLastIp($array['last_ip']?? '');
            $account->setLastSession($array['last_session']?? new DateTime());
            $account->setToken($array['token']?? '');
            return $account;
        } catch (Exception $e)
        {
            throw new CloudException(ErrorTypes::ERROR_CONSTRUIR_OBJETO->getErrorMessage().' - '.$e->getMessage(),
                                    ErrorTypes::ERROR_CONSTRUIR_OBJETO->getErrorCode());
        }

    }


    /**
     * @param Account $account
     * @return Filesystem
     * @throws CloudException
     **/
    public function constructFilesystem(Account $account): Filesystem
    {
        try {
            $config = Yaml::parseFile(__DIR__.'\..\Resources\config\googledrive.yaml');

            $client = new Google_Client();
            $client->setClientId($config['clientId']);
            $client->setClientSecret($config['clientSecret']);
            $client->setAccessToken($account->getToken());

            $service = new Drive($client);

            $adapter = new GoogleDriveAdapter($service,  '');
            $this->setAdapter($adapter);

            return new Filesystem($adapter, [
                new Config([Config::OPTION_VISIBILITY => Visibility::PRIVATE])
            ]);
        }
        catch (Exception)
        {
            throw new CloudException(ErrorTypes::ERROR_CONSTRUIR_FILESYSTEM->getErrorMessage(), ErrorTypes::ERROR_CONSTRUIR_FILESYSTEM->getErrorCode());
        }
    }

    public function getNativeMetadata(String $path):Metadata
    {
        try {

            $attributes = match ($this->distinguirTipoRuta($path)) {
                'file' => new FileAttributes($path),
                'dir' => new DirectoryAttributes($path),
                default => throw new CloudException(ErrorTypes::NO_SUCH_FILE_OR_DIRECTORY->getErrorMessage(),
                    ErrorTypes::NO_SUCH_FILE_OR_DIRECTORY->getErrorCode()),
            };

            $this->logger->debug("path->: ".$path);
            $this->logger->debug("isFile()->: ".$attributes->isFile());
            $this->logger->debug("type()->: ".$attributes->type());

            $extraMetadata= $this->getAdapter()->getMetadata($path);

            $this->logger->debug("extraMetadata: ".json_encode($extraMetadata));
            return new Metadata(null,$extraMetadata['extraMetadata']['id']??null,$path,$extraMetadata['extraMetadata']['virtual_path']??null,$attributes->type(),$attributes->lastModified()??new \DateTime(),null,$attributes->visibility(),null,null);

        } catch (FilesystemException | \Exception $e) {
            throw new CloudException(ErrorTypes::ERROR_GET_NATIVE_METADATA->getErrorMessage().' - '.$e->getMessage(),
                ErrorTypes::ERROR_GET_NATIVE_METADATA->getErrorCode());
        }

    }
}