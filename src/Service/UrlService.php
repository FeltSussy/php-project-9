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
            ->message('URL не должен быть пустым');

        $validator
            ->rule('url', 'urlName')
            ->message('Некорректный URL');

        $validator
            ->rule('lengthMax', 'urlName', 255)
            ->message('URL превышает 255 символов');

        if (!$validator->validate()) {
            $errors = $validator->errors('urlName');

            return [
                'key' => 'warning',
                'message' => $errors[0],
                'urlId' => null,
            ];
        }

        $parsedUrlName = parse_url($name);
        $urlNameToSave = $parsedUrlName['scheme'] . "://" . $parsedUrlName['host'];

        $url = Url::create($urlNameToSave, Carbon::now());
        if ($this->repository->findByName($url->getName())) {
            return [
                'key' => 'warning',
                'message' => 'Страница уже существует',
                'urlId' => null,
            ];
        }
        $this->repository->save($url);
        return [
            'key' => 'success',
            'message' => 'Страница успешно добавлена',
            'urlId' => $this->repository->getLastInsertId(),
        ];
    }
}
