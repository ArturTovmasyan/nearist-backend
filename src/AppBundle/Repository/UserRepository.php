<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Class UserRepository
 * @package AppBundle\Repository
 */
class UserRepository extends EntityRepository
{
    /**
     * @param $roleName
     * @return array
     */
    public function findWithRole($roleName)
    {
        return $this
            ->createQueryBuilder('user')
            ->where("user.roles LIKE '%{$roleName}%'")
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $category
     * @return array
     */
    public function findByCategory($category)
    {
        $qb = $this->createQueryBuilder('user');

        if ($category == 1) {
            $qb->andWhere("user.roles LIKE '%ROLE_CUSTOMER%'");
        } elseif($category == 2) {
            $qb->andWhere("user.roles NOT LIKE '%ROLE_CUSTOMER%'");
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param $username
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByUsernameOrEmail($username)
    {
        return $this
            ->createQueryBuilder('user')
            ->andWhere('user.username = :username OR user.email = :username')
            ->setParameter('username', $username)
            ->getQuery()
            ->getOneOrNullResult();
    }
}