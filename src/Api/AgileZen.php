<?php

namespace AgileZenToRedmine\Api;

use GuzzleHttp\Client;

use AgileZenToRedmine\Api\AgileZen\Attachment;
use AgileZenToRedmine\Api\AgileZen\Project;
use AgileZenToRedmine\Api\AgileZen\Story;

class AgileZen
{
    const BASE_URI = 'https://agilezen.com/api/v1/';

    /// @var GuzzleHttp\Client
    private $client;

    /// @var bool should we cache every request.
    private $cache = false;

    /// @var string where to store raw API results.
    private $cacheDir;

    public function __construct(array $params)
    {
        $params += [
            'token' => null,
            'cache' => false,
            'cacheDir' => null,
        ];

        assert('is_string($params["token"]) && strlen($params["token"]) > 0');

        $this->client = new Client([
            'base_uri' => self::BASE_URI,
            'headers' => [
                'X-Zen-ApiKey' => $params["token"],
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip',
            ]
        ]);

        if ($params['cache'] !== false) {
            if (!file_exists($params['cacheDir'])
                || !is_dir($params['cacheDir'])
                || !is_writeable($params['cacheDir'])
            ) {
                throw new \RuntimeException('Can\'t write to cache dir.');
            }
            $this->cache = true;
            $this->cacheDir = $params['cacheDir'];
        }

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
        $uri = "projects/$projectId/stories?with=details,comments,tags,steps";

        return array_map(
            Story::class . '::marshal',
            $this->unpaginatedGet($uri)['items']
        );
    }

    /**
     * @param int $projectId
     * @param int $storyId
     * return Story[]
     */
    public function attachments($projectId, $storyId)
    {
        $uri = "projects/$projectId/stories/$storyId/attachments";

        return array_map(
            Attachment::class . '::marshal',
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
        if ($this->cache) {
            $debugCache = "{$this->cacheDir}/$uri.json";
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

        if ($this->cache) {
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
            $curQuery = http_build_query(
                ['page' => $curPage, 'pageSize' => 1000] + $originalQueryArray
            );
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
