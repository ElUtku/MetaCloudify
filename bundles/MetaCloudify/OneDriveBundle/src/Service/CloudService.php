<?php

namespace MetaCloudify\OneDriveBundle\Service;

use DateTime;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use League\Flysystem\Filesystem;
use ShitwareLtd\FlysystemMsGraph\Adapter;
use Microsoft\Graph\Graph;
use Stevenmaguire\OAuth2\Client\Provider\Microsoft;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Yaml\Yaml;

use MetaCloudify\CoreBundle\Entity\Account;
use MetaCloudify\CoreBundle\Resources\CloudTypes;
use MetaCloudify\CoreBundle\Resources\ErrorTypes;
use MetaCloudify\CoreBundle\Service\CloudException;
use MetaCloudify\CoreBundle\Service\CloudService as Core;
class CloudService extends Core
{

    /**
     * @param SessionInterface $session
     * @param Request $request
     * @return Account
     * @throws CloudException
     */
    public function login(SessionInterface $session,Request $request): Account
    {
        $config = Yaml::parseFile(__DIR__.'\..\Resources\config\onedrive.yaml'); //Configuraicon de la nube

        $provider = new Microsoft([
            // Required
            'clientId'                => $config['clientId'],
            'clientSecret'            => $config['clientSecret'],
            'redirectUri'             => $config['redirectUriLogin'],
            'urlAuthorize'            => $config['urlAuthorize'],
            'urlAccessToken'          => $config['urlAccessToken'],
            'urlResourceOwnerDetails' => $config['urlResourceOwnerDetails'],
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

        } else {
            try {
                $session->remove('oauth2state');

                $token = $provider->getAccessToken('authorization_code', [
                    'code' => $request->get('code')
                ]);

                $user=$this->getUserInfo($token);

                $account=$this->arrayToObject($user);

                $account->setLastIp($request->getClientIp());
                $account->setLastSession(new DateTime());
                $account->setToken($token);
                $account->setCloud(CloudTypes::OneDrive->value);
                return $account;

            } catch (Exception $e) {
                throw new CloudException(ErrorTypes::ERROR_INICIO_SESION->getErrorMessage().' - '.$e->getMessage(), ErrorTypes::ERROR_INICIO_SESION->getErrorCode());
            }
        }
    }

    /**
     * @param SessionInterface $session
     * @param Request $request
     * @return Account | string
     * @throws CloudException
     */
    public function loginPost(SessionInterface $session, Request $request): Account | String
    {
        $config = Yaml::parseFile(__DIR__.'\..\Resources\config\onedrive.yaml'); //Configuraicon de la nube

        $provider = new Microsoft([
            // Required
            'clientId'                => $config['clientId'],
            'clientSecret'            => $config['clientSecret'],
            'redirectUri'             => $config['redirectUriLoginToken'],
            'urlAuthorize'            => $config['urlAuthorize'],
            'urlAccessToken'          => $config['urlAccessToken'],
            'urlResourceOwnerDetails' => $config['urlResourceOwnerDetails'],
        ]);
        $options = [ 'scope' => $config['scopes'] ];

        $code=$request->get('code');
        $state=$request->get('state');
        $oauth2state=$session->get('oauth2state');

        if (empty($code)) { // Se obtiene el codigo de autorizacion si no lo tenemos.

            $authUrl = $provider->getAuthorizationUrl($options);
            $oauth2state=$provider->getState();
            $session->set('oauth2state',$oauth2state);

            return 'Navega en esta url para obtener una url nueva que deberas pegar como nueva peticion : '.$authUrl;

        } elseif (empty($state) || ($state !== $oauth2state)) { // Si el codigo de estado esta vacio o coincide es
            return 'Copia la URL y enviala de nuevo en el medio donde iniciaste la comunicacion';
        } else {
            try {
                $session->remove('oauth2state');

                $token = $provider->getAccessToken('authorization_code', [
                    'code' => $request->get('code')
                ]);

                $user=$this->getUserInfo($token);

                $account=$this->arrayToObject($user);

                $account->setLastIp($request->getClientIp());
                $account->setLastSession(new DateTime());
                $account->setToken($token);
                $account->setCloud(CloudTypes::OneDrive->value);
                return $account;

            } catch (Exception $e) {
                throw new CloudException(ErrorTypes::ERROR_INICIO_SESION->getErrorMessage().' - '.$e->getMessage(), ErrorTypes::ERROR_INICIO_SESION->getErrorCode());
            }
        }
    }
    /**
     * Proporciona información básica del usuario gracias a la API de Microsoft /me.
     *
     * @param $token
     * @return Exception|GuzzleException|mixed|string
     * @throws CloudException
     */
    public function getUserInfo($token): mixed
    {
        try {
            $client = new Client();
            $response = $client->request('GET', "https://graph.microsoft.com/v1.0/me", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json'
                ]
            ]);
            if ($response->getStatusCode() == 200) {
                return json_decode($response->getBody(), true);
            }
            else
            {
                throw new CloudException(ErrorTypes::ERROR_OBTENER_USUARIO->getErrorMessage(),
                    ErrorTypes::ERROR_OBTENER_USUARIO->getErrorCode());
            }
        } catch (GuzzleException $e) {
            throw new CloudException(ErrorTypes::ERROR_OBTENER_USUARIO->getErrorMessage().' - '.$e->getMessage(),
                                        ErrorTypes::ERROR_OBTENER_USUARIO->getErrorCode());
        }

    }

    /**
     * @param Account $account
     * @return void
     * @throws CloudException
     */
    public function testConection(Account $account): void
    {
        try {
            $this->getUserInfo($account->getToken());
        }catch (CloudException $e)
        {
            throw new CloudException($account->getUser().' - '.ErrorTypes::TOKEN_EXPIRED->getErrorMessage(). ' - '.$e->getMessage(),
                ErrorTypes::TOKEN_EXPIRED->getErrorCode());
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
            $account->setId(is_int($array['id']) ? $array['id'] : -1);
            $account->setCloud(CloudTypes::OneDrive->value);
            $account->setUser($array['displayName'] ?? $array['user']);
            $account->setEmail($array['mail'] ?? $array['email'] ?? null);
            $account->setOpenid($array['openid'] ?? $array['id']);
            $account->setLastIp($array['last_ip'] ?? '');
            $account->setLastSession($array['last_session'] ?? new DateTime());
            $account->setToken($array['token'] ?? '');
            return $account;
        } catch (Exception $e){
            throw new CloudException(ErrorTypes::ERROR_CONSTRUIR_OBJETO->getErrorMessage().' - '.$e->getMessage(),
                                     ErrorTypes::ERROR_CONSTRUIR_OBJETO->getErrorCode());
        }
    }


    /**
     * @param Account $account
     * @return Filesystem
     * @throws CloudException
     */
    public function constructFilesystem(Account $account): Filesystem
    {
        try {
        $access_token= $account->getToken();
        $graph = new Graph();
        $graph->setAccessToken($access_token);
        $adapter = new Adapter($graph, 'me');
        return new Filesystem($adapter);
        } catch (Exception $e){
            throw new CloudException(ErrorTypes::ERROR_CONSTRUIR_FILESYSTEM->getErrorMessage().' - '.$e->getMessage(),
                                     ErrorTypes::ERROR_CONSTRUIR_FILESYSTEM->getErrorCode());
        }
    }

}