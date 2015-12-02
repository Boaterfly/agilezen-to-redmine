<?php

namespace AgilezenToRedmine\Api;

use GuzzleHttp\Client;

use AgilezenToRedmine\Api\AgileZen\Project;
use AgilezenToRedmine\Api\AgileZen\User;

class AgileZen
{
    const BASE_URI = 'https://agilezen.com/api/v1/';

    /// @var GuzzleHttp\Client
    private $client;

    public function __construct($token)
    {
        assert('is_string($token) && strlen($token) > 0');

        $this->client = new Client([
            'base_uri' => self::BASE_URI,
            'headers' => [
                'X-Zen-ApiKey' => $token,
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip',
            ]
        ]);
    }

    /**
     * Make an HTTP GET call on the API.
     *
     * @param string $uri relative URI.
     * @return mixed[] decoded JSON response.
     */
    private function get($uri)
    {
        $response = $this->client->get($uri);
        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException("Got status code {$response->getStatusCode()} from AgileZen API.");
        }

        if (strlen($response->getBody()) <= 0) {
            throw new \RuntimeException('Empty response from AgileZen API.');
        }

        $decoded = json_decode($response->getBody(), true);
        if ($decoded === false && $response->getBody() !== 'false') {
            throw new \RuntimeException('Invalid JSON from AgileZen API.');
        }

        return $decoded;
    }

    /// @return Project[]
    public function projects()
    {
        return array_map(
            function ($raw) {
                $owner = new User($raw['owner']);
                return new Project(compact('owner') + $raw);
            },
            $this->get('projects')['items']
        );
    }
}
