<?php

namespace App\Service;

use App\Repository\UrlCheckRepository;
use App\Repository\UrlRepository;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;

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

    public function checkUrl(int $urlId): bool
    {
        $url = $this->urlRepository->findById($urlId);
        try {
            $response = $this->client->get($url->getName(), ['timeout' => 15]);
        } catch (ConnectException | ServerException $e) {
            return false;
        } catch (RequestException $e) {
            $response = $e->getResponse();
        }

        if ($response !== null) {
            $crawler = new Crawler($response->getBody());
            $statusCode = $response->getStatusCode();
            $h1 = $this->crawl($crawler, 'h1');
            $title = $this->crawl($crawler, 'title');
            $description = $this->crawl($crawler, 'meta[name="description"]', 'content');
            return $this->urlCheckRepository->save($urlId, $statusCode, $h1, $title, $description);
        }

        return $this->urlCheckRepository->save($urlId);
    }

    private function crawl(Crawler $crawler, string $selector, ?string $attribute = null): string
    {
        $node = $crawler->filter($selector)->first();
        if (!$node->count()) {
            return '';
        }
        return $attribute === null ? $node->text() : $node->attr($attribute);
    }
}
