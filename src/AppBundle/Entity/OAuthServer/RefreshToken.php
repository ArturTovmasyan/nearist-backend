<?php

namespace AppBundle\Entity\OAuthServer;

use FOS\OAuthServerBundle\Entity\RefreshToken as BaseRefreshToken;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class RefreshToken
 * @package AppBundle\Entity\OAuthServer
 * @ORM\Entity()
 * @ORM\Table(name="oauth2_refresh_token")
 */
class RefreshToken extends BaseRefreshToken
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\OAuthServer\Client")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $client;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Portal\User")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    protected $user;
}