<?php

namespace AppBundle\Repository;


/**
 * Class TemperatureLogRepository
 * @package AppBundle\Repository
 */
class TemperatureLogRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @param $id
     * @param $limit
     * @param $offset
     * @return mixed
     */
    public function findByServerId($id, $limit, $offset)
    {
        return $this
            ->createQueryBuilder('tl')
            ->leftJoin('tl.server', 's')
            ->where('s.id = (:id)')
            ->setParameter('id', $id)
            ->orderBy('tl.dateTime', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult(($offset - 1) * $limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $id
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countByServerId($id)
    {
        return $this
            ->createQueryBuilder('tl')
            ->leftJoin('tl.server', 's')
            ->where('s.id = (:id)')
            ->setParameter('id', $id)
            ->select('count(tl.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param $id
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLastRecords()
    {
        return $this
            ->createQueryBuilder('tl')
            ->orderBy('tl.dateTime', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
    }
}
