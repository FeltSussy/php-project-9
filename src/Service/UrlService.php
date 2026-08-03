<?php

namespace App\Service;

use App\Repository\UrlCheckRepository;
use App\Repository\UrlRepository;

class UrlService
{
    private UrlRepository $urlRepository;
    private UrlCheckRepository $urlCheckRepository;

    public function __construct(
        UrlRepository $urlRepository,
        UrlCheckRepository $urlCheckRepository
    ) {
        $this->urlRepository = $urlRepository;
        $this->urlCheckRepository = $urlCheckRepository;
    }

    public function addUrl(string $name): array
    {
        $parsed = parse_url($name);
        $urlNameToSave = $parsed['scheme']
            . '://'
            . $parsed['host']
            . (isset($parsed['port']) ? ':' . $parsed['port'] : '');

        if ($existingUrl = $this->urlRepository->findByName($urlNameToSave)) {
            return [
                'url' => $existingUrl,
                'isNew' => false
            ];
        }
        $this->urlRepository->save($urlNameToSave);

        return [
            'url' => $this->urlRepository->findByName($urlNameToSave),
            'isNew' => true
        ];
    }

    public function getUrls(): array
    {
        $urls = $this->urlRepository->getAll();
        $checks = $this->urlCheckRepository->findLatestChecks();
        $latestChecksByUrlId = [];

        foreach ($checks as $check) {
            $latestChecksByUrlId[$check->getUrlId()] = [
                'created_at' => $check->getCreatedAt()->format('Y-m-d H:i:s'),
                'status_code' => $check->getStatusCode(),
            ];
        }

        foreach ($urls as $url) {
            if (!isset($latestChecksByUrlId[$url->getId()])) {
                $latestChecksByUrlId[$url->getId()] = [
                    'created_at' => '',
                    'status_code' => '',
                ];
            }
        }

        return [
            'urls' => $urls,
            'lastChecks' => $latestChecksByUrlId
        ];
    }

    public function getUrl(int $urlId): array|bool
    {
        if ($url = $this->urlRepository->findById($urlId)) {
            return [
                'url' => $url,
                'checks' => $this->urlCheckRepository->findAllByUrlId($urlId)
            ];
        }

        return false;
    }
}
