<?php

namespace AppBundle\Entity\Portal;

use AppBundle\Model\Persistence\Entity\TimeAwareTrait;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class TemperatureLog
 * @package AppBundle\Entity\Portal
 *
 * @ORM\Table(name="temperature_logs")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\TemperatureLogRepository")
 */
class TemperatureLog
{
    use TimeAwareTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Groups({"temp_log", "temp_log_list"})
     */
    private $id;

    /**
     * @var \Datetime
     * @ORM\Column(type="datetime", name="date_time")
     * @Assert\NotBlank()
     * @Serializer\Groups({"temp_log", "temp_log_list"})
     * @Serializer\Type("DateTime<'Y-m-d H:i:s'>")
     */
    private $dateTime;

    /**
     * @var int
     *
     * @ORM\Column(name="board", type="integer")
     * @Assert\NotBlank()
     * @Serializer\Groups({"temp_log", "temp_log_list"})
     */
    private $board;

    /**
     * @var int
     *
     * @ORM\Column(name="lane", type="integer")
     * @Assert\NotBlank()
     * @Serializer\Groups({"temp_log", "temp_log_list"})
     */
    private $lane;


    /**
     * @ORM\ManyToOne(targetEntity="Server")
     * @ORM\JoinColumn(name="server_id", referencedColumnName="id")
     * @Assert\NotBlank()
     * @Serializer\Groups({"temp_log"})
     */
    private $server;

    /**
     * @var int
     *
     * @ORM\Column(name="temperature", type="integer", nullable=true)
     * @Assert\NotBlank()
     * @Serializer\Groups({"temp_log", "temp_log_list"})
     */
    private $temperature;

    /**
     * @var string
     *
     * @ORM\Column(name="temperature_codes", type="string", length=50)
     * @Assert\NotBlank()
     * @Serializer\Groups({"temp_log", "temp_log_list"})
     */
    private $temperatureCodes;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return \Datetime
     */
    public function getDateTime(): \Datetime
    {
        return $this->dateTime;
    }

    /**
     * @param \Datetime $dateTime
     */
    public function setDateTime(\Datetime $dateTime): void
    {
        $this->dateTime = $dateTime;
    }

    /**
     * @return int
     */
    public function getBoard(): int
    {
        return $this->board;
    }

    /**
     * @param int $board
     */
    public function setBoard(int $board): void
    {
        $this->board = $board;
    }

    /**
     * @return int
     */
    public function getLane(): int
    {
        return $this->lane;
    }

    /**
     * @param int $lane
     */
    public function setLane(int $lane): void
    {
        $this->lane = $lane;
    }

    /**
     * @return mixed
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @param mixed $server
     */
    public function setServer($server): void
    {
        $this->server = $server;
    }

    /**
     * @return int
     */
    public function getTemperature(): int
    {
        return $this->temperature;
    }

    /**
     * @param int $temperature
     */
    public function setTemperature(int $temperature): void
    {
        $this->temperature = $temperature;
    }

    /**
     * @return string
     */
    public function getTemperatureCodes(): string
    {
        return $this->temperatureCodes;
    }

    /**
     * @param string $temperatureCodes
     */
    public function setTemperatureCodes(string $temperatureCodes): void
    {
        $this->temperatureCodes = $temperatureCodes;
    }
}
