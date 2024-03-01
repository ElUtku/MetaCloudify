<?php

namespace UEMC\GoogleDrive\Entity;

use Exception;
use League\OAuth2\Client\Provider\Google;
use Symfony\Component\Yaml\Yaml;
use UEMC\GoogleDrive\Repository\GoogleDriveAccountRepository;
use Doctrine\ORM\Mapping as ORM;
use UEMC\Core\Entity\Account;

#[ORM\Entity(repositoryClass: GoogleDriveAccountRepository::class)]
class GoogleDriveAccount extends Account
{
    #[ORM\Column(length: 1000)]
    private ?string $token = null;

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function login($session, $request)
    {
        $config = Yaml::parseFile(__DIR__.'\..\Resources\config\googledrive.yaml');

        $provider = new Google([
            'clientId'     => $config['clientId'],
            'clientSecret' => $config['clientSecret'],
            'redirectUri'  => $config['redirectUri']
        ]);

        if (!empty($request->get('error'))) {

            // Got an error, probably user denied access
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
            $token = $provider->getAccessToken('authorization_code', [
                'code' => $request->get('code')
            ]);
            try {
                //$session->set('code',$request->get('code'));
                // Try to get an access token (using the authorization code grant)
                $token = $provider->getAccessToken('authorization_code', [
                    'code' => $request->get('code')
                ]);
                $user=$this->$provider->getResourceOwner($token)->toArray();
                $account=$this->arrayToObject($user);
                $account->setToken($token);
                $googledriveAccounts=$session->get('googledriveAccounts');
                $googledriveAccounts[uniqid()]=get_object_vars($account);
                $session->set('googledriveAccounts',$googledriveAccounts);
            } catch (Exception $e) {
                return($e);
            }
            return $token->getToken();
        }
    }

    function arrayToObject($array): GoogleDriveAccount
    {
        $object = new GoogleDriveAccount();
        $object->setUser($array['displayName'] ?? $array['user']);
        $object->setEmail($array['mail'] ?? $array['email']);
        $object->setOpenid($array['id'] ?? $array['openid']);
        $object->setToken($array['token'] ?? '');
        return $object;
    }
}
