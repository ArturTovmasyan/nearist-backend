<?php

namespace AppBundle\Model\Persistence\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Groups;

/**
 * Trait HumanTrait
 * @package AppBundle\Model\Persistence\Entity
 */
trait HumanTrait
{
    /**
     * @var string $name
     * @ORM\Column(name="first_name", type="string", length=40, nullable=true)
     * @Assert\NotBlank(groups={"registration", "edit_user"})
     * @Groups({"user", "user_list"})
     */
    private $firstName;

    /**
     * @var string $name
     * @ORM\Column(name="last_name", type="string", length=40, nullable=true)
     * @Assert\NotBlank(groups={"registration", "edit_user", "edit_profile"})
     * @Groups({"user", "user_list", "edit_profile"})
     */
    private $lastName;

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName($firstName)
    {
        $firstName = preg_replace('/\s\s+/', ' ', $firstName);
        $this->firstName = $firstName;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName($lastName)
    {
        $lastName = preg_replace('/\s\s+/', ' ', $lastName);
        $this->lastName = $lastName;
    }

    /**
     * @return string
     */
    public function getFullName()
    {
        return $this->firstName . ' ' . $this->lastName;
    }
}