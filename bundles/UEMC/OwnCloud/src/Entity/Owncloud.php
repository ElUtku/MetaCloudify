<?php

namespace UEMC\OwnCloud\Entity;

use UEMC\core\Entity\Account;
use UEMC\OwnCloud\Repository\OwnCloudRepository;

class Owncloud
{

    private Account $account;
    private OwncloudOptions $options;


    public function __construct()
    {
        $this->account = new Account();
        $this->options = new OwncloudOptions();
    }

    /**
     * @return Account
     */
    public function getAccount(): Account
    {
        return $this->account;
    }

    /**
     * @param Account $account
     */
    public function setAccount(Account $account): void
    {
        $this->account = $account;
    }

    /**
     * @return \UEMC\OwnCloud\Entity\OwncloudOptions
     */
    public function getOptions(): \UEMC\OwnCloud\Entity\OwncloudOptions
    {
        return $this->options;
    }

    /**
     * @param \UEMC\OwnCloud\Entity\OwncloudOptions $options
     */
    public function setOptions(\UEMC\OwnCloud\Entity\OwncloudOptions $options): void
    {
        $this->options = $options;
    }

    public function loginSet(array $options)
    {
        $this->account->setUsername($options['username']);
        $this->account->setPassword($options['password']);
        $this->account->setEmail($options['email']);
        $this->options->setUrl($options['url']);
        $this->options->setPort($options['port']);
    }

}