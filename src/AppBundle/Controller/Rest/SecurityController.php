<?php

namespace AppBundle\Controller\Rest;

use AppBundle\Controller\Rest\Exception\SignupException;
use AppBundle\Entity\OAuthServer\AccessToken;
use AppBundle\Entity\OAuthServer\Client;
use AppBundle\Entity\OAuthServer\RefreshToken;
use AppBundle\Entity\Portal\Logs;
use AppBundle\Entity\Portal\User;
use AppBundle\Model\Log\LogType;
use AppBundle\Model\User\UserRole;
use AppBundle\Util\Mailer;
use Doctrine\ORM\EntityManager;
use JMS\Serializer\SerializationContext;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use OAuth2\IOAuth2Storage;
use OAuth2\OAuth2;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use JMS\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class SecurityController
 * @package AppBundle\Controller\Rest
 */
class SecurityController extends Controller
{
    /**
     * This function is used to login user
     *
     * @ApiDoc(
     *  resource=true,
     *  section="User",
     *  description="This function is used to login user",
     *  statusCodes={
     *         200="Returned when was login",
     *         403="Forbidden",
     *         400="Bad request",
     *         401="Unauthorized"
     *     },
     * parameters={
     *      {"name"="username", "dataType"="string", "required"=true, "description"="User's username"},
     *      {"name"="password", "dataType"="password", "required"=true, "description"="User's password"},
     * }
     * )
     *
     * @Method("POST")
     * @Route("/security/login", name="security_login")
     * @param $request
     * @return array | JsonResponse
     * @throws
     */
    public function postLoginAction(Request $request)
    {
        //check if request content type is json
        if ($request->getContentType() === 'application/json' || $request->getContentType() === 'json') {

            //get content and add it in request after json decode
            $content = $request->getContent();
            $request->request->add(json_decode($content, true));
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        //get user credentials
        $username = $request->get('username');
        $password = $request->get('password');

        /** @var User $user */
        $user = $em->getRepository("AppBundle:User")->findByUsernameOrEmail($username);

        if ($user) {

            $enabled = $user->getEnabled();

            if ($enabled) {

                /** @var EncoderFactory $encoderService */
                $encoderService = $this->get('security.encoder_factory');
                $encoder = $encoderService->getEncoder($user);

                //check password is valid
                if ($encoder->isPasswordValid($user->getPassword(), $password, $user->getSalt())) {
                    /** @var OAuth2 $server */
                    $server = $this->container->get('fos_oauth_server.server');
                    /** @var Client $oauthClient */
                    $oauthClient = $em->getRepository('AppBundle\Entity\OAuthServer\Client')->find(1);

                    /** @var AccessToken $accessToken */
                    $accessToken = $em->getRepository('AppBundle\Entity\OAuthServer\AccessToken')->findOneBy(['user' => $user->getId()]);

                    if ($accessToken) {

                        /** @var IOAuth2Storage $storage */
                        $storage = $this->container->get('fos_oauth_server.storage');

                        //Get the stored token data (from the implementing subclass)
                        $token = $storage->getAccessToken($accessToken->getToken());

                        //Check token expiration
                        if ($token->hasExpired()) {

                            /** @var RefreshToken $refreshToken */
                            $refreshToken = $em->getRepository('AppBundle\Entity\OAuthServer\RefreshToken')->findOneBy(['user' => $user->getId()]);

                            //remove old tokens
                            $em->remove($accessToken);
                            $em->remove($refreshToken);

                            //create new token
                            $token = $server->createAccessToken($oauthClient, $user);

                        } else {
                            $token = ['access_token' => $accessToken->getToken()];
                        }

                    } else {
                        //create new token
                        $token = $server->createAccessToken($oauthClient, $user);
                    }

                    $log = new Logs();
                    $log->setDateTime(new \DateTime());
                    $log->setUser($user);
                    $log->setType(LogType::AUTH);
                    $log->setMessage(sprintf("User %s (%s) logged in.", $user->getFullName(), $user->getUsername()));
                    $em->persist($log);
                    $em->flush();

                    /** @var Serializer $serializer */
                    $serializer = $this->get('jms_serializer');

                    //generate data for json response
                    $data = ['status' => JsonResponse::HTTP_OK, 'message' => 'Success', 'data' => ['token' => $token, 'user' => $user]];

                    $userContent = $serializer->serialize($data, 'json', SerializationContext::create()->setGroups(['user']));

                    //create new json Response
                    $response = new JsonResponse();

                    //set data in response content
                    $response->setContent($userContent);

                    return $response;

                } else {
                    $status = Response::HTTP_UNAUTHORIZED;
                    $response = ['status' => Response::HTTP_UNAUTHORIZED, 'message' => 'The username or password is invalid!'];
                }
            } else {
                $status = Response::HTTP_UNAUTHORIZED;
                $response = ['status' => Response::HTTP_UNAUTHORIZED, 'message' => 'The user account is disabled.'];
            }
        } else {
            $status = Response::HTTP_UNAUTHORIZED;
            $response = ['status' => Response::HTTP_UNAUTHORIZED, 'message' => 'The username or password is invalid!'];
        }

        return new JsonResponse($response, $status);
    }

    /**
     * This function is used to logout process
     *
     * @ApiDoc(
     *  resource=true,
     *  section="Reservation",
     *  statusCodes={
     *         200="Returned when user was removed",
     *         401="Expired User token"
     *     }
     * )
     *
     * @Method("DELETE")
     * @Route("/api/v1.0/security/logout", name="security_logout")
     *
     * @return JsonResponse
     * @throws
     */
    public function logoutAction()
    {
        try {
            /** @var EntityManager $em */
            $em = $this->getDoctrine()->getManager();

            $user = $this->getUser();

            $log = new Logs();
            $log->setDateTime(new \DateTime());
            $log->setUser($this->getUser());
            $log->setType(LogType::LOGOUT);
            $log->setMessage(sprintf("User %s (%s) logout from portal", $user->getFullName(), $user->getUsername()));
            $em->persist($log);
            $em->flush();

            $this->get('security.token_storage')->setToken(null);

            $status = JsonResponse::HTTP_OK;
            $response = ['status' => $status, 'message' => "Successfully logout"];
        } catch (\Throwable $e) {
            $status = Response::HTTP_UNAUTHORIZED;
            $response = ['status' => Response::HTTP_UNAUTHORIZED, 'message' => 'Expired User token'];
        }

        return new JsonResponse($response, $status);
    }

    /**
     * This function is used to login user
     *
     * @ApiDoc(
     *  resource=true,
     *  section="User",
     *  description="This function is used to login user",
     *  statusCodes={
     *         200="Returned when was login",
     *         403="Forbidden",
     *         400="Bad request",
     *         401="Unauthorized"
     *     },
     *     parameters={
     *          {"name"="firstName", "dataType"="string", "required"=true, "description"="User's First Name"},
     *          {"name"="lastName", "dataType"="string", "required"=true, "description"="User's Last Name"},
     *          {"name"="email", "dataType"="string", "required"=true, "description"="User's Email"},
     *          {"name"="password", "dataType"="password", "required"=true, "description"="User's password"},
     *          {"name"="rePassword", "dataType"="password", "required"=true, "description"="User's repeat password"}
     *     }
     * )
     *
     * @Method("POST")
     * @Route("/security/signup", name="security_signup")
     * @param $request
     * @return array | JsonResponse
     * @throws
     */
    public function signupAction(Request $request)
    {
        try {
            //check if request content type is json
            if ($request->getContentType() === 'application/json' || $request->getContentType() === 'json') {

                //get content and add it in request after json decode
                $content = $request->getContent();
                $request->request->add(json_decode($content, true));
            }

            /** @var EntityManager $em */
            $em = $this->getDoctrine()->getManager();

            //get user credentials
            $firstName  = $request->get('firstName');
            $lastName   = $request->get('lastName');
            $email      = $request->get('email');
            $password   = $request->get('password');
            $rePassword = $request->get('rePassword');

            /** @var User $user */
            $user = $em->getRepository("AppBundle:User")->findOneBy(array('email' => $email));

            if ($user) {
                throw new SignupException(
                    'User with this email address already exist',
                    Response::HTTP_BAD_REQUEST
                );
            }

            if ($password != $rePassword) {
                throw new SignupException(
                    'Password and repeat password don\'t match',
                    Response::HTTP_BAD_REQUEST
                );
            }

            /** @var User $user */
            $user = new User();
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setUsername(strtolower($firstName) . time());
            $user->setPlainPassword($password);
            $user->setRoles(['ROLE_CUSTOMER']);
            $user->setEmail($email);
            $user->setEnabled(false);
            $user->setAdmin(false);

            /** @var ValidatorInterface $validator */
            $validator    = $this->get('validator');
            $errors       = $validator->validate($user, null, ["signup"]);
            $returnErrors = [];

            if ($errors->count() > 0) {
                foreach ($errors as $error) {
                    $returnErrors[$error->getPropertyPath()] = $error->getMessage();
                }

                throw new SignupException(
                    'Invalid data',
                    Response::HTTP_BAD_REQUEST,
                    [
                        'errors' => $returnErrors
                    ]
                );
            }

            /** @var EntityManager $em */
            $em = $this->getDoctrine()->getManager();

            try {
                $em->getConnection()->beginTransaction();
                $em->persist($user);
                $em->flush();
                $em->getConnection()->commit();
            } catch (\Exception $e) {
                $em->getConnection()->rollBack();

                throw new SignupException(
                    'System Error',
                    Response::HTTP_BAD_REQUEST
                );
            }

            $status   = Response::HTTP_CREATED;
            $response = [
                'message' => 'Please waiting to approval',
                'status'  => $status
            ];
        } catch (\Throwable $e) {
            $status = $e->getCode();
            $response = [
                'status' => $status,
                'message' => $e->getMessage()
            ];

            if ($e instanceof SignupException && !empty($e->getData())) {
                $response['data'] = $e->getData();
            }
        }

        return new JsonResponse($response, $status);
    }

    /**
     * This function is used to change password
     *
     * @ApiDoc(
     *  resource=true,
     *  section="User",
     *  description="This function is used to change password",
     *  statusCodes={
     *         200="Returned when password was changed",
     *         403="Forbidden",
     *         400="Bad request",
     *         401="Unauthorized"
     *     },
     * parameters={
     *      {"name"="password", "dataType"="password", "required"=true, "description"="User's current password"},
     *      {"name"="newPassword", "dataType"="password", "required"=true, "description"="User's new password"},
     *      {"name"="confirmPassword", "dataType"="password", "required"=true, "description"="User's confirm password"}
     * }
     * )
     *
     * @Method("PUT")
     * @Route("/api/v1.0/security/change-password", name="security_change_password")
     * @param $request
     * @return array | JsonResponse
     * @throws
     */
    public function changePasswordAction(Request $request)
    {
        //check if request content type is json
        if ($request->getContentType() === 'application/json' || $request->getContentType() === 'json') {

            //get content and add it in request after json decode
            $content = $request->getContent();
            $request->request->add(json_decode($content, true));
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        //get user credentials
        $userId = $request->get('id');
        $password = $request->get('password');
        $newPassword = $request->get('newPassword');
        $confirmPassword = $request->get('confirmPassword');

        /** @var User $user */
        $user = $em->getRepository("AppBundle:User")->find($userId);

        if ($user) {

            $allErrors = [];

            /** @var EncoderFactory $encoderService */
            $encoderService = $this->get('security.encoder_factory');
            $encoder = $encoderService->getEncoder($user);

            //check password is valid
            if ($encoder->isPasswordValid($user->getPassword(), $password, $user->getSalt())) {
                //check if new and confirm password is equal
                if ($newPassword === $confirmPassword) {
                    // check with old password
                    if ($newPassword !== $password) {
                        //encode and save new password
                        $user->setPlainPassword($newPassword);

                        if (!$user->isAdmin()) {
                            $user->setVerified(true);
                        }

                        $em->getConnection()->beginTransaction();

                        try {
                            $em->persist($user);
                            $em->flush();

                            $em->getConnection()->commit();

                            $status = Response::HTTP_CREATED;
                            $response = ['status' => $status, 'message' => 'Success'];

                        } catch (\Exception $e) {
                            $em->getConnection()->rollBack();

                            $status = Response::HTTP_BAD_REQUEST;
                            $response = ['status' => $e->getCode(), 'message' => $e->getMessage()];
                        }
                    } else {
                        $allErrors['newPassword'] = 'New password must be different from last password';
                        $status = Response::HTTP_BAD_REQUEST;
                        $response = ['status' => Response::HTTP_BAD_REQUEST, 'message' => $allErrors['newPassword'], 'data' => ['errors' => $allErrors]];
                    }
                } else {
                    $allErrors['newPassword'] = 'New password is not confirmed';
                    $status = Response::HTTP_BAD_REQUEST;
                    $response = ['status' => Response::HTTP_BAD_REQUEST, 'message' => $allErrors['newPassword'], 'data' => ['errors' => $allErrors]];
                }

            } else {
                $allErrors['password'] = 'Invalid current password';
                $status = Response::HTTP_BAD_REQUEST;
                $response = ['status' => Response::HTTP_BAD_REQUEST, 'message' => $allErrors['password'], 'data' => ['errors' => $allErrors]];
            }

        } else {
            $allErrors['userNotFound'] = "User by id $userId not found";
            $status = Response::HTTP_NOT_FOUND;
            $response = ['status' => Response::HTTP_NOT_FOUND, 'message' => $allErrors['userNotFound'], 'data' => ['errors' => $allErrors]];
        }

        return new JsonResponse($response, $status);
    }

    /**
     * This function is used to forgot password
     *
     * @ApiDoc(
     *  resource=true,
     *  section="User",
     *  description="This function is used to forgot password",
     *  statusCodes={
     *         200="Returned when email was sent",
     *         403="Forbidden",
     *         400="Bad request",
     *         401="Unauthorized"
     *     },
     *  parameters={
     *     {"name"="email", "dataType"="email", "required"=true, "description"="Email"}
     *  }
     * )
     *
     * @Method("POST")
     * @Route("/security/forgot-password", name="security_forgot_password")
     * @param $request
     * @return array | JsonResponse
     * @throws
     */
    public function forgotPasswordAction(Request $request)
    {
        //check if request content type is json
        if ($request->getContentType() === 'application/json' || $request->getContentType() === 'json') {
            $content = $request->getContent();
            $request->request->add(json_decode($content, true));
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        // get user credentials
        $email = $request->get('email');

        /** @var User $user */
        $user = $em->getRepository("AppBundle:User")->findOneBy(['email' => $email]);

        if ($user) {
            $user->setPasswordRecoveryHash($email);

            try {
                $em->getConnection()->beginTransaction();
                $em->persist($user);
                $em->flush();

                $mailer = new Mailer($this->container);
                $mailer->sendPasswordRecoveryLink($user, $request->getSchemeAndHttpHost());

                $em->getConnection()->commit();

                $status = Response::HTTP_CREATED;
                $response = ['status' => $status, 'message' => 'Password recovery link sent, please check email.'];
            } catch (\Exception $e) {
                $em->getConnection()->rollBack();

                $status = Response::HTTP_BAD_REQUEST;
                $response = ['status' => $e->getCode(), 'message' => $e->getMessage()];
            }

        } else {
            $status   = Response::HTTP_NOT_FOUND;
            $response = ['status' => Response::HTTP_NOT_FOUND, 'message' => "User by id $email not found"];
        }

        return new JsonResponse($response, $status);
    }


    // TODO: add action for resetting password
    /**
     * This function is used to change password
     *
     * @ApiDoc(
     *      resource=true,
     *      section="User",
     *      description="This function is used to register customer",
     *      statusCodes={
     *             201="Returned when registration success",
     *          400="Bad request"
     *      },
     *      parameters={
     *          {"name"="password", "dataType"="password", "required"=true, "description"="User's current password"},
     *          {"name"="newPassword", "dataType"="password", "required"=true, "description"="User's new password"},
     *          {"name"="confirmPassword", "dataType"="password", "required"=true, "description"="User's confirm password"}
     *      }
     * )
     *
     * @Method("PUT")
     * @Route("/api/v1.0/security/reset-password/{id}", name="security_reset_password", requirements={"id"="\d+"})
     * @param $request
     * @return array | JsonResponse
     * @throws
     */
    public function resetPasswordAction($id, Request $request)
    {
        //check if request content type is json
        if ($request->getContentType() === 'application/json' || $request->getContentType() === 'json') {

            //get content and add it in request after json decode
            $content = $request->getContent();
            $request->request->add(json_decode($content, true));
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var User $user */
        $user = $em->getRepository("AppBundle:User")->find($id);

        if ($user) {
            $password = $this->random_password(8);
            $user->setPlainPassword($password);

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $mailer = new Mailer($this->container);
            $mailer->notifyCredentials($user);

            $status = Response::HTTP_OK;
            $response = ['status' => Response::HTTP_NOT_FOUND, 'message' => "Success"];
        } else {
            $status = Response::HTTP_NOT_FOUND;
            $response = ['status' => Response::HTTP_NOT_FOUND, 'message' => "User by id $id not found"];
        }

        return new JsonResponse($response, $status);
    }

    /**
     * This function is used to confirm password with hash
     *
     * @ApiDoc(
     *      resource=true,
     *      section="User",
     *      description="This function is used to register customer",
     *      statusCodes={
     *             201="Returned when registration success",
     *          400="Bad request"
     *      },
     *      parameters={
     *          {"name"="hash", "dataType"="string", "required"=true, "description"="User's hash code"},
     *          {"name"="newPassword", "dataType"="password", "required"=true, "description"="User's new password"},
     *          {"name"="confirmPassword", "dataType"="password", "required"=true, "description"="User's confirm password"}
     *      }
     * )
     *
     * @Method("PUT")
     * @Route("/api/v1.0/security/confirm-password", name="security_confirm_password")
     * @param $request
     * @return array | JsonResponse
     * @throws
     */
    public function confirmPasswordAction(Request $request)
    {
        try {
            if ($request->getContentType() === 'application/json' || $request->getContentType() === 'json') {

                //get content and add it in request after json decode
                $content = $request->getContent();
                $request->request->add(json_decode($content, true));
            }

            /** @var EntityManager $em */
            $em = $this->getDoctrine()->getManager();

            $hash            = $request->get('hash');
            $newPassword     = $request->get('newPassword');
            $confirmPassword = $request->get('confirmPassword');

            /** @var User $user */
            $user = $em->getRepository("AppBundle:User")->findOneBy(['passwordRecoveryHash' => $hash]);

            if (!$user) {
                throw new \Exception("User by hash $hash not found", Response::HTTP_NOT_FOUND);
            }

            // check if new and confirm password is equal
            if ($newPassword !== $confirmPassword) {
                throw new \Exception('New password is not confirmed.', Response::HTTP_BAD_REQUEST);
            }

            // encode and save new password
            $user->setPlainPassword($newPassword);
            $user->setPasswordRecoveryHash(null);

            try {
                $em->getConnection()->beginTransaction();
                $em->persist($user);
                $em->flush();

                $em->getConnection()->commit();

                $status = Response::HTTP_CREATED;
                $response = ['status' => $status, 'message' => 'Success'];
            } catch (\Exception $e) {
                $em->getConnection()->rollBack();
                $status = Response::HTTP_BAD_REQUEST;
                throw new \Exception($e->getMessage(), $status);
            }
        } catch (\Throwable $e) {
            $status = $e->getCode();
            $response = ['status' => $status, 'message' => $e->getMessage()];
        }

        return new JsonResponse($response, $status);
    }

    private function random_password( $length = 8 ) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
        $password = substr( str_shuffle( $chars ), 0, $length );
        return $password;
    }
}