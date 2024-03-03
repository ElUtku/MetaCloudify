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
                //$session->set('code',$request->get('code'));
                // Try to get an access token (using the authorization code grant)
                $token = $provider->getAccessToken('authorization_code', [
                    'code' => $request->get('code')
                ]);
                $user=$provider->getResourceOwner($token)->toArray();
                $account=$this->arrayToObject($user);

                $account->setLastIp($request->getClientIp());

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

    public function logout($session, $request): string
    {
        $googleDriveAccounts = $session->get('googledriveAccounts');

        $id = $request->get('id');
        if (array_key_exists($id, $googleDriveAccounts)) {
            // Eliminar el elemento del array
            unset($googleDriveAccounts[$id]);
            if (empty($googleDriveAccounts) || !is_array($googleDriveAccounts)) {
                // Si está vacío o no es un array, eliminarlo de la sesión
                $session->remove('googledriveAccounts');
            }else{
                $session->set('googledriveAccounts', $googleDriveAccounts);
            }
        }
        return "Sesion limpia";
    }

    /**
     * Convierte un array al objeto de la clase
     *
     * @param $array | Array con los parametros de la cuenta (Existen dos verisones, si se invoca desde getUserInfo los
     *                 parametros tienen un nombre y si se invoca desde sesion, tienen otro.)
     * @return GoogleDriveAccount
     */
    function arrayToObject($array): GoogleDriveAccount
    {
        $object = new GoogleDriveAccount();
        $object->setUser($array['name'] ?? $array['user']);
        $object->setEmail($array['email']);
        $object->setOpenid($array['sub'] ?? $array['openid']);
        $object->setToken($array['token'] ?? '');
        return $object;
    }
}
