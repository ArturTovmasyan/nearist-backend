<?php

namespace AppBundle\Entity\Portal;

use AppBundle\Model\Persistence\Entity\TimeAwareTrait;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ReservationData
 *
 * @ORM\Table(name="reservation_data")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ReservationDataRepository")
 */
class ReservationData
{
    use TimeAwareTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Assert\NotBlank()
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Reservation", inversedBy="data", cascade={"persist"}, fetch="LAZY")
     * @ORM\JoinColumn(name="id_reservation", referencedColumnName="id")
     */
    private $reservation;

    /**
     * @var int
     *
     * @ORM\Column(name="board", type="integer")
     * @Assert\NotBlank()
     * @Serializer\Groups({"reservation_list"})
     */
    private $board;

    /**
     * @var int
     *
     * @ORM\Column(name="lane", type="integer")
     * @Assert\NotBlank()
     * @Serializer\Groups({"reservation_list"})
     */
    private $lane;

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
     * Get reservation
     *
     * @return Reservation
     */
    public function getReservation()
    {
        return $this->reservation;
    }

    /**
     * Set reservation
     *
     * @param Reservation $reservation
     *
     * @return ReservationData
     */
    public function setReservation(Reservation $reservation = null)
    {
        $this->reservation = $reservation;

        return $this;
    }

    /**
     * Get board
     *
     * @return int
     */
    public function getBoard()
    {
        return $this->board;
    }

    /**
     * Set board
     *
     * @param integer $board
     *
     * @return ReservationData
     */
    public function setBoard($board)
    {
        $this->board = $board;

        return $this;
    }

    /**
     * Get lane
     *
     * @return int
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
     * @return ReservationData
     */
    public function setLane($lane)
    {
        $this->lane = $lane;

        return $this;
    }

}
