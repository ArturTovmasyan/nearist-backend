<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);

//        $em = $this->getDoctrine()->getManager();
//
//
//        $mailer = new Mailer($this->container);
//
//        /** @var Reservation $reservation */
//        $reservation = $em->getRepository("AppBundle:Reservation")->find(9);
//
//        $users = $em->getRepository("AppBundle:User")->findBy(['enabled' => true]);
//
//        /** @var BoardConfig $bc */
//        $bc = $em->getRepository("AppBundle:BoardConfig")->find(1);
//
//        $password = $this->random_password(8);
//        $reservation->getUser()->setPlainPassword($password);
//        $em->persist($reservation->getUser());
//        $em->flush();
//
//        file_put_contents("D:/bitstream.html", $mailer->notifyBitstream($bc, $users));
//        file_put_contents("D:/reservation.html", $mailer->notifyReservation($reservation));
//        file_put_contents("D:/credentials.html", $mailer->notifyCredentials($reservation->getUser()));
//
//        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
//    }
//
//    private function random_password( $length = 8 ) {
//        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
//        $password = substr( str_shuffle( $chars ), 0, $length );
//        return $password;
    }
}
