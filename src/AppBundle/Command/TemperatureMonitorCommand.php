<?php

namespace AppBundle\Command;

use AppBundle\Entity\Portal\Logs;
use AppBundle\Entity\Portal\Server;
use AppBundle\Entity\Portal\TemperatureLog;
use AppBundle\Model\Log\LogType;
use AppBundle\Util\IFlexClient;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TemperatureMonitorCommand extends ContainerAwareCommand
{
    use LockableTrait;

    protected function configure()
    {
        $this->setName('nearist:monitor:temperature');
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

            /** @var TemperatureLog[] $lastRecords * */
            $servers = $em->getRepository("AppBundle:Server")->findAll();
            $lastRecords = $em->getRepository("AppBundle:TemperatureLog")->getLastRecords();

            $lastRecordsByParams = [];
            foreach ($lastRecords as $lastRecord) {
                $lastRecordsByParams[$lastRecord->getServer()->getId()][$lastRecord->getBoard()][$lastRecord->getLane()][$lastRecord->getDateTime()->getTimestamp()] = true;
            }

            foreach ($servers as $server) {
                try {
                    $client = new IFlexClient($guzzle, $server);

                    if ($client->login()) {
                        $lastRecords = $lastRecordsByParams[$server->getId()] ?? [];
                        $data = $client->getTemperature($lastRecords);

                        foreach ($data as $row) {
                            $datetime = new \DateTime();
                            $datetime->setTimestamp($row['timestamp']);

                            $temperatureLog = new TemperatureLog();
                            $temperatureLog->setDateTime($datetime);
                            $temperatureLog->setBoard($row['board']);
                            $temperatureLog->setLane($row['lane']);
                            $temperatureLog->setServer($server);
                            $temperatureLog->setTemperatureCodes(implode(',', $row['params']));
                            $em->persist($temperatureLog);
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