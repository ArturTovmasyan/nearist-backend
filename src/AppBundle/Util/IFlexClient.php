<?php

namespace AppBundle\Util;

use AppBundle\Entity\Portal\Server;
use AppBundle\Entity\Portal\User;
use Psr\Http\Message\ResponseInterface;

class IFlexClient
{
    /** @var Server */
    private $server;

    /** @var \GuzzleHttp\Client $client */
    private $client;

    /** @var string */
    private $token;

    /** @var string */
    private $loginUrl;

    /** @var string */
    private $apiUrl;

    /**
     * @param \GuzzleHttp\Client $httpClient
     * @param Server $server
     */
    public function __construct(\GuzzleHttp\Client $httpClient, Server $server)
    {
        $this->server = $server;

        $address = $server->getIflexIp();
        $port = $server->getIflexPort();
        $secure = $server->getIflexSecure();

        $baseUrl = sprintf('%s://%s:%d/agent/index.php', ($secure ? "https" : "http"), $address, $port);
        $this->loginUrl = sprintf('%s/api/login_check', $baseUrl);
        $this->apiUrl = sprintf('%s/api/v1.0', $baseUrl);

        /** @var \GuzzleHttp\Client $client */
        $this->client = $httpClient;
    }

    private function request($method, $url, $params = [], $callback = null, $auth = true, $json = false)
    {
        $options = [
            'http_errors' => false,
            'connect_timeout' => 1000,
            'timeout' => 1000
        ];

        switch ($method) {
            case 'post':
                if ($json) {
                    $options['json'] = $params;
                } else {
                    $options['form_params'] = $params;
                }
                break;
            case 'get':
                $options['query'] = $params;
                break;
            default:
                break;
        }

        if ($auth) {
            $options['headers'] = ['Authorization' => "Bearer {$this->token}"];
        }

        if ($callback == null) {
            $callback = function ($response) {
                return true;
            };
        }

        /** @var ResponseInterface $response */
        $response = $this->client->$method($url, $options);
        $payload = json_decode($response->getBody()->getContents(), true);

        if ($response->getStatusCode() == 200 || $payload['code'] == 200) {
            return $callback($payload);
        }

        return false;
    }

    public function login()
    {
        return $this->request('post', $this->loginUrl,
            [
                'username' => $this->server->getIflexUsername(),
                'password' => $this->server->getIflexPassword()
            ],
            function ($response) {
                $this->token = $response['token'];
                return true;
            }, false, true);
    }

    public function getHealth()
    {
        return $this->request('get', sprintf("%s/health", $this->apiUrl),
            [],
            function ($response) {
                return $response['data'];
            });
    }

    public function getSystemInfo()
    {
        return $this->request('get', sprintf("%s/system-info", $this->apiUrl),
            [],
            function ($response) {
                return $response['data'];
            });
    }

    public function getTemperature($lastRecords = [])
    {
        return $this->request('get', sprintf("%s/temperature", $this->apiUrl),
            ['last_records' => $lastRecords],
            function ($response) {
                return $response['data'];
            });
    }

    public function getBoardCount()
    {
        return $this->request('get', sprintf("%s/board-info", $this->apiUrl),
            [],
            function ($response) {
                return $response['data']['board_count'];
            });
    }

    public function reboot()
    {
        return $this->request('post', sprintf("%s/reboot", $this->apiUrl));
    }

    public function loadBitstream($boardData, $sea, $sed)
    {
        return $this->request('post', sprintf("%s/bitstream", $this->apiUrl),
            [
                'board_data' => $boardData,
                'sea' => $sea,
                'sed' => $sed
            ]);
    }

    public function loadConfig(User $user, $data)
    {
        return $this->request('post', sprintf("%s/reconfig", $this->apiUrl),
            [
                'api_key' => $user->getApiKey(),
                'username' => $user->getUsername(),
                'data' => $data
            ]);
    }

    public function syncFTPUsers($users)
    {
        return $this->request('post', sprintf("%s/sync-ftp-users", $this->apiUrl),
            [
                'users' => $users,
            ]);
    }

    public function powerOnConfig($date, $description, $sea, $sed)
    {
        return $this->request('post', sprintf("%s/power-on-config", $this->apiUrl),
            [
                'date' => $date,
                'description' => $description,
                'sea' => $sea,
                'sed' => $sed
            ]);
    }

}
