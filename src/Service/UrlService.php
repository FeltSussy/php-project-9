<?php

namespace App\Service;

use App\Repository\UrlRepository;
use Valitron\Validator;
use App\Entity\Url;
use Carbon\Carbon;

class UrlService
{
    private UrlRepository $repository;

    public function __construct(
        UrlRepository $repository
    ) {
        $this->repository = $repository;
    }

    public function addUrl(string $name): array
    {
        $validator = new Validator(['urlName' => $name]);
        $validator->stopOnFirstFail();

        $validator
            ->rule('required', 'urlName')
            ->message('url_required');

        $validator
            ->rule('url', 'urlName')
            ->message('url_invalid');

        $validator
            ->rule('lengthMax', 'urlName', 255)
            ->message('url_too_long');

        if (!$validator->validate()) {
            $errors = $validator->errors('urlName');

            return [
                'status' => $errors[0],
                'urlId' => null
            ];
        }

        $parsedUrlName = parse_url($name);
        $urlNameToSave = $parsedUrlName['scheme'] . "://" . $parsedUrlName['host'];

        $url = Url::create($urlNameToSave, Carbon::now());
        if ($existingUrl = $this->repository->findByName($url->getName())) {
            return [
                'status' => 'url_already_exists',
                'urlId' => $existingUrl->getId(),
            ];
        }
        $this->repository->save($url);
        return [
            'status' => 'url_added',
            'urlId' => $this->repository->getLastInsertId(),
        ];
    }

    public function getAllUrls()
    {
        return $this->repository->getAll();
    }

    public function getUrlById(int $urlId)
    {
        return $this->repository->findById($urlId);
    }
}
