<?php

namespace AppBundle\Entity\Portal;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Bitstream
 *
 * @ORM\Table(name="bitstream")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\BitstreamRepository")
 */
class Bitstream
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Groups({"board_config__list", "bitstream__list"})
     */
    private $id;

    /**
     * @var BoardConfig
     *
     * @ORM\ManyToOne(targetEntity="BoardConfig", inversedBy="bitstreams")
     * @ORM\JoinColumn(name="id_board_config", referencedColumnName="id")
     * @Serializer\Groups({"bitstream__list"})
     */
    private $boardConfig;

    /**
     * @var int
     *
     * @ORM\Column(name="device", type="smallint")
     * @Assert\NotBlank()
     * @Serializer\Groups({"board_config__list", "bitstream__list"})
     */
    private $device;

    /**
     * @var int
     *
     * @ORM\Column(name="file_type", type="smallint")
     * @Assert\NotBlank()
     * @Serializer\Groups({"board_config__list", "bitstream__list"})
     */
    private $fileType;

    /**
     * @var string
     *
     * @ORM\Column(name="file_name", type="string")
     * @Serializer\Groups({"board_config__list", "bitstream__list"})
     */
    private $fileName;

    /**
     * @var string
     *
     * @ORM\Column(name="file", type="text")
     * @Serializer\Groups({"bitstream__list"})
     */
    private $file;

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
     * @return BoardConfig
     */
    public function getBoardConfig()
    {
        return $this->boardConfig;
    }

    /**
     * @param BoardConfig $boardConfig
     *
     * @return Bitstream
     */
    public function setBoardConfig($boardConfig)
    {
        $this->boardConfig = $boardConfig;

        return $this;
    }

    /**
     * @return int
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * @param int $device
     *
     * @return Bitstream
     */
    public function setDevice($device)
    {
        $this->device = $device;

        return $this;
    }

    /**
     * @return int
     */
    public function getFileType()
    {
        return $this->fileType;
    }

    /**
     * @param int $fileType
     *
     * @return Bitstream
     */
    public function setFileType($fileType)
    {
        $this->fileType = $fileType;

        return $this;
    }

    /**
     * Get fileName
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Set fileName
     *
     * @param string $fileName
     *
     * @return Bitstream
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * Get file
     *
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set file
     *
     * @param string $file
     *
     * @return Bitstream
     */
    public function setFile($file)
    {
        $this->file = $file;

        return $this;
    }

}
