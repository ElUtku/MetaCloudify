<?php

namespace UEMC\GoogleDrive\Service;

// include 'vendor/autoload.php';

use Exception;
use Google_Client;
use Google\Service\Drive;
use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use League\Flysystem\Visibility;
use League\OAuth2\Client\Provider\Google;
use Masbug\Flysystem\GoogleDriveAdapter;
use Symfony\Component\Yaml\Yaml;

use UEMC\Core\Entity\Account;
use UEMC\Core\Resources\CloudTypes;
use UEMC\Core\Service\CloudService as Core;

class CloudService extends Core
{
    public function login($session, $request): Account|Exception|string
    {
        $config = Yaml::parseFile(__DIR__.'\..\Resources\config\googledrive.yaml');

        $provider = new Google([
            'clientId'     => $config['clientId'],
            'clientSecret' => $config['clientSecret'],
            'redirectUri'  => $config['redirectUri']
        ]);

        if (!empty($request->get('error'))) {

            exit('Got error: ' . htmlspecialchars($request->get('error'), ENT_QUOTES, 'UTF-8'));

        } elseif (empty($request->get('code'))) {

            $authUrl = $provider->getAuthorizationUrl([
                'scope' => $config['scopes'],
            ]);
            $googleSesion['oauth2state']=$provider->getState();
            $session->set('googleSession',$googleSesion);
            header('Location: ' . $authUrl);
            exit();

        } elseif (empty($request->get('state')) || ($request->get('state') !== $session->get('googleSession')['oauth2state'])) {
            // Situacion de ataque CSRF
            $session->remove('googleSession');
            return ('Invalid state');

        } else {
            try {

                $session->remove('googleSession');

                $token = $provider->getAccessToken('authorization_code', [
                    'code' => $request->get('code')
                ]);
                $user=$provider->getResourceOwner($token)->toArray();
                $account=$this->arrayToObject($user);

                $account->setLastIp($request->getClientIp());
                $account->setLastSession(new \DateTime);
                $account->setToken($token);
                $account->setCloud(CloudTypes::GoogleDrive->value);

                $this->setSession($session, $account);

            } catch (Exception $e) {
                return($e);
            }
            return $token->getToken();
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
        $account = new Account();
        $account->setUser($array['name'] ?? $array['user']);
        $account->setEmail($array['email']);
        $account->setOpenid($array['sub'] ?? $array['openid']);
        $account->setToken($array['token'] ?? '');
        return $account;
    }
    public function constructFilesystem(Account $account): Filesystem
    {
        $config = Yaml::parseFile(__DIR__.'\..\Resources\config\googledrive.yaml');

        $client = new Google_Client();
        $client->setClientId($config['clientId']);
        $client->setClientSecret($config['clientSecret']);
        $client->setAccessToken($account->getToken());

        $service = new Drive($client);

        $adapter = new GoogleDriveAdapter($service,  '');

        $filesystem = new Filesystem($adapter, [
            new Config([Config::OPTION_VISIBILITY => Visibility::PRIVATE])
        ]);

        return $filesystem;
    }
}