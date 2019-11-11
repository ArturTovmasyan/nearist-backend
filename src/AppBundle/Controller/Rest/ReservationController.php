<?php

namespace AppBundle\Controller\Rest;

use AppBundle\Entity\Portal\BoardConfig;
use AppBundle\Entity\Portal\Reservation;
use AppBundle\Entity\Portal\ReservationData;
use AppBundle\Util\Mailer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class ServerController
 * @package AppBundle\Controller\Rest
 * @Route("/api/v1.0/reservation")
 */
class ReservationController extends Controller
{
    /**
     * This function is used to get reservation list
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Reservation",
     *  description="This function is used to get reservation list",
     *  statusCodes={
     *         200="Returned when get user list",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("GET")
     * @Route("/list", name="reservation_list")
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @return array | JsonResponse
     */
    public function listAction()
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var ArrayCollection $reservation */
        $reservation = $em->getRepository("AppBundle:Reservation")->findAll();

        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');
        $data = ['status' => JsonResponse::HTTP_OK, 'message' => 'Success', 'data' => $reservation];
        $usersContent = $serializer->serialize($data, 'json', SerializationContext::create()->setGroups(['reservation_list']));

        $response = new JsonResponse();
        $response->setContent($usersContent);
        
        return $response;
    }

    /**
     * This function is used to add new reservation
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Reservation",
     *  description="This function is used to add new reservation",
     *  statusCodes={
     *         200="Returned when add new reservation",
     *         403="Forbidden",
     *         400="Bad request"
     *     },
     *     parameters={
     *          {"name"="user", "dataType"="integer", "required"=true, "description"="Reservation user"},
     *          {"name"="server", "dataType"="integer", "required"=true, "description"="Reservation server"},
     *          {"name"="board_configuration", "dataType"="array", "required"=true, "description"="Reservation board_configuration"},
     *          {"name"="board_data", "dataType"="array", "required"=true, "description"="Reservation board_data"},
     *          {"name"="start_date", "dataType"="datetime", "required"=true, "description"="Reservation start date"},
     *          {"name"="end_date", "dataType"="datetime", "required"=true, "description"="Reservation end date"},
     *     }
     * )
     *
     * @Method("POST")
     * @Route("/add", name="reservation_add")
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param Request $request
     * @return array | JsonResponse
     * @throws
     */
    public function addAction(Request $request)
    {
        if ($request->getContentType() === 'application/json' || $request->getContentType() === 'json') {
            $content = $request->getContent();
            $request->request->add(json_decode($content, true));
        }

        //get POST data in request
        $userId = $request->get('user');
        $serverId = $request->get('server');
        $boardDetails = $request->get('board_data');
        $boardConfiguration = $request->get('board_configuration');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $user = $em->find('AppBundle\\Entity\\Portal\\User', $userId[0]['id']);
        $server = $em->find('AppBundle\\Entity\\Portal\\Server', $serverId[0]['id']);
        $boardConfig = $em->find('AppBundle\\Entity\\Portal\\BoardConfig', $boardConfiguration[0]['id']);
        $boardDataArray = [];

        foreach ($boardDetails as $detail) {
            $explodeData = explode('__', $detail);
            $boardDataArray[$explodeData[0]][] = $explodeData[1];
        }

        $boardDetails = $boardDataArray;
        $reservedServer = $em->getRepository('AppBundle:Reservation')->findReservedServer($serverId[0]['id'], 0, $boardDetails, $startDate, $endDate);

        if ($reservedServer > 0) {
            return new JsonResponse(['status' => JsonResponse::HTTP_BAD_REQUEST, 'message' => 'Invalid data.',
                'data' => ['errors' => ['reserved_server' => 'Server by selected data is reserved.']]],
                JsonResponse::HTTP_BAD_REQUEST);
        }

        /** @var Reservation $reservation */
        $reservation = new Reservation();
        $reservation->setServer($server);
        $reservation->setUser($user);
        $reservation->setBoardConfig($boardConfig);
        $reservation->setStartDate(new \DateTime($startDate));
        $reservation->setEndDate(new \DateTime($endDate));

        foreach ($boardDetails as $board_id => $lane_data) {
            foreach ($lane_data as $lane_id) {
                $boardData = new ReservationData();
                $boardData->setReservation($reservation);
                $boardData->setBoard($board_id);
                $boardData->setLane($lane_id);
                $em->persist($boardData);
            }
        }

        /** @var ValidatorInterface $validator */
        $validator = $this->get('validator');
        $errors = $validator->validate($reservation, null, []);
        $returnErrors = [];

        if ($errors->count() > 0) {

            foreach ($errors as $error) {
                $returnErrors[$error->getPropertyPath()] = $error->getMessage();
            }

            $status = JsonResponse::HTTP_BAD_REQUEST;
            $response = ['status' => $status, 'message' => 'Invalid data', 'data' => ['errors' => $returnErrors]];
        } else {

            $em->getConnection()->beginTransaction();
            try {
                $em->persist($reservation);
                $em->flush();
                $em->getConnection()->commit();

                $mailer = new Mailer($this->container);
                $mailer->notifyReservation($reservation);

                $status = JsonResponse::HTTP_OK;
                $response = ['status' => JsonResponse::HTTP_OK, 'message' => 'Success'];
            } catch (\Exception $e) {
                $em->getConnection()->rollBack();
                $status = JsonResponse::HTTP_BAD_REQUEST;
                $response = ['status' => $e->getCode(), 'message' => $e->getMessage()];
            }
        }

        return new JsonResponse($response, $status);
    }

    /**
     * This function is used to edit reservation
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Reservation",
     *  description="This function is used to edit reservation",
     *  statusCodes={
     *         200="Returned when edit",
     *         403="Forbidden",
     *         400="Bad request"
     *     },
     *     parameters={
     *      {"name"="user", "dataType"="integer", "required"=true, "description"="Reservation user"},
     *      {"name"="server", "dataType"="integer", "required"=true, "description"="Reservation server"},
     *      {"name"="board_configuration", "dataType"="array", "required"=true, "description"="Reservation board_configuration"},
     *      {"name"="board_data", "dataType"="array", "required"=true, "description"="Reservation board_data"},
     *      {"name"="start_date", "dataType"="datetime", "required"=true, "description"="Reservation start date"},
     *      {"name"="end_date", "dataType"="datetime", "required"=true, "description"="Reservation end date"},
     * }
     * )
     *
     * @Method("PUT")
     * @Route("/edit/{id}", name="reservation_edit", requirements={"id"="\d+"})
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param $id
     * @return array | JsonResponse
     * @throws
     */
    public function editAction($id, Request $request)
    {
        if ($request->getContentType() === 'application/json' || $request->getContentType() === 'json') {
            $content = $request->getContent();
            $request->request->add(json_decode($content, true));
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var Reservation $reservation */
        $reservation = $em->getRepository("AppBundle:Reservation")->find($id);

        if ($reservation) {
            $userId = $request->get('user');
            $serverId = $request->get('server');
            $boardDetails = $request->get('board_data');
            $boardConfiguration = $request->get('board_configuration');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            /** @var EntityManager $em */
            $em = $this->getDoctrine()->getManager();
            $user = $em->find('AppBundle\\Entity\\Portal\\User', $userId[0]['id']);
            $server = $em->find('AppBundle\\Entity\\Portal\\Server', $serverId[0]['id']);
            $boardConfig = $em->find('AppBundle\\Entity\\Portal\\BoardConfig', $boardConfiguration[0]['id']);
            $boardDataArray = [];

            foreach ($boardDetails as $detail) {
                $explodeData = explode('__', $detail);
                $boardDataArray[$explodeData[0]][] = $explodeData[1];
            }

            $boardDetails = $boardDataArray;
            $reservedServer = $em->getRepository('AppBundle:Reservation')->findReservedServer($serverId[0]['id'], $id, $boardDetails, $startDate, $endDate);

            if ($reservedServer > 0) {
                return new JsonResponse(['status' => JsonResponse::HTTP_BAD_REQUEST, 'message' => 'Invalid data.',
                    'data' => ['errors' => ['reserved_server' => 'Server by selected data is reserved.']]],
                    JsonResponse::HTTP_BAD_REQUEST);
            }

            $reservation->setServer($server);
            $reservation->setUser($user);
            $reservation->setBoardConfig($boardConfig);
            $reservation->setStartDate(new \DateTime($startDate));
            $reservation->setEndDate(new \DateTime($endDate));
            $oldData = $reservation->getData();

            // TODO: Review transaction
            if (count($oldData) > 0) {
                foreach ($oldData as $data) {
                    $em->remove($data);
                }
                $em->flush();
            }

            foreach ($boardDetails as $board_id => $lane_data) {
                foreach ($lane_data as $lane_id) {
                    $boardData = new ReservationData();
                    $boardData->setReservation($reservation);
                    $boardData->setBoard($board_id);
                    $boardData->setLane($lane_id);
                    $em->persist($boardData);
                }
            }

            /** @var ValidatorInterface $validator */
            $validator = $this->get('validator');
            $errors = $validator->validate($reservation, null, []);
            $returnErrors = [];

            if ($errors->count() > 0) {
                foreach ($errors as $error) {
                    $returnErrors[$error->getPropertyPath()] = $error->getMessage();
                }
                $status = JsonResponse::HTTP_BAD_REQUEST;
                $response = ['status' => $status, 'message' => 'Invalid data', 'data' => ['errors' => $returnErrors]];
            } else {
                /** @var EntityManager $em */
                $em = $this->getDoctrine()->getManager();
                $em->getConnection()->beginTransaction();
                try {
                    $em->persist($reservation);
                    $em->flush();
                    $em->getConnection()->commit();

                    $mailer = new Mailer($this->container);
                    $mailer->notifyReservation($reservation);

                    $status = JsonResponse::HTTP_OK;
                    $response = ['status' => JsonResponse::HTTP_OK, 'message' => 'Success'];
                } catch (\Exception $e) {
                    $em->getConnection()->rollBack();
                    $status = JsonResponse::HTTP_BAD_REQUEST;
                    $response = ['status' => $e->getCode(), 'message' => $e->getMessage()];
                }
            }
        } else {
            $status = JsonResponse::HTTP_NOT_FOUND;
            $response = ['status' => JsonResponse::HTTP_NOT_FOUND, 'message' => "Server by id` $id not found"];
        }

        return new JsonResponse($response, $status);
    }

    /**
     * This function is used to remove reservation by id
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Reservation",
     *  statusCodes={
     *         200="Returned when user was removed",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("DELETE")
     * @Route("/remove/{id}", name="reservation_delete", requirements={"id"="\d+"})
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param $id
     * @return array | JsonResponse
     * @throws
     */
    public function removeAction($id)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var Reservation $users */
        $reservation = $em->getRepository("AppBundle:Reservation")->find($id);

        if ($reservation) {
            $em->getConnection()->beginTransaction();
            try {
                $em->remove($reservation);
                $em->flush();
                $em->getConnection()->commit();
                $status = JsonResponse::HTTP_OK;
                $response = ['status' => JsonResponse::HTTP_OK, 'message' => "Reservation removed"];
            } catch (\Exception $e) {
                $em->getConnection()->rollBack();
                $status = JsonResponse::HTTP_BAD_REQUEST;
                $response = ['status' => $e->getCode(), 'message' => $e->getMessage()];
            }
        } else {
            $status = JsonResponse::HTTP_NOT_FOUND;
            $response = ['status' => JsonResponse::HTTP_NOT_FOUND, 'message' => "Reservation by id` $id not found"];
        }

        return new JsonResponse($response, $status);
    }

    /**
     * This function is used to get reservation by id
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Reservation",
     *  description="This function is used to get reservation by id",
     *  statusCodes={
     *         200="Success",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("GET")
     * @Route("/{id}", name="get_reservation", requirements={"id"="\d+"})
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param $id
     * @return array | JsonResponse
     */
    public function getReservationAction($id)
    {
        if (!$id) {
            return new JsonResponse(['status' => JsonResponse::HTTP_BAD_REQUEST, 'message' => 'Id param is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var ArrayCollection $reservation */
        $reservation = $em->getRepository("AppBundle:Reservation")->find($id);

        if (!$reservation) {
            return new JsonResponse(['status' => JsonResponse::HTTP_NOT_FOUND, 'message' => "reservation by id=$id not found"], JsonResponse::HTTP_NOT_FOUND);
        }

        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        $data = ['status' => JsonResponse::HTTP_OK, 'message' => 'Success', 'data' => $reservation];
        $usersContent = $serializer->serialize($data, 'json', SerializationContext::create()->setGroups(['reservation']));

        $response = new JsonResponse();
        $response->setContent($usersContent);

        return $response;
    }

    /**
     * This function is used to get reservation selects field list data
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Reservation",
     *  description="This function is used to get reservation selects field list data",
     *  statusCodes={
     *         200="Success",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("GET")
     * @Route("/select-data/{id}", name="get_select_list")
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param $id
     * @return array | JsonResponse
     */
    public function getSelectListDataAction($id = null)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var ArrayCollection $users */
        $users = $em->getRepository("AppBundle:User")->findAll();

        /** @var ArrayCollection $servers */
        $servers = $em->getRepository("AppBundle:Server")->findAll();

        $boardConfigs    = $em->getRepository("AppBundle:BoardConfig")->findAll();
        $boardConfigList = [];

        /** @var BoardConfig $boardConfig */
        foreach ($boardConfigs as $boardConfig) {
            $boardConfigList[] = $boardConfig->getBoardListString();
        }

        //TODO will be change, after it can add id required param
        $boardList = [
            ["id" => 0, "itemName" => '00'],
            ["id" => 1, "itemName" => '01'],
            ["id" => 2, "itemName" => '02'],
            ["id" => 3, "itemName" => '03'],
            ["id" => 4, "itemName" => '04'],
            ["id" => 5, "itemName" => '05'],
            ["id" => 6, "itemName" => '06'],
            ["id" => 7, "itemName" => '07'],
            ["id" => 8, "itemName" => '08'],
            ["id" => 9, "itemName" => '09']
        ];

        $laneList = [
            ["id" => 0, "itemName" => '00'],
            ["id" => 1, "itemName" => '01'],
            ["id" => 2, "itemName" => '02']
        ];

        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');
        $data = [
            'status' => JsonResponse::HTTP_OK,
            'message' => 'Success',
            'data' => [
                'users'             => $users,
                'servers'           => $servers,
                'board_config_list' => $boardConfigList,
                'board_list'        => $boardList,
                'lane_list'         => $laneList
            ]
        ];

        $usersContent = $serializer->serialize($data, 'json', SerializationContext::create()->setGroups(['select_list']));

        $response = new JsonResponse();
        $response->setContent($usersContent);

        return $response;
    }

    /**
     * This function is used to get reservation events
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Reservation",
     *  description="This function is used to get reservation events",
     *  statusCodes={
     *         200="Success",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("GET")
     * @Route("/events", name="get_calendar_events")
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @return array | JsonResponse
     */
    public function getCalendarEventsAction()
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var ArrayCollection $users */
        $calendarEvents = $em->getRepository("AppBundle:Reservation")->findCalendarEvents();

        $eventData = [];
        foreach ($calendarEvents as $event) {
            $startDate = $event['startDate'];
            $startDate = $startDate->format('Y-m-d H:i:s');
            $endDate = $event['endDate'];
            $endDate = $endDate->format('Y-m-d H:i:s');

            $eventData[] = [
                "title" => "Server - ".$event['server_name'].', User - '.$event['firstName'].' '.$event['lastName'],
                "start" => $startDate,
                "end" => $endDate,
                "reservation_id" => $event['reservation_id']
            ];
        }

        $data = ['status' => JsonResponse::HTTP_OK, 'message' => 'Success', 'data' => $eventData];
        $response = new JsonResponse();
        $response->setContent(json_encode($data));

        return $response;
    }

}