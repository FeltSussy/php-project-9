<?php

namespace App\Service;

use App\Repository\UrlCheckRepository;
use App\Repository\UrlRepository;
use App\Entity\UrlCheck;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use Carbon\Carbon;

class UrlCheckService
{
    private UrlCheckRepository $urlCheckRepository;
    private UrlRepository $urlRepository;
    private Client $client;

    public function __construct(
        UrlCheckRepository $urlCheckRepository,
        UrlRepository $urlRepository,
        Client $client,
    ) {
        $this->urlCheckRepository = $urlCheckRepository;
        $this->urlRepository = $urlRepository;
        $this->client = $client;
    }

    public function checkUrl(int $urlId)
    {
        if (!$url = $this->urlRepository->findById($urlId)) {
            return [
                'key' => 'warning',
                'message' => 'Страницы с ID {$url} не существует',
            ];
        }
        try {
            $response = $this->client->get($url->getName(), ['timeout' => 15]);
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            return [
                'key' => 'danger',
                'message' => 'Произошла ошибка при проверке, не удалось подключиться',
            ];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $response = $e->getResponse();
        }

        $crawler = new Crawler($response->getBody());
        $urlCheck = UrlCheck::create(
            $url->getId(),
            $response->getStatusCode(),
            $this->crawl($crawler, 'h1'),
            $this->crawl($crawler, 'title'),
            $this->crawl($crawler, 'meta[name="description"]', 'content'),
            Carbon::now(),
        );

        $this->urlCheckRepository->save($urlCheck);
        return [
            'key' => 'success',
            'message' => 'Страница успешно проверена',
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
