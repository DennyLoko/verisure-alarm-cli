<?php

namespace App\Repositories\Verisure;

use App\Exceptions\UnauthorizedException;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\RequestOptions;
use Symfony\Component\DomCrawler\Crawler;

class LoginRepository
{
    protected const RESOURCE = 'https://customers.verisure.com.br/br/login/BR';

    protected Client $client;
    protected Crawler $crawler;

    public function __construct(Client $client, Crawler $crawler)
    {
        $this->client = $client;
        $this->crawler = $crawler;
    }

    public function login(CookieJar $cookieJar, string $username, string $password): string
    {
        return $this->submitAuthForm($cookieJar, $this->buildCsrf($cookieJar), $username, $password);
    }

    private function submitAuthForm(CookieJar &$cookieJar, string $csrf, string $username, string $password): string
    {
        $body = $this->client->request('POST', self::RESOURCE, [
            RequestOptions::COOKIES => $cookieJar,
            RequestOptions::FORM_PARAMS => [
                'utf8' => '✓',
                'authenticity_token' => $csrf,
                'verisure_rsi_login' => [
                    'nick' => $username,
                    'passwd' => $password,
                ],
                'button' => null,
            ],
        ]);

        $crawler = clone $this->crawler;
        $crawler->addContent($body->getBody());

        $alerts = $crawler->filter('.m_notification_title');

        if ($alerts->count() > 0) {
            throw new UnauthorizedException($alerts->first()->text());
        }

        return $crawler->filter('input[name="authenticity_token"]')->attr('value');
    }

    private function buildCsrf(CookieJar &$cookieJar): string
    {
        $body = $this->client->request('GET', self::RESOURCE, [
            RequestOptions::COOKIES => $cookieJar,
        ]);

        $crawler = clone $this->crawler;
        $crawler->addContent($body->getBody());

        return $crawler->filter('input[name="authenticity_token"]')->attr('value');
    }
}
