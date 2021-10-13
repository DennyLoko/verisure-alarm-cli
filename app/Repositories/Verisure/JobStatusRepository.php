<?php

namespace App\Repositories\Verisure;

use App\Exceptions\QueueFailedException;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\RequestOptions;
use Symfony\Component\DomCrawler\Crawler;

class JobStatusRepository
{
    protected const RESOURCE = 'https://customers.verisure.com.br/es/remote/job_status';

    protected const STATUS_COMPLETED = 'completed';
    protected const STATUS_FAILED = 'failed';
    protected const STATUS_QUEUED = 'queued';
    protected const STATUS_WORKING = 'working';

    protected Client $client;
    protected Crawler $crawler;

    public function __construct(Client $client, Crawler $crawler)
    {
        $this->client = $client;
        $this->crawler = $crawler;
    }

    public function lockTillDone(CookieJar $cookieJar, string $jobId, string $csrf): void
    {
        while (! $this->isJobReady($cookieJar, $jobId, $csrf)) {
            sleep(1);
        }
    }

    public function isJobReady(CookieJar &$cookieJar, string $jobId, string $csrf): bool
    {
        $uri = sprintf('%s/%s', self::RESOURCE, $jobId);

        $body = $this->client->request('GET', $uri, [
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

        $status = json_decode($body->getBody())?->status;

        if ($status === self::STATUS_FAILED || empty($status)) {
            throw new QueueFailedException($status);
        }

        return $status === self::STATUS_COMPLETED;
    }
}
