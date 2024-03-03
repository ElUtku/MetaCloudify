<?php

namespace UEMC\OneDrive\Service;

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
use UEMC\Core\Entity\Account;
use UEMC\Core\Resources\CloudTypes;
use UEMC\Core\Service\CloudService as Core;
class CloudService extends Core
{

    public function login(SessionInterface $session,Request $request): Account|Exception|String
    {
        $config = Yaml::parseFile(__DIR__.'\..\Resources\config\onedrive.yaml'); //Configuraicon de la nube

        $provider = new Microsoft([
            // Required
            'clientId'                => $config['clientId'],
            'clientSecret'            => $config['clientSecret'],
            'redirectUri'             => $config['redirectUri'],
            'urlAuthorize'            => $config['urlAuthorize'],
            'urlAccessToken'          => $config['urlAccessToken'],
            'urlResourceOwnerDetails' => $config['urlResourceOwnerDetails'],
        ]);
        $options = [ 'scope' => $config['scope'] ];
        if (empty($request->get('code'))) {
            // If we don't have an authorization code then get one
            $authUrl = $provider->getAuthorizationUrl($options);
            $onedriveSession['oauth2state']=$provider->getState();
            $session->set('onedriveSession',$onedriveSession);
            header('Location: '.$authUrl);
            exit();
// Check given state against previously stored one to mitigate CSRF attack
        } elseif (empty($request->get('state')) || ($request->get('state') !== $session->get('onedriveSession')['oauth2state'])) {
            $session->remove('onedriveSession');
            return('Invalid state');
        } else {
            try {
                $session->remove('onedriveSession');

                $token = $provider->getAccessToken('authorization_code', [
                    'code' => $request->get('code')
                ]);
                $user=$this->getUserInfo($token);
                $account=$this->arrayToObject($user);

                $account->setLastIp($request->getClientIp());
                $account->setLastSession(new \DateTime);
                $account->setToken($token);
                $account->setCloud(CloudTypes::OneDrive->value);

                $this->setSession($session, $account);

            } catch (Exception $e) {
                return($e);
            }
            return $account;
        }
    }


    /**
     * Proporciona información básica del usuario gracias a la API de Microsoft /me.
     *
     * @param $token
     * @return Exception|GuzzleException|mixed|string
     */
    public function getUserInfo($token): mixed
    {
        $client = new Client();
        try {
            $response = $client->request('GET', "https://graph.microsoft.com/v1.0/me", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json'
                ]
            ]);
            if ($response->getStatusCode() == 200) {
                $userData = json_decode($response->getBody(), true);
                return $userData;
            }
            else
            {
                return "KO";
            }
        } catch (GuzzleException $e) {
            return $e;
        }

    }

    /**
     * Convierte un array al objeto de la clase
     *
     * @param $array | Array con los parametros de la cuenta (Existen dos verisones, si se invoca desde getUserInfo los
     *                 parametros tienen un nombre y si se invoca desde sesion, tienen otro.)
     * @return Account
     */
    function arrayToObject($array): Account
    {
        $object = new Account();
        $object->setUser($array['displayName'] ?? $array['user']);
        $object->setEmail($array['mail'] ?? $array['email']);
        $object->setOpenid($array['id'] ?? $array['openid']);
        $object->setToken($array['token'] ?? '');
        return $object;
    }


    public function constructFilesystem(Account $account): Filesystem
    {
        $access_token= $account->getToken();
        $graph = new Graph();
        $graph->setAccessToken($access_token);
        $adapter = new Adapter($graph, 'me');
        return new Filesystem($adapter);
    }

}