<?php

namespace App\Service;

use App\Repository\SiteRepository;
use Valitron\Validator;
use App\Entity\Site;
use Carbon\Carbon;

class SiteRegistrationService
{
    private SiteRepository $repository;

    public function __construct(
        SiteRepository $repository
    ) {
        $this->repository = $repository;
    }

    public function add(string $url)
    {
        $validator = new Validator(['website' => $url]);
        $validator->rules([
            'required' => [
                ['website']
            ]
        ]);
        if (!$validator->validate()) {
            return [
                'key' => 'warning',
                'message' => 'URL не должен быть пустым',
                'siteId' => null,
            ];
        }

        $validator->rules([
            'url' => [
                ['website']
            ]
        ]);
        if (!$validator->validate()) {
            return [
                'key' => 'warning',
                'message' => 'Некорректный URL',
                'siteId' => null,
            ];
        }

        $validator->rules([
            'lengthMax' => [
                ['website', 255]
            ]
        ]);
        if (!$validator->validate()) {
            return [
                'key' => 'warning',
                'message' => 'URL превышает 255 символов',
                'siteId' => null,
            ];
        }

        $parsedUrl = parse_url($url);
        $urlToSave = "{$parsedUrl['scheme']}://{$parsedUrl['host']}";

        $site = Site::create($urlToSave, Carbon::now());
        $result = $this->repository->save($site);
        return $result;
    }
}
