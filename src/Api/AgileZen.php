<?php

namespace AgileZenToRedmine\Api;

use GuzzleHttp\Client;

use AgileZenToRedmine\Api\AgileZen\Comment;
use AgileZenToRedmine\Api\AgileZen\Project;
use AgileZenToRedmine\Api\AgileZen\Story;
use AgileZenToRedmine\Api\AgileZen\User;

class AgileZen
{
    const BASE_URI = 'https://agilezen.com/api/v1/';

    // TODO: remove DEBUG_ things when done with the API.
    /// @var bool cache every request, for debugging purposes only.
    const DEBUG_CACHE = false;
    const DEBUG_CACHE_DIR = '/tmp/agilezen';

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

    /// @return Project[]
    public function projects()
    {
        return array_map(
            Project::class . '::marshal',
            $this->unpaginatedGet('projects?with=phases')['items']
        );
    }

    /**
     * @param int $projectId
     * return Story[]
     */
    public function stories($projectId)
    {
        $uri = "projects/$projectId/stories?with=details,comments";

        return array_map(
            Story::class . '::marshal',
            $this->unpaginatedGet($uri)['items']
        );
    }

    /**
     * Make an HTTP GET call on the API.
     *
     * @param string $uri relative URI.
     * @return mixed[] decoded JSON response.
     */
    private function get($uri)
    {
        if (self::DEBUG_CACHE) {
            $debugCache = self::DEBUG_CACHE_DIR . "/$uri.json";
            if (!file_exists(dirname($debugCache))) {
                mkdir(dirname($debugCache), 0775, true);
            }

            if (file_exists($debugCache)) {
                return json_decode(file_get_contents($debugCache), true);
            }
        }

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

        if (self::DEBUG_CACHE) {
            file_put_contents($debugCache, $response->getBody());
        }

        return $decoded;
    }

    /**
     * Make multiple HTTP GET calls on the API to get the full results of an
     * otherwise paginated request.
     *
     * @param string $uri relative URI.
     * @return mixed[] decoded JSON response the full results and without
     * pagination information.
     */
    private function unpaginatedGet($uri)
    {
        $originalPath = parse_url($uri, PHP_URL_PATH);
        parse_str(parse_url($uri, PHP_URL_QUERY), $originalQueryArray);

        $curPage = 1;
        $items = [];

        for (;;) {
            $curQuery = http_build_query(['page' => $curPage] + $originalQueryArray);
            $curUri = "$originalPath?$curQuery";

            $curResult = $this->get($curUri);
            $items = array_merge($items, $curResult['items']);

            $curPage += 1;
            if ($curPage > $curResult['totalPages'] || count($items) <= 0) {
                break;
            }
        }

        return compact('items');
    }
}
