<?php

namespace App\Service;

use App\Repository\UrlRepository;
use Valitron\Validator;
use App\Entity\Url;
use Carbon\Carbon;

class UrlService
{
    private const MESSAGE_URL_REQUIRED = 'URL не должен быть пустым';
    private const MESSAGE_URL_INVALID = 'Некорректный URL';
    private const MESSAGE_URL_TOO_LONG = 'URL превышает 255 символов';
    private const MESSAGE_URL_ALREADY_EXISTS = 'Страница уже существует';
    private const MESSAGE_URL_ADDED = 'Страница успешно добавлена';

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
            ->message(self::MESSAGE_URL_REQUIRED);

        $validator
            ->rule('url', 'urlName')
            ->message(self::MESSAGE_URL_INVALID);

        $validator
            ->rule('lengthMax', 'urlName', 255)
            ->message(self::MESSAGE_URL_TOO_LONG);

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
                'message' => self::MESSAGE_URL_ALREADY_EXISTS,
                'urlId' => null,
            ];
        }
        $this->repository->save($url);
        return [
            'key' => 'success',
            'message' => self::MESSAGE_URL_ADDED,
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
