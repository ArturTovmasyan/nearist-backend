<?php

namespace AppBundle\Entity\Portal;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Reservation
 *
 * @ORM\Table(name="reservation")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ReservationRepository")
 */
class Reservation
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Groups({"reservation", "reservation_list"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"})
     * @ORM\JoinColumn(name="id_user", referencedColumnName="id")
     * @Serializer\Groups({"reservation", "reservation_list"})
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="Server", cascade={"persist"})
     * @ORM\JoinColumn(name="id_server", referencedColumnName="id")
     * @Serializer\Groups({"reservation", "reservation_list"})
     */
    private $server;

    /**
     * @ORM\ManyToOne(targetEntity="BoardConfig", cascade={"persist"})
     * @ORM\JoinColumn(name="id_board_config", referencedColumnName="id")
     * @Serializer\Groups({"reservation", "reservation_list"})
     */
    private $boardConfig;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_date", type="datetime")
     * @Assert\NotBlank()
     * @Serializer\Groups({"reservation", "reservation_list", "customer_server_list" })
     * @Serializer\Type("DateTime<'Y-m-d H:i:s'>")
     */
    private $startDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_date", type="datetime")
     * @Assert\NotBlank()
     * @Serializer\Groups({"reservation", "reservation_list", "customer_server_list"})
     * @Serializer\Type("DateTime<'Y-m-d H:i:s'>")
     */
    private $endDate;

    /**
     * @ORM\OneToMany(targetEntity="ReservationData", mappedBy="reservation", cascade={"persist", "remove"})
     */
    private $data;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->data = new ArrayCollection();
    }

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
     * Get startDate
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     *
     * @return Reservation
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     *
     * @return Reservation
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set user
     *
     * @param User $user
     *
     * @return Reservation
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

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
     * @return Reservation
     */
    public function setServer(Server $server = null)
    {
        $this->server = $server;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBoardConfig()
    {
        return $this->boardConfig;
    }

    /**
     * @param mixed $boardConfig
     * @return Reservation
     */
    public function setBoardConfig($boardConfig)
    {
        $this->boardConfig = $boardConfig;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("reservation_data")
     * @Serializer\Groups({"reservation"})
     */
    public function getReservationDetails()
    {
        $result = [];

        if ($this->data) {
            $result = $this->data->map(function (ReservationData $entry) {
                return $entry->getBoard() . '__' . $entry->getLane();
            });
        }

        return $result;
    }

    /**
     * @Serializer\VirtualProperty()
     * @Serializer\Type("array<string, array<string>>")
     * @Serializer\SerializedName("reservation_data")
     * @Serializer\Groups({"reservation_list"})
     */
    public function getReservationList()
    {
        $result = [];

        if ($this->data) {
            foreach ($this->data as $entry) {
                $result[$entry->getBoard()][] = $entry->getLane();
            }
        }

        return $result;
    }

}
