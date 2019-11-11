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
 * Class UserController
 * @package AppBundle\Controller\Rest
 * @Route("/api/v1.0/user")
 */
class UserController extends Controller
{
    /**
     * This function is used to get user list
     *
     * @ApiDoc(
     *  resource=true,
     *  section="User",
     *  description="This function is used to get user list",
     *  statusCodes={
     *         200="Returned when get user list",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("GET")
     * @Route("/list/{type}", name="user_list", requirements={"type"="\d+"})
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param $type
     * @return array | JsonResponse
     */
    public function listAction($type = null)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var ArrayCollection $users */
        $users = $em->getRepository("AppBundle:User")->findByCategory($type);

        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        $data = ['status' => JsonResponse::HTTP_OK, 'message' => 'Success', 'data' => ['users' => $users]];

        $usersContent = $serializer->serialize($data, 'json', SerializationContext::create()->setGroups(['user_list']));

        $response = new JsonResponse();

        $response->setContent($usersContent);

        return $response;
    }

    /**
     * This function is used to add new user
     *
     * @ApiDoc(
     *  resource=true,
     *  section="User",
     *  description="This function is used to add new user",
     *  statusCodes={
     *          200="Returned when add new user",
     *          403="Forbidden",
     *          400="Bad request"
     *     },
     *     parameters={
     *          {"name"="first_name", "dataType"="string", "required"=true, "description"="FirstName"},
     *          {"name"="last_name", "dataType"="string", "required"=true, "description"="LastName"},
     *          {"name"="user_name", "dataType"="string", "required"=true, "description"="Username"},
     *          {"name"="password", "dataType"="string", "required"=true, "description"="Password"},
     *          {"name"="roles_select", "dataType"="array", "required"=true, "description"="Roles"},
     *          {"name"="email", "dataType"="email", "required"=true, "description"="Email"},
     *          {"name"="organization", "dataType"="string",  "required"=true, "description"="Organization"},
     *          {"name"="phone", "dataType"="string",  "required"=true, "Phone"},
     *          {"name"="disk_quota", "dataType"="integer",  "required"=true, "Disk Quota"},
     *          {"name"="api_key", "dataType"="string",  "required"=true, "Api Key"},
     *          {"name"="enabled", "dataType"="boolean",  "required"=true, "Enabled"},
     *          {"name"="user_category", "dataType"="number",  "required"=true, "Category"},
     *          {"name"="country", "dataType"="number",  "required"=false, "Country"},
     *          {"name"="state", "dataType"="number",  "required"=true, "State"},
     *          {"name"="city", "dataType"="number",  "required"=true, "City"},
     *          {"name"="address", "dataType"="string",  "required"=true, "Address"},
     *          {"name"="zip", "dataType"="string",  "required"=true, "Zip"}
     *     }
     * )
     *
     * @Method("POST")
     * @Route("/add", name="user_add")
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param Request $request
     * @return array | JsonResponse
     * @throws
     */
    public function addAction(Request $request)
    {
        try {
            if ($request->getContentType() === 'application/json' || $request->getContentType() === 'json') {
                $content = $request->getContent();
                $request->request->add(json_decode($content, true));
            }

            $firstName    = $request->get('first_name');
            $lastName     = $request->get('last_name');
            $username     = $request->get('username');
            $password     = $request->get('password');
            $roles        = $request->get('roles_select');
            $email        = $request->get('email');
            $organization = $request->get('organization');
            $phone        = $request->get('phone');
            $diskQuota    = $request->get('disk_quota');
            $apiKey       = $request->get('api_key');
            $enabled      = $request->get('enabled');
            $userCategory = $request->get('user_category');
            $countryId    = $request->get('country');
            $stateId      = $request->get('state');
            $cityId       = $request->get('city');
            $address      = $request->get('address');
            $zip          = $request->get('zip');

            /** @var User $user */
            $user = new User();
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setUsername($username);
            $user->setPlainPassword($password);
            $user->setEmail($email);
            $user->setOrganization($organization);
            $user->setPhone($phone);
            $user->setDiskQuota($diskQuota);
            $user->setApiKey($apiKey);

            if (!$enabled) {
                $enabled = false;
            }

            $isAdmin  = true;
            $verified = true;

            if ($userCategory == 1) {
                $roles    = ["ROLE_CUSTOMER"];
                $isAdmin  = false;
                $verified = false;
            } elseif ($roles) {
                $roles = array_map(function ($item) {
                    return $item['itemName'];
                }, $roles);
            }

            $user->setEnabled($enabled);
            $user->setAdmin($isAdmin);
            $user->setVerified($verified);
            $user->setRoles($roles);

            /** @var ValidatorInterface $validator */
            $validator    = $this->get('validator');
            $errors       = $validator->validate($user, null, ['add_user']);
            $returnErrors = [];

            /** @var EntityManager $em */
            $em                = $this->getDoctrine()->getManager();
            $duplicateUsername = $em->getRepository("AppBundle:User")->findOneBy(array('username' => $username));
            $duplicateEmail    = $em->getRepository("AppBundle:User")->findOneBy(array('email' => $email));

            if ($countryId) {
                $country = $em->getRepository("AppBundle:Country")->find($countryId);
            }

            if ($stateId) {
                $state = $em->getRepository("AppBundle:State")->find($stateId);
            }

            if ($cityId) {
                $city = $em->getRepository("AppBundle:City")->find($cityId);
            }

            $user->setCountry($country ?? null);
            $user->setState($state ?? null);
            $user->setCity($city ?? null);
            $user->setAddress($address ?? null);
            $user->setZip($zip ?? null);

            if ($errors->count() > 0) {
                foreach ($errors as $error) {
                    $returnErrors[$error->getPropertyPath()] = $error->getMessage();
                }

                throw new AddUserException(
                    'Invalid data',
                    Response::HTTP_BAD_REQUEST,
                    ['errors' => $returnErrors]
                );
            }

            if ($duplicateUsername) {
                throw new AddUserException(
                    'Duplicate Customer with username ' . $username,
                    Response::HTTP_BAD_REQUEST
                );
            }

            if ($duplicateEmail) {
                throw new AddUserException(
                    'Duplicate Customer with email ' . $email,
                    Response::HTTP_BAD_REQUEST
                );
            }

            try {
                $em->getConnection()->beginTransaction();
                $em->persist($user);
                $em->flush();

                if (!$user->isVerified()) {
                    $mailer = new Mailer($this->container);
                    $mailer->inviteCustomer($user, $request->getSchemeAndHttpHost());
                }

                $em->getConnection()->commit();

                $status   = Response::HTTP_OK;
                $response = ['status' => Response::HTTP_OK, 'message' => 'Success'];
            } catch (\Exception $e) {
                $em->getConnection()->rollBack();

                throw new AddUserException(
                    $e->getMessage(),
                    Response::HTTP_BAD_REQUEST,
                    ['errors' => $returnErrors]
                );
            }
        } catch (\Throwable $e) {
            $status = $e->getCode();
            $response = [
                'status' => $status,
                'message' => $e->getMessage()
            ];

            if ($e instanceof AddUserException && !empty($e->getData())) {
                $response['data'] = $e->getData();
            }
        }

        return new JsonResponse($response, $status);
    }

    /**
     * This function is used to edit user
     *
     * @ApiDoc(
     *  resource=true,
     *  section="User",
     *  description="This function is used to edit user",
     *  statusCodes={
     *         200="Returned when edit user",
     *         403="Forbidden",
     *         400="Bad request",
     *         404="Not found"
     *     },
     *     parameters={
     *          {"name"="first_name", "dataType"="string", "required"=true, "description"="FirstName"},
     *          {"name"="last_name", "dataType"="string", "required"=true, "description"="LastName"},
     *          {"name"="user_name", "dataType"="string", "required"=true, "description"="Username"},
     *          {"name"="roles_select", "dataType"="array", "required"=true, "description"="Roles"},
     *          {"name"="email", "dataType"="email", "required"=true, "description"="Email"},
     *          {"name"="organization", "dataType"="string",  "required"=true, "description"="Organization"},
     *          {"name"="phone", "dataType"="string",  "required"=true, "Phone"},
     *          {"name"="disk_quota", "dataType"="integer",  "required"=true, "Disk Quota"},
     *          {"name"="api_key", "dataType"="string",  "required"=true, "Api Key"},
     *          {"name"="enabled", "dataType"="boolean",  "required"=true, "Enabled"},
     *          {"name"="country", "dataType"="number",  "required"=false, "Country"},
     *          {"name"="state", "dataType"="number",  "required"=true, "State"},
     *          {"name"="city", "dataType"="number",  "required"=true, "City"},
     *          {"name"="address", "dataType"="string",  "required"=true, "Address"},
     *          {"name"="zip", "dataType"="string",  "required"=true, "Zip"}
     *      }
     * )
     *
     * @Method("PUT")
     * @Route("/edit/{id}", name="user_edit", requirements={"id"="\d+"})
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param int $id
     * @param Request $request
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
        /** @var User $user */
        $user = $em->getRepository("AppBundle:User")->findOneBy(array('id' => $id));

        if ($user) {
            $firstName = $request->get('first_name');
            $lastName = $request->get('last_name');
            $username = $request->get('username');
            $roles = $request->get('roles_select');
            $email = $request->get('email');
            $organization = $request->get('organization');
            $phone = $request->get('phone');
            $diskQuota = $request->get('disk_quota');
            $apiKey = $request->get('api_key');
            $enabled = $request->get('enabled');
            $userCategory = $request->get('user_category');
            $countryId    = $request->get('country');
            $stateId      = $request->get('state');
            $cityId       = $request->get('city');
            $address      = $request->get('address');
            $zip          = $request->get('zip');

            if (!$enabled) {
                $enabled = false;
            }

            if ($userCategory == 1) {
                $roles = ["ROLE_CUSTOMER"];
            } else {
                if ($roles) {
                    $roles = array_map(function ($item) {
                        return $item['itemName'];
                    }, $roles);
                }
            }

            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setUsername($username);
            $user->setRoles($roles);
            $user->setEmail($email);
            $user->setOrganization($organization);
            $user->setPhone($phone);
            $user->setDiskQuota($diskQuota);
            $user->setApiKey($apiKey);
            $user->setEnabled($enabled);

            if ($countryId) {
                $country = $em->getRepository("AppBundle:Country")->find($countryId);
            }

            if ($stateId) {
                $state = $em->getRepository("AppBundle:State")->find($stateId);
            }

            if ($cityId) {
                $city = $em->getRepository("AppBundle:City")->find($cityId);
            }

            $user->setCountry($country ?? null);
            $user->setState($state ?? null);
            $user->setCity($city ?? null);
            $user->setAddress($address ?? null);
            $user->setZip($zip ?? null);

            /** @var ValidatorInterface $validator */
            $validator = $this->get('validator');

            $errors = $validator->validate($user, null, ['edit_user']);

            $returnErrors = [];

            if ($errors->count() > 0) {
                foreach ($errors as $error) {
                    $returnErrors[$error->getPropertyPath()] = $error->getMessage();
                }

                $status = Response::HTTP_BAD_REQUEST;
                $response = ['status' => $status, 'message' => 'Invalid data', 'data' => ['errors' => $returnErrors]];
            } else {

                $em->getConnection()->beginTransaction();

                try {
                    $em->persist($user);
                    $em->flush();

                    $em->getConnection()->commit();

                    $status = Response::HTTP_OK;
                    $response = ['status' => Response::HTTP_OK, 'message' => 'Success'];
                } catch (\Exception $e) {
                    $em->getConnection()->rollBack();

                    $status = Response::HTTP_BAD_REQUEST;
                    $response = ['status' => $e->getCode(), 'message' => $e->getMessage()];
                }
            }
        } else {
            $status = Response::HTTP_NOT_FOUND;
            $response = ['status' => Response::HTTP_NOT_FOUND, 'message' => "User by id` $id not found"];
        }

        return new JsonResponse($response, $status);
    }


    /**
     * This function is used to edit user
     *
     * @ApiDoc(
     *  resource=true,
     *  section="User",
     *  description="This function is used to edit user",
     *  statusCodes={
     *         200="Returned when edit user",
     *         403="Forbidden",
     *         400="Bad request",
     *         404="Not found"
     *     },
     *     parameters={
     *          {"name"="first_name", "dataType"="string", "required"=true, "description"="FirstName"},
     *          {"name"="last_name", "dataType"="string", "required"=true, "description"="LastName"},
     *          {"name"="email", "dataType"="email", "required"=true, "description"="Email"},
     *          {"name"="organization", "dataType"="string",  "required"=false, "description"="Organization"},
     *          {"name"="phone", "dataType"="string",  "required"=true, "Phone"},
     *          {"name"="disk_quota", "dataType"="integer",  "required"=false, "Disk Quota"},
     *          {"name"="api_key", "dataType"="string",  "required"=true, "Api Key"},
     *          {"name"="country", "dataType"="number",  "required"=false, "Country"},
     *          {"name"="state", "dataType"="number",  "required"=true, "State"},
     *          {"name"="city", "dataType"="number",  "required"=true, "City"},
     *          {"name"="address", "dataType"="string",  "required"=true, "Address"},
     *          {"name"="zip", "dataType"="string",  "required"=true, "Zip"}
     *     }
     * )
     *
     * @Method("PUT")
     * @Route("/edit-profile/{id}", name="user_profile_edit", requirements={"id"="\d+"})
     *
     * @param int $id
     * @param Request $request
     * @return array | JsonResponse
     * @throws
     */
    public function editProfileAction($id, Request $request)
    {
        if ($request->getContentType() === 'application/json' || $request->getContentType() === 'json') {
            $content = $request->getContent();
            $request->request->add(json_decode($content, true));
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var User $user */
        $user = $em->getRepository("AppBundle:User")->find($id);

        if ($id != $this->get('security.token_storage')->getToken()->getUser()->getId()) {
            $status   = Response::HTTP_BAD_REQUEST;
            $response = [
                'status'  => $status,
                'message' => 'Authentication Error'
            ];
        } elseif ($user) {

            $firstName = $request->get('first_name');
            $lastName = $request->get('last_name');
            $email = $request->get('email');
            $organization = $request->get('organization');
            $phone = $request->get('phone');
            $diskQuota = $request->get('disk_quota');
            $apiKey = $request->get('api_key');
            $countryId    = $request->get('country');
            $stateId      = $request->get('state');
            $cityId       = $request->get('city');
            $address      = $request->get('address');
            $zip          = $request->get('zip');


            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setEmail($email);
            $user->setOrganization($organization);
            $user->setPhone($phone);

            if ($user->isAdmin()) {
                $user->setDiskQuota($diskQuota);
                $user->setApiKey($apiKey);
            }

            if ($countryId) {
                $country = $em->getRepository("AppBundle:Country")->find($countryId);
            }

            if ($stateId) {
                $state = $em->getRepository("AppBundle:State")->find($stateId);
            }

            if ($cityId) {
                $city = $em->getRepository("AppBundle:City")->find($cityId);
            }

            $user->setCountry($country ?? null);
            $user->setState($state ?? null);
            $user->setCity($city ?? null);
            $user->setAddress($address ?? null);
            $user->setZip($zip ?? null);

            /** @var ValidatorInterface $validator */
            $validator = $this->get('validator');

            $errors = $validator->validate($user, null, ['edit_profile']);

            $returnErrors = [];

            if ($errors->count() > 0) {
                foreach ($errors as $error) {
                    $returnErrors[$error->getPropertyPath()] = $error->getMessage();
                }

                $status = Response::HTTP_BAD_REQUEST;
                $response = ['status' => $status, 'message' => 'Invalid data', 'data' => ['errors' => $returnErrors]];
            } else {

                $em->getConnection()->beginTransaction();

                try {
                    $em->persist($user);
                    $em->flush();

                    $em->getConnection()->commit();

                    $status = Response::HTTP_OK;

                    /** @var Serializer $serializer */
                    $serializer = $this->get('jms_serializer');


                    $response = ['status' => $status, 'message' => 'Success', 'data' => ['user' => $user]];
                    $usersContent = $serializer->serialize($response, 'json', SerializationContext::create()->setGroups(['user']));

                    $response = new JsonResponse();
                    $response->setContent($usersContent);

                    return $response;

                } catch (\Exception $e) {
                    $em->getConnection()->rollBack();

                    $status = Response::HTTP_BAD_REQUEST;
                    $response = ['status' => $e->getCode(), 'message' => $e->getMessage()];
                }
            }
        } else {
            $status = Response::HTTP_NOT_FOUND;
            $response = ['status' => Response::HTTP_NOT_FOUND, 'message' => "User by id` $id not found"];
        }

        return new JsonResponse($response, $status);
    }

    /**
     * This function is used to remove user by id
     *
     * @ApiDoc(
     *  resource=true,
     *  section="User",
     *  statusCodes={
     *         200="Returned when user was removed",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("DELETE")
     * @Route("/remove/{id}", name="user_delete", requirements={"id"="\d+"})
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param $id
     * @return array | JsonResponse
     * @throws
     */
    public function removeUserAction($id)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var User $users */
        $user = $em->getRepository("AppBundle:User")->find($id);

        if ($user) {

            $em->getConnection()->beginTransaction();

            try {
                $em->remove($user);
                $em->flush();

                $em->getConnection()->commit();

                $status = Response::HTTP_OK;
                $response = ['status' => Response::HTTP_OK, 'message' => "User removed"];
            } catch (\Exception $e) {
                $em->getConnection()->rollBack();

                $status = Response::HTTP_BAD_REQUEST;
                $response = ['status' => $e->getCode(), 'message' => $e->getMessage()];
            }
        } else {
            $status = Response::HTTP_NOT_FOUND;
            $response = ['status' => Response::HTTP_NOT_FOUND, 'message' => "User by id` $id not found"];
        }

        return new JsonResponse($response, $status);
    }

    /**
     * This function is used to get user by id
     *
     * @ApiDoc(
     *  resource=true,
     *  section="User",
     *  description="This function is used to get user by id",
     *  statusCodes={
     *         200="Returned when get user list",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("GET")
     * @Route("/{id}", name="get_user")
     * @param $id
     * @return array | JsonResponse
     */
    public function getUserAction($id)
    {
        if (!$id) {
            return new JsonResponse(['status' => JsonResponse::HTTP_BAD_REQUEST, 'message' => 'Id param is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var ArrayCollection $user */
        $user = $em->getRepository("AppBundle:User")->find($id);

        if (!$user) {
            return new JsonResponse(['status' => JsonResponse::HTTP_NOT_FOUND, 'message' => "User by id=$id not found"], JsonResponse::HTTP_NOT_FOUND);
        }

        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        $data = ['status' => JsonResponse::HTTP_OK, 'message' => 'Success', 'data' => ['user' => $user]];

        $usersContent = $serializer->serialize($data, 'json', SerializationContext::create()->setGroups(['user']));

        $response = new JsonResponse();
        $response->setContent($usersContent);

        return $response;
    }

    /**
     * This function is used to get user by password recovery hash
     *
     * @ApiDoc(
     *  resource=true,
     *  section="User",
     *  description="This function is used to get user by password recovery hash",
     *  statusCodes={
     *         200="Returned when user have password recovery request",
     *         403="Forbidden",
     *         400="Bad request"
     *     }
     * )
     *
     * @Method("GET")
     * @Route("/hash/{hash}", name="get_user_by_hash")
     * @param $hash
     * @return array | JsonResponse
     */
    public function getUserByHashAction($hash)
    {
        if (empty($hash)) {
            return new JsonResponse(['status' => JsonResponse::HTTP_BAD_REQUEST, 'message' => 'Hash param is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var ArrayCollection $user */
        $user = $em->getRepository("AppBundle:User")->findOneBy(['passwordRecoveryHash' => $hash]);

        if (!$user) {
            return new JsonResponse(['status' => JsonResponse::HTTP_NOT_FOUND, 'message' => "Hash is expired or not exist"], JsonResponse::HTTP_NOT_FOUND);
        }

        /** @var Serializer $serializer */
        $serializer = $this->get('jms_serializer');

        $data = [
            'status'  => JsonResponse::HTTP_OK,
            'message' => 'Success',
            'data'    => ['user' => $user]
        ];

        $usersContent = $serializer->serialize($data, 'json', SerializationContext::create()->setGroups(['user_by_hash']));

        $response = new JsonResponse();
        $response->setContent($usersContent);

        return $response;
    }
}