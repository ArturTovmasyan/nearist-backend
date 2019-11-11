<?php

namespace AppBundle\Entity\Portal;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * BitstreamStatus
 *
 * @ORM\Table(name="bitstream_status")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\BitstreamStatusRepository")
 */
class BitstreamStatus
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Groups({"bitstream_status__list"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string")
     * @Assert\NotBlank()
     * @Serializer\Groups({"bitstream_status__list"})
     */
    private $title;

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
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return BitstreamStatus
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return null
     * @Serializer\SerializedName("status")
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"board_config__list"})
     */
    public function getStatusString()
    {
        return ['id' => $this->getId(), 'itemName' => $this->getTitle()];
    }
}
