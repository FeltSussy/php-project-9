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
    private const MESSAGE_CONNECT_FAILED = 'Произошла ошибка при проверке, не удалось подключиться';
    private const MESSAGE_CHECK_SAVED = 'Страница успешно проверена';
    private const MESSAGE_CHECK_NOT_SAVED = 'Произошла ошибка, проверка не была сохранена';

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
        $url = $this->urlRepository->findById($urlId);
        try {
            $response = $this->client->get($url->getName(), ['timeout' => 15]);
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            return [
                'key' => 'danger',
                'message' => self::MESSAGE_CONNECT_FAILED,
            ];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $response = $e->getResponse();
        }

        $statusCode = null;
        $h1 = '';
        $title = '';
        $description = '';

        if ($response !== null) {
            $crawler = new Crawler($response->getBody());
            $statusCode = $response->getStatusCode();
            $h1 = $this->crawl($crawler, 'h1');
            $title = $this->crawl($crawler, 'title');
            $description = $this->crawl($crawler, 'meta[name="description"]', 'content');
        }

        $urlCheck = UrlCheck::create(
            $url->getId(),
            $statusCode,
            $h1,
            $title,
            $description,
            Carbon::now(),
        );

        if ($this->urlCheckRepository->save($urlCheck)) {
            return [
                'key' => 'success',
                'message' => self::MESSAGE_CHECK_SAVED,
            ];
        }
        return [
            'key' => 'danger',
            'message' => self::MESSAGE_CHECK_NOT_SAVED,
        ];
    }

    public function getLatestChecksOfAllUrls()
    {
        return $this->urlCheckRepository->findLatestForEachUrl();
    }

    public function getAllChecksOfSpecificUrlId(int $urlId)
    {
        return $this->urlCheckRepository->findAllByUrlId($urlId);
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
