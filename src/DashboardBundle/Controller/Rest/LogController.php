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
 * Class LogController
 * @package DashboardBundle\Controller\Rest
 * @Route("/api/v1.0/customer/logs")
 */
class LogController extends Controller
{
    /**
     * This function is used to get logs by customer
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Customer",
     *  description="This function is used to get logs by customer",
     *  statusCodes={
     *         200="Returned when get logs",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("GET")
     * @Route("/list", name="customer_log_list")
     * @Security("has_role('ROLE_CUSTOMER')")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listAction(Request $request)
    {
        $page  = $request->get('page');
        $limit = $request->get('limit');

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        $userId = $this->get('security.token_storage')->getToken()->getUser()->getId();

        /** @var ArrayCollection $server */
        $logs = $em->getRepository("AppBundle:Logs")->findByUserId($userId, $page, $limit);

        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        $data = [
            'status'  => JsonResponse::HTTP_OK,
            'message' => 'Success',
            'data'    =>
                [
                    'logs' => $logs
                ]
        ];

        $usersContent = $serializer->serialize($data, 'json', SerializationContext::create()->setGroups(['customer_log_list']));
        $response = new JsonResponse();
        $response->setContent($usersContent);

        return $response;
    }
}