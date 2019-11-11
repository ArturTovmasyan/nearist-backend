<?php

namespace AppBundle\Controller\Rest;

use AppBundle\Controller\Rest\Exception\IFlexException;
use AppBundle\Entity\Portal\Bitstream;
use AppBundle\Entity\Portal\BoardConfig;
use AppBundle\Entity\Portal\Reservation;
use AppBundle\Entity\Portal\Server;
use AppBundle\Entity\Portal\ServerTemperature;
use AppBundle\Entity\Portal\Logs;
use AppBundle\Model\Bitstream\FileTypes;
use AppBundle\Model\Log\LogType;
use AppBundle\Util\IFlexClient;
use AppBundle\Util\IPMIClient;
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
 * @Route("/api/v1.0/server")
 */
class ServerController extends Controller
{
    /**
     * This function is used to get server list
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Server",
     *  description="This function is used to get server list",
     *  statusCodes={
     *         200="Returned when get user list",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("GET")
     * @Route("/list", name="server_list")
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @return array | JsonResponse
     */
    public function listAction()
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var ArrayCollection $server */
        $server = $em->getRepository("AppBundle:Server")->findAll();

        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        $data = ['status' => JsonResponse::HTTP_OK, 'message' => 'Success', 'data' => ['server' => $server]];

        $usersContent = $serializer->serialize($data, 'json', SerializationContext::create()->setGroups(['server_list']));

        $response = new JsonResponse();

        $response->setContent($usersContent);

