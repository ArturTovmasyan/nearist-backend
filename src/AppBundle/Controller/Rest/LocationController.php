<?php

namespace AppBundle\Controller\Rest;

use AppBundle\Controller\Rest\Exception\AddUserException;
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
use AppBundle\Entity\Portal\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class LocationController
 * @package AppBundle\Controller\Rest
 * @Route("/api/v1.0/location")
 */
class LocationController extends Controller
{
    /**
     * This function is used to get country list
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Location",
     *  description="This function is used to get country list",
     *  statusCodes={
     *         200="Returned when get country list",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("GET")
     * @Route("/countries", name="country_list")
     *
     * @return array | JsonResponse
     */
    public function countryAction()
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var ArrayCollection $users */
        $countries = $em->getRepository("AppBundle:Country")->findAll();

        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        $data    = ['status' => JsonResponse::HTTP_OK, 'message' => 'Success', 'data' => ['countries' => $countries]];
        $content = $serializer->serialize($data, 'json', SerializationContext::create()->setGroups(['country_list']));

        $response = new JsonResponse();

        $response->setContent($content);

        return $response;
    }

    /**
     * This function is used to get state list by country
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Location",
     *  description="This function is used to get state list",
     *  statusCodes={
     *         200="Returned when get state list",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("GET")
     * @Route("/countries/{id}/states", name="state_list", requirements={"id"="\d+"})
     *
     * @param $id
     * @return array | JsonResponse
     */
    public function stateAction($id)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var ArrayCollection $users */
        $states = $em->getRepository("AppBundle:State")->findBy(['country' => $id]);

        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        $data    = ['status' => JsonResponse::HTTP_OK, 'message' => 'Success', 'data' => ['states' => $states]];
        $content = $serializer->serialize($data, 'json', SerializationContext::create()->setGroups(['state_list']));

        $response = new JsonResponse();

        $response->setContent($content);

        return $response;
    }

    /**
     * This function is used to get city list by state
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Location",
     *  description="This function is used to get city list",
     *  statusCodes={
     *         200="Returned when get city list",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("GET")
     * @Route("/countries/{id}/states/{stateId}/cities", name="city_list", requirements={"id"="\d+", "stateId"="\d+"})
     *
     * @param $id
     * @return array | JsonResponse
     */
    public function cityAction($id, $stateId)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var ArrayCollection $users */
        $cities = $em->getRepository("AppBundle:City")->findBy(['state' => $stateId]);

        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        $data    = ['status' => JsonResponse::HTTP_OK, 'message' => 'Success', 'data' => ['cities' => $cities]];
        $content = $serializer->serialize($data, 'json', SerializationContext::create()->setGroups(['city_list']));

        $response = new JsonResponse();

        $response->setContent($content);

        return $response;
    }
}