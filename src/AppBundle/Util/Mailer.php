<?php

namespace AppBundle\Util;


use AppBundle\Entity\Portal\BoardConfig;
use AppBundle\Entity\Portal\Reservation;
use AppBundle\Entity\Portal\User;
use AppBundle\Model\Bitstream\BoardTypes;
use AppBundle\Model\Bitstream\DeviceTypes;
use AppBundle\Model\Bitstream\FileTypes;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Mailer
{
    /** @var ContainerInterface */
    private $container;

    /**
     * Mailer constructor.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    private function send($type, $subject, $bcc, $user, $object = null, $extra = null)
    {
        $body = $this->container->get('twig')->render(
            "AppBundle::email/$type.html.twig",
            array(
                'subject' => $subject,
                'user' => $user,
                'object' => $object,
                'extra' => $extra
            )
        );
        $message = new \Swift_Message();
        $message
            ->setSubject('[Nearist] ' . $subject)
            ->setFrom('noreply@nearist.io')
            ->setBcc($bcc)
            ->setBody($body, 'text/html');

        return $this->container->get('mailer')->send($message);
    }

    public function notifyBitstream(BoardConfig $bitstream, $users)
    {
        if (count($users) === 0) {
            return false;
        }

        $bcc = [];

        /** @var User $user */
        foreach ($users as $user) {
            if (!$user->hasRole('ROLE_CUSTOMER')) {
                $bcc[$user->getEmail()] = $user->getFullName();
            }
        }

        return $this->send('bitstream', 'Bitstream Notification', $bcc, null, $bitstream, ['board_list' => BoardTypes::getNamesList(), 'device_list' => DeviceTypes::getNamesList(), 'file_list' => FileTypes::getNamesList()]);
    }

    public function notifyReservation(Reservation $reservation)
    {
        $user = $reservation->getUser();

        if (!$user) {
            return false;
        }

        $bcc = [$user->getEmail() => $user->getFullName()];

        return $this->send('reservation', 'Reservation Notification', $bcc, $user, $reservation);
    }

    public function notifyCredentials(User $user)
    {
        if (!$user) {
            return false;
        }

        $bcc = [$user->getEmail() => $user->getFullName()];

        return $this->send('credentials', 'Sign In Details', $bcc, $user);
    }

    /**
     * @param User $user
     * @return bool
     */
    public function inviteCustomer(User $user, $baseUrl)
    {
        if (!$user) {
            return false;
        }

        $bcc = [$user->getEmail() => $user->getFullName()];

        return $this->send('invitation', 'Sign In Details', $bcc, $user, null, $baseUrl);
    }

    /**
     * @param User $user
     * @return bool
     */
    public function sendPasswordRecoveryLink(User $user, $baseUrl)
    {
        if (!$user) {
            return false;
        }

        $bcc = [$user->getEmail() => $user->getFullName()];

        return $this->send('password-recovery', 'Password Recovery', $bcc, $user, null, $baseUrl);
    }
}