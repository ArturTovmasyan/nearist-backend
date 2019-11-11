<?php

namespace AppBundle\Controller\Rest;

use AppBundle\Entity\Portal\Bitstream;
use AppBundle\Entity\Portal\BitstreamStatus;
use AppBundle\Model\Bitstream\BoardTypes;
use AppBundle\Util\Mailer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class BitstreamStatusController
 * @package AppBundle\Controller\Rest
 * @Route("/api/v1.0/bitstream_status")
 */
class BitstreamStatusController extends Controller
{
    /**
     *
     * @ApiDoc(
     *  resource=true,
     *  section="BitstreamStatus",
     *  description="This function is used to get list of all entities",
     *  statusCodes={
     *         200="Success",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("GET")
     * @Route("/list", name="bitstream_status__list")
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @return array | JsonResponse
     */
    public function listAction()
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var ArrayCollection $list */
        $list = $em->getRepository("AppBundle:BitstreamStatus")->findAll();

        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        $data = ['status' => JsonResponse::HTTP_OK, 'message' => 'Success', 'data' => $list];
        $json_data = $serializer->serialize($data, 'json', SerializationContext::create()->setGroups(['bitstream_status__list']));

        return new JsonResponse($json_data, JsonResponse::HTTP_OK, [], true);
    }

    /**
     *
     * @ApiDoc(
     *  resource=true,
     *  section="BitstreamStatus",
     *  description="This function is used to add entity",
     *  statusCodes={
     *         200="Returned when add new user",
     *         403="Forbidden",
     *         400="Bad request"
     *     },
     *     parameters={
     *         {"name"="title", "dataType"="integer", "required"=true, "description"="Title"},
     *     }
     * )
     *
     * @Method("POST")
     * @Route("/add", name="bitstream_status__add")
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

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        $title = $request->get('title');

        $entity = new BitstreamStatus();
        $entity->setTitle($title);

        /** @var ValidatorInterface $validator */
        $validator = $this->get('validator');
        $errors = $validator->validate($entity, null, []);

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
                $em->persist($entity);
                $em->flush();
                $em->getConnection()->commit();

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
     *
     * @ApiDoc(
     *  resource=true,
     *  section="BitstreamStatus",
     *  description="This function is used to edit entity",
     *  statusCodes={
     *         200="Returned when add new user",
     *         403="Forbidden",
     *         400="Bad request"
     *  },
     *  parameters={
     *   {"name"="title", "dataType"="integer", "required"=true, "description"="title"},
     *  }
     * )
     *
     * @Method("PUT")
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @Route("/edit/{id}", name="bitstream_status__edit")
     * @param Request $request
     * @return array | JsonResponse
     * @throws
     */
    public function editAction(Request $request, $id)
    {
        if ($request->getContentType() === 'application/json' || $request->getContentType() === 'json') {
            $content = $request->getContent();
            $request->request->add(json_decode($content, true));
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        $title = $request->get('title');

        $entity = $em->getRepository('AppBundle:BitstreamStatus')->find($id);

        if ($entity) {
            $entity->setTitle($title);

            /** @var ValidatorInterface $validator */
            $validator = $this->get('validator');
            $errors = $validator->validate($entity, null, []);

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
                    $em->persist($entity);
                    $em->flush();
                    $em->getConnection()->commit();

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
            $response = ['status' => JsonResponse::HTTP_NOT_FOUND, 'message' => "Entity not found"];
        }

        return new JsonResponse($response, $status);
    }

    /**
     *
     * @ApiDoc(
     *  resource=true,
     *  section="BitstreamStatus",
     *  statusCodes={
     *         200="Returned when entity was removed",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("DELETE")
     * @Route("/remove/{id}", name="bitstream_status__remove", requirements={"id"="\d+"})
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param $id
     * @return JsonResponse
     * @throws
     */
    public function removeAction($id)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var BitstreamStatus $entity */
        $entity = $em->getRepository("AppBundle:BitstreamStatus")->find($id);

        if ($entity) {
            $em->getConnection()->beginTransaction();

            try {
                $em->remove($entity);
                $em->flush();
                $em->getConnection()->commit();

                $status = JsonResponse::HTTP_OK;
                $response = ['status' => JsonResponse::HTTP_OK, 'message' => "Entity removed"];
            } catch (\Exception $e) {
                $em->getConnection()->rollBack();

                $status = JsonResponse::HTTP_BAD_REQUEST;
                $response = ['status' => $e->getCode(), 'message' => $e->getMessage()];
            }
        } else {
            $status = JsonResponse::HTTP_NOT_FOUND;
            $response = ['status' => JsonResponse::HTTP_NOT_FOUND, 'message' => "Entity not found"];
        }

        return new JsonResponse($response, $status);
    }

    /**
     *
     * @ApiDoc(
     *  resource=true,
     *  section="BitstreamStatus",
     *  description="This function is used to get entity by id",
     *  statusCodes={
     *         200="Success",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("GET")
     * @Route("/{id}", name="bitstream_status__get", requirements={"id"="\d+"})
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param $id
     * @return JsonResponse
     */
    public function getAction($id)
    {
        if (!$id) {
            return new JsonResponse(['status' => JsonResponse::HTTP_BAD_REQUEST, 'message' => 'Id parameter is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var BitstreamStatus $entity */
        $entity = $em->getRepository("AppBundle:BitstreamStatus")->find($id);

        if (!$entity) {
            return new JsonResponse(['status' => JsonResponse::HTTP_NOT_FOUND, 'message' => 'Entity not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        $data = ['status' => JsonResponse::HTTP_OK, 'message' => 'Success', 'data' => $entity];
        $json_data = $serializer->serialize($data, 'json', SerializationContext::create()->setGroups(['bitstream_status__list']));

        return new JsonResponse($json_data, JsonResponse::HTTP_OK, [], true);
    }

}