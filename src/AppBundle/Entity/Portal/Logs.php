<?php

namespace AppBundle\Entity\Portal;

use AppBundle\Model\Log\LogType;
use AppBundle\Model\Persistence\Entity\TimeAwareTrait;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Logs
 *
 * @ORM\Table(name="logs")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\LogsRepository")
 */
class Logs
{
    use TimeAwareTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Groups({"temp_log", "customer_log_list"})
     */
    private $id;

    /**
     * @var \Datetime
     * @ORM\Column(type="datetime", name="date_time")
     * @Assert\NotBlank()
     * @Serializer\Groups({"temp_log", "customer_log_list"})
     * @Serializer\Type("DateTime<'Y-m-d H:i:s'>")
     */
    private $dateTime;

    /**
     * @ORM\ManyToOne(targetEntity="Server")
     * @ORM\JoinColumn(name="server_id", referencedColumnName="id")
     * @Serializer\Groups({"customer_log_list"})
     */
    private $server;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="smallint")
     * @Assert\NotBlank()
     * @Serializer\Groups({"temp_log"})
     */
    private $type = 0;

    /**
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(name="board", type="string", length=50, nullable=true)
     * @Assert\NotBlank()
     * @Serializer\Groups({"temp_log", "customer_log_list"})
     */
    private $board;

    /**
     * @var integer
     *
     * @ORM\Column(name="dtr", type="integer", nullable=true)
     * @Assert\NotBlank()
     * @Serializer\Groups({"temp_log", "customer_log_list"})
     */
    private $dtr;

    /**
     * @var integer
     *
     * @ORM\Column(name="lane", type="integer", nullable=true)
     * @Assert\NotBlank()
     * @Serializer\Groups({"temp_log", "customer_log_list"})
     */
    private $lane;

    /**
     * @var int
     *
     * @ORM\Column(name="temperature", type="integer", nullable=true)
     * @Assert\NotBlank()
     * @Serializer\Groups({"temp_log", "customer_log_list"})
     */
    private $temperature;

    /**
     * @ORM\Column(name="level", type="integer", nullable=true)
     * @Serializer\Groups({"temp_log", "customer_log_list"})
     */
    private $level;

    /**
     * @ORM\Column(name="memory", type="float", nullable=true)
     * @Serializer\Groups({"temp_log", "customer_log_list"})
     */
    private $memory;

    /**
     * @ORM\Column(name="cpu", type="float", nullable=true)
     * @Serializer\Groups({"temp_log", "customer_log_list"})
     */
    private $cpu;

    /**
     * @ORM\Column(name="message", type="string", nullable=true)
     * @Serializer\Groups({"temp_log", "customer_log_list"})
     */
    private $message;

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
     * Get board
     *
     * @return string
     */
    public function getBoard()
    {
        return $this->board;
    }

    /**
     * Set board
     *
     * @param string $board
     *
     * @return Logs
     */
    public function setBoard($board)
    {
        $this->board = $board;

        return $this;
    }

    /**
     * Get dtr
     *
     * @return string
     */
    public function getDtr()
    {
        return $this->dtr;
    }

    /**
     * Set dtr
     *
     * @param string $dtr
     *
     * @return Logs
     */
    public function setDtr($dtr)
    {
        $this->dtr = $dtr;

        return $this;
    }

    /**
     * Get temperature
     *
     * @return int
     */
    public function getTemperature()
    {
        return $this->temperature;
    }

    /**
     * Set temperature
     *
     * @param integer $temperature
     *
     * @return Logs
     */
    public function setTemperature($temperature)
    {
        $this->temperature = $temperature;

        return $this;
    }

    /**
     * Get lane
     *
     * @return integer
     */
    public function getLane()
    {
        return $this->lane;
    }

    /**
     * Set lane
     *
     * @param integer $lane
     *
     * @return Logs
     */
    public function setLane($lane)
    {
        $this->lane = $lane;

        return $this;
    }

    /**
     * Get dateTime
     *
     * @return \DateTime
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * Set dateTime
     *
     * @param \DateTime $dateTime
     *
     * @return Logs
     */
    public function setDateTime($dateTime)
    {
        $this->dateTime = $dateTime;

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
     * @return Logs
     */
    public function setServer(Server $server = null)
    {
        $this->server = $server;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set type
     *
     * @param integer $type
     *
     * @return Logs
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     * @return Logs
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Get level
     *
     * @return integer
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Set level
     *
     * @param integer $level
     *
     * @return Logs
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set message
     *
     * @param string $message
     *
     * @return Logs
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get memory
     *
     * @return integer
     */
    public function getMemory()
    {
        return $this->memory;
    }

    /**
     * Set memory
     *
     * @param integer $memory
     *
     * @return Logs
     */
    public function setMemory($memory)
    {
        $this->memory = $memory;

        return $this;
    }

    /**
     * Get cpu
     *
     * @return integer
     */
    public function getCpu()
    {
        return $this->cpu;
    }

    /**
     * Set cpu
     *
     * @param integer $cpu
     *
     * @return Logs
     */
    public function setCpu($cpu)
    {
        $this->cpu = $cpu;

        return $this;
    }

    /**
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("title")
     * @Serializer\Groups({"customer_log_list"})
     */
    public function getLogTitle()
    {
        switch ($this->type) {
            case LogType::AUTH:
                $title = 'Authentication';
                break;
            case LogType::POWER:
                $title = $this->getServer()->getName() . ' Power';
                break;
            case LogType::LOGOUT:
                $title = 'Logout';
                break;
            default:
                $title = 'Unrecognized';
        }

        return $title;
    }

    /**
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("icon")
     * @Serializer\Groups({"customer_log_list"})
     */
    public function getLogIcon()
    {
        return LogType::LOG_TYPE_ICONS[$this->type];
    }

    /**
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("color")
     * @Serializer\Groups({"customer_log_list"})
     */
    public function getLogColor()
    {
        return LogType::LOG_TYPE_ICON_COLOR[$this->type];
    }
}
