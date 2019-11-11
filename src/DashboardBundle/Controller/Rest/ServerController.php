<?php

namespace DashboardBundle\Controller\Rest;

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
 * @package DashboardBundle\Controller\Rest
 * @Route("/api/v1.0/customer/servers")
 */
class ServerController extends Controller
{
    /**
     * This function is used to get server list
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Customer",
     *  description="This function is used to get server list by customer",
     *  statusCodes={
     *         200="Returned when get user list",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("GET")
     * @Route("/list", name="customer_server_list")
     * @Security("has_role('ROLE_CUSTOMER')")
     *
     * @return array | JsonResponse
     */
    public function listAction()
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        $userId = $this->get('security.token_storage')->getToken()->getUser()->getId();

        /** @var ArrayCollection $server */
        $server = $em->getRepository("AppBundle:Reservation")->findByCustomerId($userId);

        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        $data = ['status' => JsonResponse::HTTP_OK, 'message' => 'Success', 'data' => ['server' => $server]];

        $usersContent = $serializer->serialize($data, 'json', SerializationContext::create()->setGroups(['customer_server_list']));

        $response = new JsonResponse();

        $response->setContent($usersContent);

        return $response;
    }
}