        return $response;
    }

    /**
     * This function is used to add new user
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Server",
     *  description="This function is used to add new user",
     *  statusCodes={
     *         200="Returned when add new user",
     *         403="Forbidden",
     *         400="Bad request"
     *     },
     *     parameters={
     *          {"name"="name", "dataType"="string", "required"=true, "description"="Server Name"},
     *          {"name"="description", "dataType"="string",  "required"=true, "Description"},
     *          {"name"="ip", "dataType"="string", "required"=true, "description"="Server IP"},
     *          {"name"="ssh_port", "dataType"="string", "required"=true, "description"="SSH port"},
     *          {"name"="ftp_port", "dataType"="string", "required"=true, "description"="FTP port"},
     *          {"name"="ipmi_ip", "dataType"="string", "required"=false, "description"="iFlex IP"},
     *          {"name"="iflex_port", "dataType"="array", "required"=true, "description"="iFlex Management port"},
     *          {"name"="iflex_secure", "dataType"="string",  "required"=true, "iFlex Management Secure"},
     *          {"name"="iflex_username", "dataType"="string",  "required"=true, "iFlex Management Username"},
     *          {"name"="iflex_password", "dataType"="password",  "required"=true, "iFlex Management Password"},
     *          {"name"="ipmi_ip", "dataType"="string", "required"=false, "description"="IPMI IP"},
     *          {"name"="ipmi_port", "dataType"="string",  "required"=false, "description"="IPMI Port"},
     *          {"name"="ipmi_secure", "dataType"="string",  "required"=true, "IPMI Secure"},
     *          {"name"="ipmi_username", "dataType"="string",  "required"=true, "IPMI Username"},
     *          {"name"="ipmi_password", "dataType"="password",  "required"=true, "IPMI Password"},
     *      }
     * )
     *
     * @Method("POST")
     * @Route("/add", name="server_add")
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function addAction(Request $request)
    {
        if ($request->getContentType() === 'application/json' || $request->getContentType() === 'json') {
            $content = $request->getContent();
            $request->request->add(json_decode($content, true));
        }

        //get POST data in request
        $name = $request->get('name');
        $description = $request->get('description');
        $ip = $request->get('ip');
        $sshPort = $request->get('ssh_port');
        $ftpPort = $request->get('ftp_port');
        $iflexIp = $request->get('iflex_ip');
        $iflexPort = $request->get('iflex_port');
        $iflexSecure = $request->get('iflex_secure');
        $iflexUsername = $request->get('iflex_username');
        $iflexPassword = $request->get('iflex_password');
        $ipmiIp = $request->get('ipmi_ip');
        $ipmiPort = $request->get('ipmi_port');
        $ipmiSecure = $request->get('ipmi_secure');
        $ipmiUsername = $request->get('ipmi_username');
        $ipmiPassword = $request->get('ipmi_password');

        /** @var Server $server */
        $server = new Server();
        $server->setName($name);
        $server->setDescription($description);
        $server->setIp($ip);
        $server->setSshPort($sshPort);
        $server->setFtpPort($ftpPort);
        $server->setIflexIp($iflexIp);
        $server->setIflexPort($iflexPort ? $iflexPort : 0);
        $server->setIflexSecure($iflexSecure);
        $server->setIflexUsername($iflexUsername);
        $server->setIflexPassword($iflexPassword);
        $server->setIpmiIp($ipmiIp);
        $server->setIpmiPort($ipmiPort ? $ipmiPort : 0);
        $server->setIpmiSecure($ipmiSecure);
        $server->setIpmiUsername($ipmiUsername);
        $server->setIpmiPassword($ipmiPassword);

        /** @var ValidatorInterface $validator */
        $validator = $this->get('validator');
        $errors = $validator->validate($server, null, []);

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
                $em->persist($server);
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
     * This function is used to add new user
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Server",
     *  description="This function is used to add new user",
     *  statusCodes={
     *         200="Returned when add new user",
     *         403="Forbidden",
     *         400="Bad request"
     *     },
     *     parameters={
     *      {"name"="name", "dataType"="string", "required"=true, "description"="Server Name"},
     *      {"name"="description", "dataType"="string",  "required"=true, "Description"},
     *      {"name"="ip", "dataType"="string", "required"=true, "description"="Server IP"},
     *      {"name"="ssh_port", "dataType"="string", "required"=true, "description"="SSH port"},
     *      {"name"="ftp_port", "dataType"="string", "required"=true, "description"="FTP port"},
     *      {"name"="ipmi_ip", "dataType"="string", "required"=false, "description"="iFlex IP"},
     *      {"name"="iflex_port", "dataType"="array", "required"=true, "description"="iFlex Management port"},
     *      {"name"="iflex_secure", "dataType"="string",  "required"=true, "iFlex Management Secure"},
     *      {"name"="iflex_username", "dataType"="string",  "required"=true, "iFlex Management Username"},
     *      {"name"="iflex_password", "dataType"="password",  "required"=true, "iFlex Management Password"},
     *      {"name"="ipmi_ip", "dataType"="email", "required"=false, "description"="IPMI IP"},
     *      {"name"="ipmi_port", "dataType"="string",  "required"=false, "description"="IPMI Port"},
     *      {"name"="ipmi_secure", "dataType"="string",  "required"=true, "IPMI Secure"},
     *      {"name"="ipmi_username", "dataType"="string",  "required"=true, "IPMI Username"},
     *      {"name"="ipmi_password", "dataType"="password",  "required"=true, "IPMI Password"},
     * }
     * )
     *
     * @Method("PUT")
     * @Route("/edit/{id}", name="server_edit", requirements={"id"="\d+"})
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param $id
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function editAction($id, Request $request)
    {
        if ($request->getContentType() === 'application/json' || $request->getContentType() === 'json') {
            $content = $request->getContent();
            $request->request->add(json_decode($content, true));
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var Server $server */
        $server = $em->getRepository("AppBundle:Server")->find($id);

        if ($server) {

            //get POST data in request
            $name = $request->get('name');
            $description = $request->get('description');
            $ip = $request->get('ip');
            $sshPort = $request->get('ssh_port');
            $ftpPort = $request->get('ftp_port');
            $iflexIp = $request->get('iflex_ip');
            $iflexPort = $request->get('iflex_port');
            $iflexSecure = $request->get('iflex_secure');
            $iflexUsername = $request->get('iflex_username');
            $iflexPassword = $request->get('iflex_password');
            $ipmiIp = $request->get('ipmi_ip');
            $ipmiPort = $request->get('ipmi_port');
            $ipmiSecure = $request->get('ipmi_secure');
            $ipmiUsername = $request->get('ipmi_username');
            $ipmiPassword = $request->get('ipmi_password');

            $server->setName($name);
            $server->setDescription($description);
            $server->setIp($ip);
            $server->setSshPort($sshPort);
            $server->setFtpPort($ftpPort);
            $server->setIflexIp($iflexIp);
            $server->setIflexPort($iflexPort ? $iflexPort : 0);
            $server->setIflexSecure($iflexSecure);
            $server->setIflexUsername($iflexUsername);
            $server->setIflexPassword($iflexPassword);
            $server->setIpmiIp($ipmiIp);
            $server->setIpmiPort($ipmiPort ? $ipmiPort : 0);
            $server->setIpmiSecure($ipmiSecure);
            $server->setIpmiUsername($ipmiUsername);
            $server->setIpmiPassword($ipmiPassword);

            /** @var ValidatorInterface $validator */
            $validator = $this->get('validator');
            $errors = $validator->validate($server, null, []);

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
                    $em->persist($server);
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
            $response = ['status' => JsonResponse::HTTP_NOT_FOUND, 'message' => "Server by id` $id not found"];
        }

        return new JsonResponse($response, $status);
    }

    /**
     * This function is used to remove user by id
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Server",
     *  statusCodes={
     *         200="Returned when user was removed",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("DELETE")
     * @Route("/remove/{id}", name="server_delete", requirements={"id"="\d+"})
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param $id
     * @return JsonResponse
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function removeAction($id)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var Server $users */
        $server = $em->getRepository("AppBundle:Server")->find($id);

        if ($server) {

            $em->getConnection()->beginTransaction();

            try {
                $em->remove($server);
                $em->flush();

                $em->getConnection()->commit();

                $status = JsonResponse::HTTP_OK;
                $response = ['status' => JsonResponse::HTTP_OK, 'message' => "Server removed"];
            } catch (\Exception $e) {
                $em->getConnection()->rollBack();

                $status = JsonResponse::HTTP_BAD_REQUEST;
                $response = ['status' => $e->getCode(), 'message' => $e->getMessage()];
            }
        } else {
            $status = JsonResponse::HTTP_NOT_FOUND;
            $response = ['status' => JsonResponse::HTTP_NOT_FOUND, 'message' => "Server by id` $id not found"];
        }

        return new JsonResponse($response, $status);
    }

    /**
     * This function is used to get server by id
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Server",
     *  description="This function is used to get server by id",
     *  statusCodes={
     *         200="Success",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("GET")
     * @Route("/{id}", name="get_server", requirements={"id"="\d+"})
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param $id
     * @return array | JsonResponse
     */
    public function getAction($id)
    {
        if (!$id) {
            return new JsonResponse(['status' => JsonResponse::HTTP_BAD_REQUEST, 'message' => 'Id param is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var ArrayCollection $server */
        $server = $em->getRepository("AppBundle:Server")->find($id);

        if (!$server) {
            return new JsonResponse(['status' => JsonResponse::HTTP_NOT_FOUND, 'message' => "Server by id=$id not found"], JsonResponse::HTTP_NOT_FOUND);
        }

        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        $data = ['status' => JsonResponse::HTTP_OK, 'message' => 'Success', 'data' => ['server' => $server]];

        $usersContent = $serializer->serialize($data, 'json', SerializationContext::create()->setGroups(['server_list']));

        $response = new JsonResponse();
        $response->setContent($usersContent);

        return $response;
    }

    /**
     * This function is used to get server IPMI status by id
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Server",
     *  description="This function is used to get server IPMI status by id",
     *  statusCodes={
     *         200="Success",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("GET")
     * @Route("/ipmi/{id}", name="ipmi_status", requirements={"id"="\d+"})
     *
     * @param $id
     * @return array | JsonResponse
     */
    public function ipmiStatusAction($id)
    {
        if (!$id) {
            return new JsonResponse(['status' => JsonResponse::HTTP_NOT_FOUND, 'message' => "Server id param is required"], JsonResponse::HTTP_NOT_FOUND);
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var Server $server */
        $server = $em->getRepository('AppBundle:Server')->find($id);

        if (!$server) {
            return new JsonResponse(['status' => JsonResponse::HTTP_NOT_FOUND, 'message' => "Server by id = $id not found"], JsonResponse::HTTP_NOT_FOUND);
        }

        try {
            $ipmiClient = new IPMIClient($this->get('eight_points_guzzle.client.ipmi_server'), $server);

            if ($ipmiClient->login()) {
                $status = $ipmiClient->getStatus();
                if ($status != null) {
                    $data = ['status' => JsonResponse::HTTP_OK, 'message' => 'Success', 'data' => strtolower($status)];
                    $response = new JsonResponse($data);
                    return $response;
                } else {
                    return new JsonResponse(['status' => JsonResponse::HTTP_BAD_REQUEST, 'message' => "Invalid request."], JsonResponse::HTTP_BAD_REQUEST);
                }
            } else {
                return new JsonResponse(['status' => JsonResponse::HTTP_BAD_REQUEST, 'message' => "Invalid username or password."], JsonResponse::HTTP_BAD_REQUEST);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => JsonResponse::HTTP_BAD_REQUEST,
                'message' => "Couldn't get server power status."
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * This function is used to set server IPMI status by id
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Server",
     *  description="This function is used to set server IPMI status by id",
     *  statusCodes={
     *         200="Success",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("POST")
     * @Route("/ipmi/{id}/{status}", name="ipmi_set", requirements={"id"="\d+", "status"="(0|1)"})
     * @Security("has_role('ROLE_CUSTOMER') or has_role('ROLE_ADMIN')")
     *
     * @param $id
     * @param $status
     * @return array | JsonResponse
     */
    public function ipmiAction($id, $status)
    {
        try {
            if (!$id || ($status == null)) {
                throw new \Exception("Server id and status param is required", JsonResponse::HTTP_BAD_REQUEST);
            }

            /** @var EntityManager $em */
            $em = $this->getDoctrine()->getManager();

            /** @var Server $server */
            $server = $em->getRepository('AppBundle:Server')->find($id);

            if (!$server) {
                throw new \Exception("Server by id = $id not found", JsonResponse::HTTP_BAD_REQUEST);
            }

            // check customer request by interval
            if (!$this->get('security.token_storage')->getToken()->getUser()->isAdmin()) {
                /** @var Reservation $reservation */
                $reservation = $em->getRepository('AppBundle:Reservation')
                    ->findByCustomerIdAndServerId(
                        $this->get('security.token_storage')->getToken()->getUser(),
                        $server->getId()
                    );

                if (!$reservation) {
                    throw new \Exception("Customer haven\'t relation to server", JsonResponse::HTTP_BAD_REQUEST);
                }

                $now = new \DateTime();

                if ($reservation['startDate']->getTimestamp() > $now->getTimestamp() ||
                    $reservation['endDate']->getTimestamp() < $now->getTimestamp()
                ) {
                    throw new \Exception("Customer haven`t access to server at this time", JsonResponse::HTTP_BAD_REQUEST);
                }
            }

            try {
                $ipmiClient = new IPMIClient($this->get('eight_points_guzzle.client.ipmi_server'), $server);

                if (!$ipmiClient->login()) {
                    throw new IFlexException("Invalid username or password.", JsonResponse::HTTP_BAD_REQUEST);
                }

                $action_status = $ipmiClient->setStatus($status);

                if (is_null($action_status)) {
                    throw new IFlexException("Invalid request.", JsonResponse::HTTP_BAD_REQUEST);
                }

                $log = new Logs();
                $log->setDateTime(new \DateTime());
                $log->setUser($this->getUser());
                $log->setType(LogType::POWER);
                $log->setServer($server);
                $log->setMessage(sprintf("User %s (%s) powered %s the %s (%s) server.",
                    $this->getUser()->getFullName(), $this->getUser()->getUsername(),
                    $status ? 'on' : 'off',
                    $server->getName(),  $server->getIp()
                ));
                $em->persist($log);
                $em->flush();
            } catch (IFlexException $e) {
                throw $e;
            } catch (\Exception $e) {
                throw new \Exception("Couldn`t get server power status.", JsonResponse::HTTP_BAD_REQUEST);
            }

            $status  = JsonResponse::HTTP_OK;
            $message = 'Success';
            $data    =  strtolower($action_status);
        } catch (\Throwable $e) {
            $status  = $e->getCode() ?: JsonResponse::HTTP_BAD_REQUEST;
            $message = $e->getMessage();
        }

        $response = [
            'status'  => $status,
            'message' => $message
        ];

        if (isset($data)) {
            $response['data'] = $data;
        }

        return new JsonResponse($response, $status);
    }


    /**
     * This function is used to get server status by id
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Server",
     *  description="This function is used to get server status by id",
     *  statusCodes={
     *         200="Success",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("GET")
     * @Route("/health/{id}", name="get_server_status", requirements={"id"="\d+"})
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param $id
     * @return array | JsonResponse
     */
    public function healthAction($id)
    {
        if (!$id) {
            return new JsonResponse(['status' => JsonResponse::HTTP_NOT_FOUND, 'message' => "Server id param is required"], JsonResponse::HTTP_NOT_FOUND);
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var Server $server */
        $server = $em->getRepository('AppBundle:Server')->find($id);

        if (!$server) {
            return new JsonResponse(['status' => JsonResponse::HTTP_NOT_FOUND, 'message' => "Server by id = $id not found"], JsonResponse::HTTP_NOT_FOUND);
        }

        try {
            $client = new IFlexClient($this->get('eight_points_guzzle.client.api_server'), $server);

            if ($client->login()) {
                $data = $client->getSystemInfo();
                if ($data !== false) {
                    /** @var Serializer $serializer */
                    $serializer = $this->get('jms_serializer');
                    $data = ['status' => JsonResponse::HTTP_OK, 'message' => 'Success', 'data' => $data];
                    $data = $serializer->serialize($data, 'json', SerializationContext::create());
                    return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
                } else {
                    $http_status = JsonResponse::HTTP_BAD_REQUEST;
                    $status = JsonResponse::HTTP_BAD_REQUEST;
                    $message = "Unfortunately can't connect to server. Please try again later...";
                    return new JsonResponse(['status' => $status, 'message' => $message], $http_status);
                }
            } else {
                $http_status = JsonResponse::HTTP_BAD_REQUEST;
                $status = JsonResponse::HTTP_BAD_REQUEST;
                $message = "Unable to connect to to server invalid username/password...";
                return new JsonResponse(['status' => $status, 'message' => $message], $http_status);
            }
        } catch (\Exception $e) {
            $http_status = JsonResponse::HTTP_BAD_REQUEST;
            $status = JsonResponse::HTTP_BAD_REQUEST;
            $message = $e->getMessage();
            return new JsonResponse(['status' => $status, 'message' => $message], $http_status);
        }
    }

    /**
     * This function is used to get server monitoring by id
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Server",
     *  description="This function is used to get server monitoring by id",
     *  statusCodes={
     *         200="Success",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("GET")
     * @Route("/temperature/logs/{id}", name="get_server_temperature_logs", requirements={"id"="\d+"})
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param $id
     * @return array | JsonResponse
     */
    public function getServerTemperatureLogsAction(Request $request, $id)
    {
        if (!$id) {
            return new JsonResponse(['status' => JsonResponse::HTTP_BAD_REQUEST, 'message' => 'Id param is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        // gte pagination params
        $page  = $request->query->get('page')  ?: 1;
        $limit = $request->query->get('limit') ?: 10;

        /** @var ServerTemperature $serverTemperature */
        $serverTemperature = $em->getRepository("AppBundle:TemperatureLog")->findByServerId($id, $limit, $page);
        $logCount          = $em->getRepository("AppBundle:TemperatureLog")->countByServerId($id);

        if (!$serverTemperature) {
            return new JsonResponse(['status' => JsonResponse::HTTP_OK, 'message' => ''], JsonResponse::HTTP_OK);
        }

        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');
        $data = [
            'status'  => JsonResponse::HTTP_OK,
            'message' => 'Success',
            'data'    => [
                'list'       => $serverTemperature,
                'pagination' => [
                    'page'  => $page,
                    'pages' => ceil($logCount/$limit),
                    'limit' => $limit,
                    'count' => $logCount,
                ]
            ]
        ];

        $serverContent = $serializer->serialize($data, 'json', SerializationContext::create()->setGroups(['temp_log_list']));

        $response = new JsonResponse();
        $response->setContent($serverContent);

        return $response;
    }

    /**
     * This function is used to get server monitoring by id
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Server",
     *  description="This function is used to get server monitoring by id",
     *  statusCodes={
     *         200="Success",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("GET")
     * @Route("/temperature/{id}", name="get_server_temperature", requirements={"id"="\d+"})
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param Request $request
     * @param $id
     * @return array | JsonResponse
     */
    public function getServerTemperatureAction(Request $request, $id)
    {
        if (!$id) {
            return new JsonResponse(['status' => JsonResponse::HTTP_BAD_REQUEST, 'message' => 'Id param is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var ServerTemperature $serverTemperature */
        $serverTemperature = $em->getRepository("AppBundle:ServerTemperature")->findByServerId($id);

        if (!$serverTemperature) {
            return new JsonResponse(['status' => JsonResponse::HTTP_OK, 'message' => ''], JsonResponse::HTTP_OK);
        }

        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');
        $data = ['status' => JsonResponse::HTTP_OK, 'message' => 'Success', 'data' => $serverTemperature];
        $serverContent = $serializer->serialize($data, 'json', SerializationContext::create());

        $serverContent = json_decode($serverContent, true);
        unset($serverContent['data']['server']);
        $serverContent = json_encode($serverContent);

        $response = new JsonResponse();
        $response->setContent($serverContent);

        return $response;
    }

    /**
     * This function is used to add new user
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Server",
     *  description="This function is used to add new user",
     *  statusCodes={
     *         200="Returned when add new user",
     *         403="Forbidden",
     *         400="Bad request"
     *     },
     *     parameters={
     *      {"name"="emails", "dataType"="string", "required"=true, "description"="Server Temperature notification emails"},
     *      {"name"="event_duration", "dataType"="string", "required"=true, "description"="Server Temperature event duration"},
     *      {"name"="event_temperature", "dataType"="string", "required"=true, "description"="Server Temperature event temperature"},
     *      {"name"="log_rate", "dataType"="string", "required"=true, "description"="Server Temperature log rate"}
     * }
     * )
     *
     * @Method("PUT")
     * @Route("/temperature/manage/{id}", name="manage_server_temperature", requirements={"id"="\d+"})
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param $id
     * @param Request $request
     * @return JsonResponse
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function manageServerTemperatureAction($id, Request $request)
    {
        if (!$id) {
            return new JsonResponse(['status' => JsonResponse::HTTP_BAD_REQUEST, 'message' => 'Server Id param is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($request->getContentType() === 'application/json' || $request->getContentType() === 'json') {
            $content = $request->getContent();
            $request->request->add(json_decode($content, true));
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var Server $server */
        $server = $em->getRepository('AppBundle:Server')->find($id);

        if (!$server) {
            return new JsonResponse(['status' => JsonResponse::HTTP_NOT_FOUND, 'message' => "Server by id=$id not found"], JsonResponse::HTTP_NOT_FOUND);
        }

        /** @var ServerTemperature $serverTemperature */
        $serverTemperature = $server->getServerTemperature();

        //check if add action
        if (!$serverTemperature) {
            $serverTemperature = new ServerTemperature();
        }

        if ($serverTemperature) {

            //get POST data in request
            $emails = $request->get('emails');
            $eventDuration = $request->get('event_duration');
            $eventTemperature = $request->get('event_temperature');
            $logRate = $request->get('log_rate');

            //set server temperature data
            $serverTemperature->setEmails($emails);
            $serverTemperature->setEventDuration($eventDuration);
            $serverTemperature->setEventTemperature($eventTemperature);
            $serverTemperature->setLogRate($logRate);
            $serverTemperature->setServer($server);

            /** @var ValidatorInterface $validator */
            $validator = $this->get('validator');
            $errors = $validator->validate($serverTemperature, null, []);

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
                    $em->persist($serverTemperature);
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
            $response = ['status' => JsonResponse::HTTP_NOT_FOUND, 'message' => "Server Temperature by id` $id not found"];
        }

        return new JsonResponse($response, $status);
    }

    /**
     * This function is used to get server temperature log by id
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Server",
     *  description="This function is used to get server temperature log by id",
     *  statusCodes={
     *         200="Success",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("GET")
     * @Route("/logs/{id}/{page}/{temperature}/{dateFilter}/{type}", name="get_server_logs", requirements={"id"="\d+", "temperature"="\d+", "type"="\d+"})
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param $id
     * @param $page
     * @param $temperature
     * @param $type
     * @param $dateFilter
     * @return array | JsonResponse
     */
    public function getServerLogDataAction($id, $page, $temperature = 0, $type, $dateFilter)
    {
        if (!$id && $id !== "0") {
            return new JsonResponse(['status' => JsonResponse::HTTP_BAD_REQUEST, 'message' => 'Id params is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if($id === "0") {
            $id = null;
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var Logs $logs */
        $logs = $em->getRepository("AppBundle:Logs")->findLogs($id, $page, $temperature, $type, $dateFilter);

        if (!$logs) {
            return new JsonResponse(['status' => JsonResponse::HTTP_OK, 'message' => ''], JsonResponse::HTTP_OK);
        }

        //get logs count
        $logsCount = $em->getRepository("AppBundle:Logs")->getLogsSum($id, $type, $dateFilter);

        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');
        $data = ['status' => JsonResponse::HTTP_OK, 'message' => 'Success', 'data' => ['logs_data' => $logs, 'count' => $logsCount]];
        $serverContent = $serializer->serialize($data, 'json', SerializationContext::create()->setGroups(['temp_log']));

        $response = new JsonResponse();
        $response->setContent($serverContent);

        return $response;
    }

    /**
     * This function is used to get server temperature log by id
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Server",
     *  description="This function is used to get server temperature log by id",
     *  statusCodes={
     *         200="Success",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("DELETE")
     * @Route("/logs/delete/{id}/{type}", name="remove_logs", requirements={"id"="\d+", "type"="\d+"})
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param $id
     * @param $type
     * @return array | JsonResponse
     */
    public function deleteLogsAction($id, $type)
    {
        if (!$id || (!$type)) {
            return new JsonResponse(['status' => JsonResponse::HTTP_BAD_REQUEST, 'message' => 'Id and Type params is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $em->getRepository("AppBundle:Logs")->deleteLogs($id, $type);

        $data = ['status' => JsonResponse::HTTP_OK, 'message' => 'Success'];
        $data = json_encode($data);

        $response = new JsonResponse();
        $response->setContent($data);

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
     * @Route("/select-info/{id}", name="server_info___list", requirements={"id"="\d+"})
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @return array | JsonResponse
     */
    public function serverInfoListAction(Request $request, $id)
    {
        if (!$id) {
            return new JsonResponse(['status' => JsonResponse::HTTP_BAD_REQUEST, 'message' => 'Server Id param is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var Server $server */
        $server = $em->getRepository("AppBundle:Server")->find($id);

        $boardConfigs = $em->getRepository("AppBundle:BoardConfig")->findAll();

        $boardConfigList = [];

        /** @var BoardConfig $boardConfig */
        foreach ($boardConfigs as $boardConfig) {
            $boardConfigList[] = $boardConfig->getBoardListString();
        }

        $boardCount = 0;
        $client = new IFlexClient($this->get('eight_points_guzzle.client.api_server'), $server);
        if ($client->login()) {
            $boardCount = $client->getBoardCount();

            if ($boardCount === false) {
                $boardCount = 0;
            }
        }

        $boardList = [];

        for ($i = 0; $i < $boardCount; $i++) {
            $boardList[] = ["id" => $i, "itemName" => sprintf('%02d', $i)];
        }

        $laneCount = 3;
        for ($i = 0; $i < $laneCount; $i++) {
            $laneList[] = ["id" => $i, "itemName" => sprintf('%02d', $i)];
        }

        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');
        $data = ['status' => JsonResponse::HTTP_OK, 'message' => 'Success', 'data' => ['board_config_list' => $boardConfigList, 'board_list' => $boardList, 'lane_list' => $laneList]];
        $usersContent = $serializer->serialize($data, 'json', SerializationContext::create()->setGroups(['select_list']));

        $response = new JsonResponse();
        $response->setContent($usersContent);

        return $response;
    }

    /**
     * @Method("POST")
     * @Route("/load-bitstream", name="load__bitstream")
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param Request $request
     * @return array | JsonResponse
     * @throws
     */
    public function loadBitstreamAction(Request $request)
    {
        if ($request->getContentType() === 'application/json' || $request->getContentType() === 'json') {
            $content = $request->getContent();
            $request->request->add(json_decode($content, true));
        }

        $id = $request->get('id');

        if(empty($id)) {
            $id = 0;
        }

        $boardConfig = $request->get('board_config');
        $boardData = $request->get('board_data');

        $boardDataArray = [];
        foreach ($boardData as $detail) {
            $explodeData = explode('__', $detail);
            $boardDataArray[$explodeData[0]][] = $explodeData[1];
        }
        $boardData = $boardDataArray;

        $em = $this->getDoctrine()->getManager();

        $server = $em->getRepository("AppBundle:Server")->find($id);

        if (!$server) {
            return new JsonResponse(['status' => JsonResponse::HTTP_NOT_FOUND, 'message' => "Server by id = $id not found"], JsonResponse::HTTP_NOT_FOUND);
        }

        try {
            $client = new IFlexClient($this->get('eight_points_guzzle.client.api_server'), $server);

            if ($client->login()) {
                /** @var BoardConfig $board_config */
                $board_config = $em->getRepository("AppBundle:BoardConfig")->find($boardConfig[0]['id']);

                /** @var Bitstream $sea */
                $sea = $board_config->getBitstreams()->filter(function (Bitstream $entry) {
                    return $entry->getFileType() == FileTypes::SEA;
                })->first();
                /** @var Bitstream $sed */
                $sed = $board_config->getBitstreams()->filter(function (Bitstream $entry) {
                    return $entry->getFileType() == FileTypes::SED;
                })->first();

                if ($sed != null || $sea != null) {
                    $data = $client->loadBitstream($boardData, $sea->getFile(), $sed->getFile());

                    if ($data !== false) {
                        $status = JsonResponse::HTTP_OK;
                        $message = "Success";
                        $http_status = JsonResponse::HTTP_OK;
                    } else {
                        $http_status = JsonResponse::HTTP_BAD_REQUEST;
                        $status = JsonResponse::HTTP_BAD_REQUEST;
                        $message = "Unfortunately can't connect to server. Please try again later...";
                    }

                    $log = new Logs();
                    $log->setDateTime(new \DateTime());
                    $log->setType(LogType::LOAD_BITSTREAM);
                    $log->setServer($server);
                    $log->setMessage(sprintf("User %s (%s) loaded bitstream %s (%s) to the %s (%s) server %s.",
                        $this->getUser()->getFullName(), $this->getUser()->getUsername(),
                        $board_config->getDescription(), $board_config->getDate()->format("Y-m-d"),
                        $server->getName(),  $server->getIp(),
                        $data !== false ? 'successfully' : 'unsuccessfully'
                    ));
                    $em->persist($log);
                    $em->flush();
                } else {
                    $http_status = JsonResponse::HTTP_BAD_REQUEST;
                    $status = JsonResponse::HTTP_BAD_REQUEST;
                    $message = "There is invalid bitstream configuration";
                }
            } else {
                $http_status = JsonResponse::HTTP_BAD_REQUEST;
                $status = JsonResponse::HTTP_BAD_REQUEST;
                $message = "Unable to connect to to server invalid username/password...";
            }
        } catch (\Exception $e) {
            $http_status = JsonResponse::HTTP_BAD_REQUEST;
            $status = JsonResponse::HTTP_BAD_REQUEST;
            $message = $e->getMessage();
        }

        return new JsonResponse(['status' => $status, 'message' => $message], $http_status);
    }


    /**
     * @Method("POST")
     * @Route("/load-config", name="server__load_config")
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param Request $request
     * @return array | JsonResponse
     * @throws
     */
    public function loadConfigAction(Request $request)
    {
        if ($request->getContentType() === 'application/json' || $request->getContentType() === 'json') {
            $content = $request->getContent();
            $request->request->add(json_decode($content, true));
        }

        $id = $request->get('id');

        $em = $this->getDoctrine()->getManager();

        /** @var Reservation $reservation */
        $reservation = $em->getRepository("AppBundle:Reservation")->find($id);

        if (!$reservation) {
            return new JsonResponse(['status' => JsonResponse::HTTP_NOT_FOUND, 'message' => "Reservation by id = $id not found"], JsonResponse::HTTP_NOT_FOUND);
        }

        try {
            $client = new IFlexClient($this->get('eight_points_guzzle.client.api_server'), $reservation->getServer());

            if ($client->login()) {
                $data = $client->loadConfig($reservation->getUser(), $reservation->getReservationList());

                if ($data !== false) {
                    /** @var Serializer $serializer */
                    $serializer = $this->get('jms_serializer');
                    $data = ['status' => JsonResponse::HTTP_OK, 'message' => 'Success'];
                    $data = $serializer->serialize($data, 'json', SerializationContext::create());
                    return new JsonResponse($data, JsonResponse::HTTP_OK, [], true);
                } else {
                    $http_status = JsonResponse::HTTP_BAD_REQUEST;
                    $status = JsonResponse::HTTP_BAD_REQUEST;
                    $message = "Unfortunately can't connect to server. Please try again later...";
                    return new JsonResponse(['status' => $status, 'message' => $message], $http_status);
                }
            } else {
                $http_status = JsonResponse::HTTP_BAD_REQUEST;
                $status = JsonResponse::HTTP_BAD_REQUEST;
                $message = "Unable to connect to to server invalid username/password...";
                return new JsonResponse(['status' => $status, 'message' => $message], $http_status);
            }
        } catch (\Exception $e) {
            $http_status = JsonResponse::HTTP_BAD_REQUEST;
            $status = JsonResponse::HTTP_BAD_REQUEST;
            $message = $e->getMessage();
            return new JsonResponse(['status' => $status, 'message' => $message], $http_status);
        }
    }

    /**
     * @Method("POST")
     * @Route("/sync-ftp-users", name="server__sync_ftp_users")
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param Request $request
     * @return array | JsonResponse
     * @throws
     */
    public function syncFtpUsersAction(Request $request)
    {
        if ($request->getContentType() === 'application/json' || $request->getContentType() === 'json') {
            $content = $request->getContent();
            $request->request->add(json_decode($content, true));
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        $users = $em->getRepository("AppBundle:User")->findAll();
        $users = $this->get('jms_serializer')->serialize($users, 'json', SerializationContext::create()->setGroups(['sync_ftp']));

        $servers = $em->getRepository("AppBundle:Server")->findAll();

        /** @var Server $server */
        $errors = [];

        foreach ($servers as $server) {
            try {
                $client = new IFlexClient($this->get('eight_points_guzzle.client.api_server'), $server);

                if ($client->login()) {
                    $data = $client->syncFTPUsers($users);

                    if ($data === false) {
                        $errors[] = sprintf("Unable to sync users with server '%s'...", $server->getName());
                    }
                } else {
                    $errors[] = sprintf("Unable to connect to server '%s' invalid username/password...", $server->getName());
                }
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        $status = JsonResponse::HTTP_OK;
        $message = 'Success';

        if(count($errors) > 0) {
            $status = JsonResponse::HTTP_BAD_REQUEST;
            $message = 'Error';
        }

        return new JsonResponse(['status' => $status, 'message' => $message, 'data' => $errors], $status);
    }

    /**
     * @Method("POST")
     * @Route("/power-on-config", name="server__power_on_config")
     * @param Request $request
     * @return array | JsonResponse
     * @throws
     */
    public function powerOnConfig(Request $request)
    {
        if ($request->getContentType() === 'application/json' || $request->getContentType() === 'json') {
            $content = $request->getContent();
            $request->request->add(json_decode($content, true));
        }

        $id = $request->get('id');
        $boardConfig = $request->get('board_config');

        if (empty($id)) {
            $id = 0;
        }

        $em = $this->getDoctrine()->getManager();

        /** @var Server $server */
        $server = $em->getRepository("AppBundle:Server")->find($id);

        if (!$server) {
            return new JsonResponse(['status' => JsonResponse::HTTP_NOT_FOUND, 'message' => "Server by id = $id not found"], JsonResponse::HTTP_NOT_FOUND);
        }

        try {
            $client = new IFlexClient($this->get('eight_points_guzzle.client.api_server'), $server);

            if ($client->login()) {
                /** @var BoardConfig $board_config */
                $board_config = $em->getRepository("AppBundle:BoardConfig")->find($boardConfig[0]['id']);


                /** @var Bitstream $sea */
                $sea = $board_config->getBitstreams()->filter(function (Bitstream $entry) {
                    return $entry->getFileType() == FileTypes::SEA;
                })->first();
                /** @var Bitstream $sed */
                $sed = $board_config->getBitstreams()->filter(function (Bitstream $entry) {
                    return $entry->getFileType() == FileTypes::SED;
                })->first();

                if ($sed != null || $sea != null) {
                    $data = $client->powerOnConfig($board_config->getDate(), $board_config->getDescription(), $sea->getFile(), $sed->getFile());

                    if ($data !== false) {
                        $status = JsonResponse::HTTP_OK;
                        $message = "Success";
                        $http_status = JsonResponse::HTTP_OK;
                    } else {
                        $http_status = JsonResponse::HTTP_BAD_REQUEST;
                        $status = JsonResponse::HTTP_BAD_REQUEST;
                        $message = "Unfortunately can't connect to server. Please try again later...";
                    }
                } else {
                    $http_status = JsonResponse::HTTP_BAD_REQUEST;
                    $status = JsonResponse::HTTP_BAD_REQUEST;
                    $message = "There is invalid bitstream configuration";
                }
            } else {
                $http_status = JsonResponse::HTTP_BAD_REQUEST;
                $status = JsonResponse::HTTP_BAD_REQUEST;
                $message = "Unable to connect to to server invalid username/password...";
            }
        } catch (\Exception $e) {
            $http_status = JsonResponse::HTTP_BAD_REQUEST;
            $status = JsonResponse::HTTP_BAD_REQUEST;
            $message = $e->getMessage();
        }

        return new JsonResponse(['status' => $status, 'message' => $message], $http_status);
    }

}