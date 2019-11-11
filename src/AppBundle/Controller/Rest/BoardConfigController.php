<?php

namespace AppBundle\Controller\Rest;

use AppBundle\Entity\Portal\Bitstream;
use AppBundle\Entity\Portal\BoardConfig;
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
 * Class BoardConfigController
 * @package AppBundle\Controller\Rest
 * @Route("/api/v1.0/board_config")
 */
class BoardConfigController extends Controller
{
    /**
     *
     * @ApiDoc(
     *  resource=true,
     *  section="BoardConfig",
     *  description="This function is used to get list of all entities",
     *  statusCodes={
     *         200="Success",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("GET")
     * @Route("/list", name="board_config__list")
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @return array | JsonResponse
     */
    public function listAction()
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var ArrayCollection $list */
        $list = $em->getRepository("AppBundle:BoardConfig")->findBy([], ['date' => 'DESC']);

        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        $configs = BoardTypes::getConfig();
        $allTypes = [];
        $typeSum = 0;

        foreach ($configs as $key => $config) {
            foreach ($config as $type) {
                $allTypes[$key] = $type;
            }

            $typeSum += count($allTypes[$key]);
        }

        $data = ['status' => JsonResponse::HTTP_OK, 'message' => 'Success', 'data' => ['config' => $configs, 'fileCount' => $typeSum, 'list' => $list]];
        $json_data = $serializer->serialize($data, 'json', SerializationContext::create()->setGroups(['board_config__list']));

        return new JsonResponse($json_data, JsonResponse::HTTP_OK, [], true);
    }

    /**
     *
     * @ApiDoc(
     *  resource=true,
     *  section="BoardConfig",
     *  description="This function is used to add entity",
     *  statusCodes={
     *         200="Returned when add new user",
     *         403="Forbidden",
     *         400="Bad request"
     *     },
     *     parameters={
     *         {"name"="board_type", "dataType"="integer", "required"=true, "description"="Board type"},
     *         {"name"="description", "dataType"="string", "required"=true, "description"="Description"}
     *     }
     * )
     *
     * @Method("POST")
     * @Route("/add", name="board_config__add")
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

        $boardType = $request->get('board_type');
        $description = $request->get('description');
        $raw_bitstreams = $request->get('fileData');
        $status = $request->get('status');

        $status = $em->getRepository("AppBundle:BitstreamStatus")->find($status[0]['id']);

        $entity = new BoardConfig();
        $entity->setBoardType(BoardTypes::get(reset($boardType)['id']));
        $entity->setDescription($description);
        $entity->setDate(new \DateTime());
        $entity->setStatus($status);

        foreach ($raw_bitstreams as $raw_bitstream) {
            $bitstream = new Bitstream();
            $bitstream->setBoardConfig($entity);
            $bitstream->setDevice($raw_bitstream['device_type']);
            $bitstream->setFileName($raw_bitstream['file_name']);
            $bitstream->setFile($raw_bitstream['value']);
            $bitstream->setFileType($raw_bitstream['file_type']);
            $em->persist($bitstream);
        }

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

                $users = $em->getRepository("AppBundle:User")->findBy(['enabled' => true]);
                $mailer = new Mailer($this->container);
                $mailer->notifyBitstream($entity, $users);

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
     * @ApiDoc(
     *      resource=true,
     *      section="BoardConfig",
     *      description="This function is used to edit entity",
     *      statusCodes={
     *          200="Returned when add new user",
     *          403="Forbidden",
     *          400="Bad request"
     *      },
     *      parameters={
     *          {"name"="bord_type", "dataType"="integer", "required"=true, "description"="Bord type"},
     *          {"name"="description", "dataType"="string", "required"=true, "description"="Description"},
     *      }
     * )
     *
     * @Method("PUT")
     * @Route("/edit/{id}", name="board_config__edit")
     * @Security("has_role('ROLE_ADMIN')")
     *
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

        /** @var BoardConfig $entity */
        $entity = $em->getRepository('AppBundle:BoardConfig')->find($id);

        if ($entity) {

            $boardType = $request->get('board_type');
            $description = $request->get('description');
            $raw_bitstreams = $request->get('fileData');
            $status = $request->get('status');

            $status = $em->getRepository("AppBundle:BitstreamStatus")->find($status[0]['id']);

            $entity->setBoardType(BoardTypes::get(reset($boardType)['id']));
            $entity->setDescription($description);
            $entity->setStatus($status);

            $repo_bitstream = $em->getRepository('AppBundle:Bitstream');

            foreach ($raw_bitstreams as $raw_bitstream) {
                $bitstream = null;
                $bitstream = $repo_bitstream->find($raw_bitstream['stream_id']);

                if ($bitstream === null) {
                    $bitstream = new Bitstream();
                }

                $bitstream->setBoardConfig($entity);
                $bitstream->setDevice($raw_bitstream['device_type']);
                $bitstream->setFileName($raw_bitstream['file_name']);
                $bitstream->setFile($raw_bitstream['value']);
                $bitstream->setFileType($raw_bitstream['file_type']);
                $em->persist($bitstream);
            }

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

                    $users = $em->getRepository("AppBundle:User")->findBy(['enabled' => true]);
                    $mailer = new Mailer($this->container);
                    $mailer->notifyBitstream($entity, $users);

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
     *  section="BoardConfig",
     *  statusCodes={
     *         200="Returned when entity was removed",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("DELETE")
     * @Route("/remove/{id}", name="board_config__remove", requirements={"id"="\d+"})
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

        /** @var BoardConfig $entity */
        $entity = $em->getRepository("AppBundle:BoardConfig")->find($id);

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
     *  section="BoardConfig",
     *  description="This function is used to get entity by id",
     *  statusCodes={
     *         200="Success",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("GET")
     * @Route("/{id}", name="board_config__get", requirements={"id"="\d+"})
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

        /** @var BoardConfig $entity */
        $entity = $em->getRepository("AppBundle:BoardConfig")->find($id);

        if (!$entity) {
            return new JsonResponse(['status' => JsonResponse::HTTP_NOT_FOUND, 'message' => 'Entity not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        $data = ['status' => JsonResponse::HTTP_OK, 'message' => 'Success', 'data' => $entity];
        $json_data = $serializer->serialize($data, 'json', SerializationContext::create()->setGroups(['board_config__list']));

        return new JsonResponse($json_data, JsonResponse::HTTP_OK, [], true);
    }

    /**
     *
     * @ApiDoc(
     *  resource=true,
     *  section="BoardConfig",
     *  description="This function is used to download bitstream file by id",
     *  statusCodes={
     *         200="Success",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("GET")
     * @Route("/file/{id}", name="board_config__get_file", requirements={"id"="\d+"})
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param $id
     * @return JsonResponse | Response
     */
    public function getFileAction($id)
    {
        if (!$id) {
            return new JsonResponse(['status' => JsonResponse::HTTP_BAD_REQUEST, 'message' => 'Id parameter is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var Bitstream $entity */
        $entity = $em->getRepository("AppBundle:Bitstream")->find($id);

        if (!$entity) {
            return new JsonResponse(['status' => JsonResponse::HTTP_NOT_FOUND, 'message' => 'Entity not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Generate response
        $response = new Response();
        $base64String = $entity->getFile();
        $content = base64_decode($base64String);
        $content_length = strlen(bin2hex($content)) / 2;

        // Set headers
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $entity->getFileName() . '";');
        $response->headers->set('Content-length', $content_length);

        $response->sendHeaders();
        $response->setContent($content);

        return $response;
    }
}