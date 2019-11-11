<?php

namespace AppBundle\Repository;

/**
 * ReservationRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ReservationRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * This function is used to return reserved servers by id, dates, board and lane
     *
     * @param $serverId
     * @param $editId
     * @param $boardDetails
     * @param $startDate
     * @param $endDate
     * @return int
     */
    public function findReservedServer($serverId, $editId = 0, $boardDetails, $startDate, $endDate)
    {
        $count = 0;
        foreach($boardDetails as $board_id => $lanes) {
            $query = $this
                ->createQueryBuilder('r')
                ->join('r.server', 's')
                ->join('r.data', 'rdata')
                ->where('NOT (((:startDate) < r.startDate AND (:endDate) < r.startDate) or ((:startDate) > r.endDate AND (:endDate) > r.endDate))')
                ->andWhere('s.id = (:serverId) AND r.id != (:editId)')
                ->andWhere('rdata.board = (:board) AND rdata.lane IN (:lanes)')
                ->setParameters([
                    'serverId' => $serverId,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'board' => $board_id,
                    'lanes' => $lanes,
                    'editId' => $editId
                ])
                ->getQuery()->getResult();

            $count += count($query);
        }
        return $count;
    }

    /**
     * This function is used to return calendar events data
     *
     * @return array
     */
    public function findCalendarEvents()
    {
        $result = $this
            ->createQueryBuilder('r')
            ->select('r.id AS reservation_id, r.startDate, r.endDate, s.name AS server_name, u.firstName, u.lastName')
            ->leftJoin('r.server', 's')
            ->leftJoin('r.user', 'u')
            ->getQuery()->getResult();

        return $result;
    }

    /**
     * @param $customerId
     * @return array
     */
    public function findByCustomerId($customerId)
    {
        return $this
            ->createQueryBuilder('r')
            ->select('s.id, s.name, r.startDate, r.endDate')
            ->innerJoin('r.server', 's')
            ->innerJoin('r.user', 'u')
            ->where('r.user = (:customerId)')
            ->orderBy('r.endDate', 'DESC')
            ->setParameter('customerId', $customerId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $customerId
     * @param $serverId
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByCustomerIdAndServerId($customerId, $serverId)
    {
        return $this
            ->createQueryBuilder('r')
            ->select('r.id, r.startDate, r.endDate')
            ->where('r.user = (:customerId) AND r.server = (:serverId)')
            ->orderBy('r.endDate', 'DESC')
            ->setParameter('customerId', $customerId)
            ->setParameter('serverId', $serverId)
            ->getQuery()
            ->getSingleResult();
    }
}