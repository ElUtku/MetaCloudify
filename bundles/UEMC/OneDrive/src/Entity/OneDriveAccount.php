<?php

namespace UEMC\OneDrive\Entity;

use Exception;
use GuzzleHttp\Exception\GuzzleException;

use GuzzleHttp\Client;
use Stevenmaguire\OAuth2\Client\Provider\Microsoft;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Yaml\Yaml;
use UEMC\OneDrive\Repository\OneDriveAccountRepository;
use Doctrine\ORM\Mapping as ORM;
use UEMC\Core\Entity\Account;
use Symfony\Component\HttpFoundation\Request;

#[ORM\Entity(repositoryClass: OneDriveAccountRepository::class)]
class OneDriveAccount extends Account
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

    public function login(SessionInterface $session,Request $request)
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
                //$session->set('code',$request->get('code'));
                // Try to get an access token (using the authorization code grant)
                $token = $provider->getAccessToken('authorization_code', [
                    'code' => $request->get('code')
                ]);
                $user=$this->getUserInfo($token);
                $account=$this->arrayToObject($user);
                $account->setToken($token);
                $onedriveAccounts[uniqid()]=get_object_vars($account);
                $session->set('onedriveAccounts',$onedriveAccounts);
            } catch (Exception $e) {
                return($e);
            }
            return "OK";
        }
    }


    /**
     * Proporciona información básica del usuario gracias a la API de Microsoft /me.
     *
     * @param $token
     * @return Exception|GuzzleException|mixed|string
     */
    public function getUserInfo($token)
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
     * @return OneDriveAccount
     */
    function arrayToObject($array): OneDriveAccount
    {
        $object = new OneDriveAccount();
        $object->setUser($array['displayName'] ?? $array['user']);
        $object->setEmail($array['mail'] ?? $array['email']);
        $object->setOpenid($array['id'] ?? $array['openid']);
        $object->setToken($array['token'] ?? null);
        return $object;
    }
    public function logout($session, $request): string
    {
        $onedriveAccounts = $session->get('onedriveAccounts');

        $id = $request->get('id');
        if (array_key_exists($id, $onedriveAccounts)) {
            // Eliminar el elemento del array
            unset($onedriveAccounts[$id]);
            if (empty($onedriveAccounts) || !is_array($onedriveAccounts)) {
                // Si está vacío o no es un array, eliminarlo de la sesión
                $session->remove('onedriveAccounts');
            }else{
                $session->set('onedriveAccounts', $onedriveAccounts);
            }
        }
        return "Sesion limpia";
    }
}
