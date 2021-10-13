<?php

namespace App\Repositories\Verisure;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\RequestOptions;
use Symfony\Component\DomCrawler\Crawler;

class InstallationRepository
{
    protected const RESOURCE = 'https://customers.verisure.com.br/br/installations';

    protected Client $client;
    protected Crawler $crawler;

    public function __construct(Client $client, Crawler $crawler)
    {
        $this->client = $client;
        $this->crawler = $crawler;
    }

    public function unlock(CookieJar &$cookieJar, int $id): string
    {
        $uri = sprintf('%s/%s/panel/unlock', self::RESOURCE, $id);
        $csrf = $this->buildCsrfFromMeta($cookieJar, $uri);

        $request = $this->client->request('POST', $uri, [
            RequestOptions::COOKIES => $cookieJar,
            RequestOptions::JSON => [
                'authenticity_token' => $csrf,
                'utf8' => '✓',
            ],
            RequestOptions::HEADERS => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.71 Safari/537.36',
                'X-CSRF-Token' => $csrf,
                'X-Requested-With' => 'XMLHttpRequest',
            ],
        ]);

        return json_decode($request->getBody())->job_id;
    }

    public function lock(CookieJar &$cookieJar, int $id): string
    {
        $uri = sprintf('%s/%s/panel/house', self::RESOURCE, $id);
        $csrf = $this->buildCsrfFromMeta($cookieJar, $uri);

        $request = $this->client->request('POST', $uri, [
            RequestOptions::COOKIES => $cookieJar,
            RequestOptions::JSON => [
                'authenticity_token' => $csrf,
                'utf8' => '✓',
            ],
            RequestOptions::HEADERS => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.71 Safari/537.36',
                'X-CSRF-Token' => $csrf,
                'X-Requested-With' => 'XMLHttpRequest',
            ],
        ]);

        return json_decode($request->getBody())->job_id;
    }

    private function buildCsrfFromMeta(CookieJar &$cookieJar, string $resource): string
    {
        $body = $this->client->request('GET', $resource, [
            RequestOptions::COOKIES => $cookieJar,
        ]);

        $crawler = clone $this->crawler;
        $crawler->addContent($body->getBody());

        return $crawler->filter('input[name="authenticity_token"]')->attr('value');
    }
}
