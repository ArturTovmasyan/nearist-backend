<?php

namespace AppBundle\Repository;

/**
 * ServerTemperatureRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ServerTemperatureRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @param $id
     * @return array
     */
    public function findByServerId($id)
    {
        return $this
            ->createQueryBuilder('st')
            ->leftJoin('st.server', 's')
            ->where('s.id = (:id)')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
