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

    public function addUrl(string $name)
    {
        $validator = new Validator(['urlName' => $name]);
        $validator->rules([
            'required' => [
                ['urlName']
            ]
        ]);
        if (!$validator->validate()) {
            return [
                'key' => 'warning',
                'message' => 'URL не должен быть пустым',
                'urlId' => null,
            ];
        }

        $validator->rules([
            'url' => [
                ['urlName']
            ]
        ]);
        if (!$validator->validate()) {
            return [
                'key' => 'warning',
                'message' => 'Некорректный URL',
                'urlId' => null,
            ];
        }

        $validator->rules([
            'lengthMax' => [
                ['urlName', 255]
            ]
        ]);
        if (!$validator->validate()) {
            return [
                'key' => 'warning',
                'message' => 'URL превышает 255 символов',
                'urlId' => null,
            ];
        }

        $parsedUrlName = parse_url($name);
        $urlNameToSave = "{$parsedUrlName['scheme']}://{$parsedUrlName['host']}";

        $url = Url::create($urlNameToSave, Carbon::now());
        $result = $this->repository->save($url);
        return $result;
    }
}
