<?php

namespace AppBundle\Entity\Portal;

use AppBundle\Model\Persistence\Entity\TimeAwareTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ServerTemperature
 *
 * @ORM\Table(name="server_temperature")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ServerTemperatureRepository")
 */
class ServerTemperature
{
    use TimeAwareTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="log_rate", type="integer")
     * @Assert\NotBlank()
     */
    private $logRate;

    /**
     * @var int
     *
     * @ORM\Column(name="event_temperature", type="integer")
     * @Assert\NotBlank()
     */
    private $eventTemperature;

    /**
     * @var int
     *
     * @ORM\Column(name="event_duration", type="integer")
     * @Assert\NotBlank()
     */
    private $eventDuration;

    /**
     * @var string
     *
     * @ORM\Column(name="emails", type="string")
     * @Assert\NotBlank()
     */
    private $emails;

    /**
     * @ORM\OneToOne(targetEntity="Server", inversedBy="serverTemperature")
     * @ORM\JoinColumn(name="server_id", referencedColumnName="id")
     */
    private $server;

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
     * Get logRate
     *
     * @return int
     */
    public function getLogRate()
    {
        return $this->logRate;
    }

    /**
     * Set logRate
     *
     * @param integer $logRate
     *
     * @return ServerTemperature
     */
    public function setLogRate($logRate)
    {
        $this->logRate = $logRate;

        return $this;
    }

    /**
     * Get eventTemperature
     *
     * @return int
     */
    public function getEventTemperature()
    {
        return $this->eventTemperature;
    }

    /**
     * Set eventTemperature
     *
     * @param integer $eventTemperature
     *
     * @return ServerTemperature
     */
    public function setEventTemperature($eventTemperature)
    {
        $this->eventTemperature = $eventTemperature;

        return $this;
    }

    /**
     * Get eventDuration
     *
     * @return int
     */
    public function getEventDuration()
    {
        return $this->eventDuration;
    }

    /**
     * Set eventDuration
     *
     * @param integer $eventDuration
     *
     * @return ServerTemperature
     */
    public function setEventDuration($eventDuration)
    {
        $this->eventDuration = $eventDuration;

        return $this;
    }

    /**
     * Get server
     *
     * @return Server
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * Set server
     *
     * @param Server $server
     *
     * @return ServerTemperature
     */
    public function setServer(Server $server = null)
    {
        $this->server = $server;

        return $this;
    }

    /**
     * Get emails
     *
     * @return string
     */
    public function getEmails()
    {
        return $this->emails;
    }

    /**
     * Set emails
     *
     * @param string $emails
     *
     * @return ServerTemperature
     */
    public function setEmails($emails)
    {
        $this->emails = $emails;

        return $this;
    }
}
