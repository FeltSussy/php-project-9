<?php

namespace App\Service;

use App\Repository\UrlCheckRepository;
use App\Repository\UrlRepository;
use App\Entity\Check;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use Carbon\Carbon;

class UrlCheckService
{
    private UrlCheckRepository $checkRepository;
    private UrlRepository $urlRepository;
    private Client $client;

    public function __construct(
        UrlCheckRepository $checkRepository,
        UrlRepository $urlRepository,
        Client $client,
    )
    {
        $this->checkRepository = $checkRepository;
        $this->urlRepository = $urlRepository;
        $this->client = $client;
    }

    public function checkUrl(int $urlId)
    {
        $url = $this->urlRepository->findById($urlId);
        try {
            $response = $this->client->get($url->getName());
        } catch (\Throwable $e) {
            return [
                'key' => 'danger',
                'message' => 'Произошла ошибка при проверке, не удалось подключиться',
                ];
        }

        $crawler = new Crawler($response->getBody());
        $check = Check::create(
            $url->getId(),
            $response->getStatusCode(),
            $this->crawl($crawler, 'h1'),
            $this->crawl($crawler, 'title'),
            $this->crawl($crawler, 'meta[name="description"]', 'content'),
            Carbon::now(),
        );

        if ($this->checkRepository->save($check)) {
            return [
                'key' => 'success',
                'message' => 'Страница успешно проверена',
            ];
        }

        return [
            'key' => 'danger',
            'message' => 'Произошла ошибка при внесении в базу данных',
        ];
    }

    private function crawl(Crawler $crawler, string $selector, ?string $attribute = null): ?string
    {
        $node = $crawler->filter($selector)->first();
        if (!$node->count()) {
            return null;
        }
        return $attribute === null ? $node->text() : $node->attr($attribute);
    }
}
// require __DIR__ . '/../../vendor/autoload.php';

// $client = new Client();
// $response = $client->get('https://php-project-9-9grq.ender.com');
// print_r($response->getStatusCode());