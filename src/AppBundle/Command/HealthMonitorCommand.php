<?php

namespace AppBundle\Command;

use AppBundle\Entity\Portal\Logs;
use AppBundle\Entity\Portal\Server;
use AppBundle\Model\Log\LogType;
use AppBundle\Util\IFlexClient;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HealthMonitorCommand extends ContainerAwareCommand
{
    use LockableTrait;

    protected function configure()
    {
        $this->setName('nearist:monitor:health');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->lock()) {
            $output->writeln('The command is already running in another process.');

            return 0;
        }

        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();

        /** @var \GuzzleHttp\Client $guzzle */
        $guzzle = $this->getContainer()->get('eight_points_guzzle.client.api_server');

        /** @var Server $server */
        $errors = [];
        try {

            $servers = $em->getRepository("AppBundle:Server")->findAll();

            foreach ($servers as $server) {
                try {
                    $client = new IFlexClient($guzzle, $server);

                    if ($client->login()) {
                        $data = $client->getHealth();

                        if ($data === false) {
                            $errors[] = sprintf("Unable to sync with server '%s'...", $server->getName());
                        } else {
                            $log = new Logs();
                            $log->setLevel(1);
                            $log->setDateTime(new \DateTime());
                            $log->setServer($server);
                            $log->setType(LogType::CPU);
                            $log->setCpu($data['CPU']['total'] - $data['CPU']['free']);
                            $em->persist($log);

                            $log = new Logs();
                            $log->setLevel(1);
                            $log->setDateTime(new \DateTime());
                            $log->setServer($server);
                            $log->setType(LogType::MEMORY);
                            $log->setMemory($data['RAM']['total'] - $data['RAM']['free']);
                            $em->persist($log);

                            $em->flush();
                        }
                    } else {
                        $errors[] = sprintf("Unable to connect to server '%s' invalid username/password...", $server->getName());
                    }
                } catch (\Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }

            if (count($errors) > 0) {
                var_dump($errors);
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }

        $this->release();

        return 0;
    }
}