<?php

namespace AppBundle\Entity\Portal;

use AppBundle\Model\Bitstream\BoardTypes;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * BoardConfig
 *
 * @ORM\Table(name="board_config")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\BoardConfigRepository")
 */
class BoardConfig
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Groups({"board_config__list"})
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="board_type", type="smallint")
     * @Assert\NotBlank()
     */
    private $boardType;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255)
     * @Assert\NotBlank()
     * @Serializer\Groups({"board_config__list"})
     */
    private $description;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Bitstream", mappedBy="boardConfig", cascade={"persist", "remove"})
     * @Serializer\Groups({"board_config__list"})
     */
    private $bitstreams;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime")
     * @Serializer\Groups({"board_config__list"})
     * @Serializer\Type("DateTime<'Y-m-d H:i:s'>")
     */
    private $date;

    /**
     * @var BitstreamStatus
     * @ORM\ManyToOne(targetEntity="BitstreamStatus", cascade={"persist"}, fetch="LAZY")
     * @ORM\JoinColumn(name="id_status", referencedColumnName="id")
     */
    private $status;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->bitstreams = new \Doctrine\Common\Collections\ArrayCollection();
        $this->date = new \DateTime();
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
     * @return int
     */
    public function getBoardType()
    {
        return $this->boardType;
    }

    /**
     * @param int $boardType
     */
    public function setBoardType($boardType)
    {
        $this->boardType = $boardType;
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
     * @return ArrayCollection
     */
    public function getBitstreams()
    {
        return $this->bitstreams;
    }

    /**
     * @param ArrayCollection $bitstreams
     */
    public function setBitstreams($bitstreams)
    {
        $this->bitstreams = $bitstreams;
    }

    /**
     * @return null
     * @Serializer\SerializedName("board_type")
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"board_config__list"})
     */
    public function getBoardTypeString()
    {
        $boardType = $this->boardType;
        $boardTypeData = [];

        //get static roles data
        $modelBoardType = BoardTypes::getList();
        $modelBoardTypeAssoc = BoardTypes::getNamesList();

        if (in_array($boardType, $modelBoardType)) {

            $id = $modelBoardType[$boardType - 1];
            $boardTypeData[] = ['id' => $id, 'itemName' => $modelBoardTypeAssoc[$boardType]];

            return $boardTypeData;
        }

        return null;
    }

    /**
     * @return array
     * @Serializer\SerializedName("data")
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"reservation", "reservation_list"})
     */
    public function getBoardListString()
    {
        return ['id' => $this->id, 'itemName' => BoardTypes::getName($this->boardType) . ": (" . $this->description . ")"];
    }

    /**
     * Add bitstream
     *
     * @param Bitstream $bitstream
     *
     * @return BoardConfig
     */
    public function addBitstream(Bitstream $bitstream)
    {
        $this->bitstreams[] = $bitstream;

        return $this;
    }

    /**
     * Remove bitstream
     *
     * @param Bitstream $bitstream
     */
    public function removeBitstream(Bitstream $bitstream)
    {
        $this->bitstreams->removeElement($bitstream);
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return null
     * @Serializer\SerializedName("status")
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"board_config__list"})
     */
    public function getStatusString()
    {
        if ($this->status) {
            $data[] = ['id' => $this->status->getId(), 'itemName' => $this->status->getTitle()];
            return $data;
        }

        return null;
    }

}
