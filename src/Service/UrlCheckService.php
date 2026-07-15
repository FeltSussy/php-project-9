<?php

namespace App\Service;

use App\Repository\UrlCheckRepository;
use App\Repository\UrlRepository;

class UrlCheckService
{
    private UrlCheckRepository $checkRepository;
    private UrlRepository $urlRepository;

    public function __construct(
        UrlCheckRepository $checkRepository,
        UrlRepository $urlRepository
    )
    {
        $this->checkRepository = $checkRepository;
        $this->urlRepository = $urlRepository;
    }

    public function checkUrl(int $urlId)
    {
        $url = $this->urlRepository->findById($urlId);
        $result = $this->checkRepository->save($url);
        return $result;
    }
}
