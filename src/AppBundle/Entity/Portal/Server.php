<?php

namespace AppBundle\Entity\Portal;

use AppBundle\Model\Persistence\Entity\TimeAwareTrait;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Server
 *
 * @ORM\Table(name="server")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ServerRepository")
 */
class Server
{
    use TimeAwareTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Groups({"server_list", "customer_server_list", "reservation", "reservation_list", "select_list", "customer_log_list"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank()
     * @Serializer\Groups({"server_list", "customer_server_list", "reservation", "reservation_list", "customer_log_list"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255)
     * @Assert\NotBlank()
     * @Serializer\Groups({"server_list", "customer_log_list"})
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=50)
     * @Assert\NotBlank()
     * @Assert\Ip(version = "all")
     * @Serializer\Groups({"server_list", "customer_log_list"})
     */
    private $ip;

    /**
     * @var int
     *
     * @ORM\Column(name="ssh_port", type="integer")
     * @Assert\NotBlank()
     * @Serializer\Groups({"server_list"})
     */
    private $sshPort;

    /**
     * @var int
     *
     * @ORM\Column(name="ftp_port", type="integer")
     * @Assert\NotBlank()
     * @Serializer\Groups({"server_list"})
     */
    private $ftpPort;

    /**
     * @var string
     *
     * @ORM\Column(name="iflex_ip", type="string", length=50, nullable=true)
     * @Assert\Ip(version = "all")
     * @Serializer\Groups({"server_list"})
     */
    private $iflexIp;

    /**
     * @var int
     *
     * @ORM\Column(name="iflex_port", type="integer")
     * @Assert\NotBlank()
     * @Serializer\Groups({"server_list"})
     */
    private $iflexPort;

    /**
     * @var int
     *
     * @ORM\Column(name="iflex_secure", type="boolean")
     * @Serializer\Groups({"server_list"})
     * @Assert\NotNull()
     */
    private $iflexSecure = false;

    /**
     * @var string $username
     * @ORM\Column(name="iflex_username", type="string", length=40, nullable=true)
     * @Serializer\Groups({"server_list"})
     */
    private $iflexUsername;

    /**
     * @var string $password
     * @ORM\Column(name="iflex_password", type="string", length=255, nullable=true)
     * @Serializer\Groups({"server_list"})
     */
    private $iflexPassword;

    /**
     * @var string
     *
     * @ORM\Column(name="ipmi_ip", type="string", length=50, nullable=true)
     * @Assert\Ip(version = "all")
     * @Serializer\Groups({"server_list"})
     */
    private $ipmiIp;

    /**
     * @var int
     *
     * @ORM\Column(name="ipmi_port", type="integer", nullable=true)
     * @Serializer\Groups({"server_list"})
     */
    private $ipmiPort;

    /**
     * @var int
     *
     * @ORM\Column(name="ipmi_secure", type="boolean")
     * @Serializer\Groups({"server_list"})
     * @Assert\NotNull()
     */
    private $ipmiSecure = false;

    /**
     * @var string $username
     * @ORM\Column(name="ipmi_username", type="string", length=40, nullable=true)
     * @Serializer\Groups({"server_list"})
     */
    private $ipmiUsername;

    /**
     * @var string $password
     * @ORM\Column(name="ipmi_password", type="string", length=255, nullable=true)
     * @Serializer\Groups({"server_list"})
     */
    private $ipmiPassword;

    /**
     * @ORM\OneToOne(targetEntity="ServerTemperature", mappedBy="server")
     */
    private $serverTemperature;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Server
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get ip
     *
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set ip
     *
     * @param string $ip
     *
     * @return Server
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get sshPort
     *
     * @return int
     */
    public function getSshPort()
    {
        return $this->sshPort;
    }

    /**
     * Set sshPort
     *
     * @param integer $sshPort
     *
     * @return Server
     */
    public function setSshPort($sshPort)
    {
        $this->sshPort = $sshPort;

        return $this;
    }

    /**
     * Get ftpPort
     *
     * @return int
     */
    public function getFtpPort()
    {
        return $this->ftpPort;
    }

    /**
     * Set ftpPort
     *
     * @param integer $ftpPort
     *
     * @return Server
     */
    public function setFtpPort($ftpPort)
    {
        $this->ftpPort = $ftpPort;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getIflexIp()
    {
        return $this->iflexIp;
    }

    /**
     * @param string $iflexIp
     */
    public function setIflexIp(string $iflexIp)
    {
        $this->iflexIp = $iflexIp;
    }

    /**
     * @return int
     */
    public function getIflexPort()
    {
        return $this->iflexPort;
    }

    /**
     * @param int $iflexPort
     */
    public function setIflexPort($iflexPort)
    {
        $this->iflexPort = $iflexPort;
    }

    /**
     * @return int
     */
    public function getIflexSecure()
    {
        return $this->iflexSecure;
    }

    /**
     * @param int $iflexSecure
     */
    public function setIflexSecure($iflexSecure)
    {
        $this->iflexSecure = $iflexSecure;
    }

    /**
     * @return string
     */
    public function getIflexUsername()
    {
        return $this->iflexUsername;
    }

    /**
     * @param string $iflexUsername
     */
    public function setIflexUsername($iflexUsername)
    {
        $this->iflexUsername = $iflexUsername;
    }

    /**
     * @return string
     */
    public function getIflexPassword()
    {
        return $this->iflexPassword;
    }

    /**
     * @param string $iflexPassword
     */
    public function setIflexPassword($iflexPassword)
    {
        $this->iflexPassword = $iflexPassword;
    }

    /**
     * @return string
     */
    public function getIpmiIp()
    {
        return $this->ipmiIp;
    }

    /**
     * @param string $ipmiIp
     */
    public function setIpmiIp($ipmiIp)
    {
        $this->ipmiIp = $ipmiIp;
    }

    /**
     * @return int
     */
    public function getIpmiPort()
    {
        return $this->ipmiPort;
    }

    /**
     * @param int $ipmiPort
     */
    public function setIpmiPort($ipmiPort)
    {
        $this->ipmiPort = $ipmiPort;
    }

    /**
     * @return int
     */
    public function getIpmiSecure()
    {
        return $this->ipmiSecure;
    }

    /**
     * @param int $ipmiSecure
     */
    public function setIpmiSecure($ipmiSecure)
    {
        $this->ipmiSecure = $ipmiSecure;
    }

    /**
     * @return string
     */
    public function getIpmiUsername()
    {
        return $this->ipmiUsername;
    }

    /**
     * @param string $ipmiUsername
     */
    public function setIpmiUsername($ipmiUsername)
    {
        $this->ipmiUsername = $ipmiUsername;
    }

    /**
     * @return string
     */
    public function getIpmiPassword()
    {
        return $this->ipmiPassword;
    }

    /**
     * @param string $ipmiPassword
     */
    public function setIpmiPassword($ipmiPassword)
    {
        $this->ipmiPassword = $ipmiPassword;
    }

    /**
     * Get serverTemperature
     *
     * @return ServerTemperature
     */
    public function getServerTemperature()
    {
        return $this->serverTemperature;
    }

    /**
     * Set serverTemperature
     *
     * @param ServerTemperature $serverTemperature
     *
     * @return Server
     */
    public function setServerTemperature(ServerTemperature $serverTemperature = null)
    {
        $this->serverTemperature = $serverTemperature;

        return $this;
    }

    /**
     * @Serializer\Groups({"select_list"})
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("itemName")
     */
    public function getNameForApi()
    {
        return $this->name;
    }
}
