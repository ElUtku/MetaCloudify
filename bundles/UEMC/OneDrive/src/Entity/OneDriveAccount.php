<?php

namespace UEMC\OneDrive\Entity;

use GuzzleHttp\Exception\GuzzleException;
use Microsoft\Graph\Exception\GraphException;
use Microsoft\Graph\Graph;
use Stevenmaguire\OAuth2\Client\Provider\Microsoft;
use Symfony\Component\Yaml\Yaml;
use UEMC\OneDrive\Repository\OneDriveAccountRepository;
use Doctrine\ORM\Mapping as ORM;
use UEMC\Core\Entity\Account;

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

    public function login($session,$request)
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
                $onedriveSession['token']=$token->jsonSerialize();
                $session->set('onedriveSession',$onedriveSession);


            } catch (Exception $e) {
                return($e);
            }
            return "OK";
        }
    }

    public function getUserInfo($session,$request)
    {
        $onedriveSession=$session->get('onedriveSession');
        $graph = new Graph();
        $graph->setAccessToken($onedriveSession['token']['access_token']);

        try {
            $user = $graph->createRequest('GET', '/me')
                ->setReturnType(Model\User::class)
                ->execute();
            $onedriveSession['oneDriveUser']=json_decode(json_encode($user), true);
            $session->set('onedriveSession',$onedriveSession);
            return "OK";
        } catch (GuzzleException|GraphException $e) {
            return $e;
        }

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
