<?php

namespace App\Service;

use App\Repository\UrlRepository;
use App\Entity\Url;

class UrlService
{
    private UrlRepository $repository;

    public function __construct(
        UrlRepository $repository
    ) {
        $this->repository = $repository;
    }

    public function addUrl(string $name): Url|bool
    {
        $parsed = parse_url($name);
        $urlNameToSave = $parsed['scheme']
            . '://'
            . $parsed['host']
            . (isset($parsed['port']) ? ':' . $parsed['port'] : '');

        if ($existingUrl = $this->repository->findByName($urlNameToSave)) {
            return $existingUrl;
        }
        return $this->repository->save($urlNameToSave);
    }
}
