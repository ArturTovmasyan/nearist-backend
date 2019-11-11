<?php

namespace AppBundle\Model\Persistence\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use AppBundle\Entity\Portal\User;

trait UserAwareTrait
{
    /**
     * @var User
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Portal\User")
     * @ORM\JoinColumn(name="id_created_user", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private $createdBy;

    /**
     * @var User
     * @Gedmo\Blameable(on="update")
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Portal\User")
     * @ORM\JoinColumn(name="id_updated_user", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private $updatedBy;

    /** ======================================================== **
     * Getters, setters.
     ** ======================================================== **/

    /**
     * @return User
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * @param User $createdBy
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;
    }

    /**
     * @return User
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    /**
     * @param User $updatedBy
     */
    public function setUpdatedBy($updatedBy)
    {
        $this->updatedBy = $updatedBy;
    }
}
