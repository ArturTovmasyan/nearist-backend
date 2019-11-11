<?php

namespace AppBundle\Entity\Portal;

use AppBundle\Model\Persistence\Entity\TimeAwareTrait;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Country
 *
 * @ORM\Table(name="country")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CountryRepository")
 */
class Country
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Groups({"country_list", "user", "add_user", "edit_user", "edit_profile"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="iso", type="string", length=3)
     * @Assert\NotBlank()
     * @Serializer\Groups({"country_list"})
     */
    private $iso;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=150)
     * @Assert\NotBlank()
     * @Serializer\Groups({"country_list", "user", "add_user", "edit_user", "edit_profile"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="phone_code", type="string", length=11)
     * @Assert\NotBlank()
     * @Serializer\Groups({"country_list"})
     */
    private $phoneCode;

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
     * @return string
     */
    public function getIso(): string
    {
        return $this->iso;
    }

    /**
     * @param string $iso
     */
    public function setIso(string $iso): void
    {
        $this->iso = $iso;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getPhoneCode(): string
    {
        return $this->phoneCode;
    }

    /**
     * @param string $phoneCode
     */
    public function setPhoneCode(string $phoneCode): void
    {
        $this->phoneCode = $phoneCode;
    }
}
