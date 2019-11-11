<?php

namespace AppBundle\Util;


use AppBundle\Entity\Portal\Server;
use Symfony\Component\DomCrawler\Crawler;

class IPMIClient
{
    /** @var Server */
    private $server;

    /** @var \GuzzleHttp\Client $client */
    private $client;


    /** @var string */
    private $loginUrl;
    private $ipmiUrl;

    public function __construct(\GuzzleHttp\Client $httpClient, Server $server)
    {
        $this->server = $server;

        $address = $server->getIpmiIp();
        $port = $server->getIpmiPort();
        $secure = $server->getIpmiSecure();

        $baseUrl = sprintf('%s://%s:%d/cgi', ($secure ? "https" : "http"), $address, $port);

        $this->loginUrl = sprintf('%s/login.cgi', $baseUrl);
        $this->ipmiUrl = sprintf('%s/ipmi.cgi', $baseUrl);

        /** @var \GuzzleHttp\Client $client */
        $this->client = $httpClient;

    }

    public function login()
    {
        $response = $this->client->post($this->loginUrl, ['connect_timeout' => 10, 'timeout' => 10,
            'form_params' => [
                'name' => $this->server->getIpmiUsername(),
                'pwd' => $this->server->getIpmiPassword()
            ]
        ]);

        $content = $response->getBody()->getContents();

        if (strpos($content, '"../cgi/url_redirect.cgi?url_name=mainmenu";') !== false) {
            return true;
        } else {
            return false;
        }
    }

    public function getStatus()
    {
        $response = $this->client->post($this->ipmiUrl, ['form_params' => [
            'POWER_INFO.XML' => '(0,0)'
        ]]);
        $content = $response->getBody()->getContents();

        $crawler = new Crawler();
        $crawler->addXmlContent($content);

        $powerNode = $crawler->filterXPath("//POWER[@STATUS]")->getNode(0);
        if ($powerNode != null) {
            $status = $powerNode->getAttribute('STATUS');

            return strtolower($status);
        }

        return null;
    }

    public function setStatus($status)
    {
        $response = $this->client->post($this->ipmiUrl, ['form_params' => [
            'POWER_INFO.XML' => "(1,$status)"
        ]]);
        $content = $response->getBody()->getContents();

        $crawler = new Crawler();
        $crawler->addXmlContent($content);

        $powerNode = $crawler->filterXPath("//POWER[@STATUS]")->getNode(0);
        if ($powerNode != null) {
            $status = $powerNode->getAttribute('STATUS');

            return strtolower($status);
        }

        return null;
    }
}