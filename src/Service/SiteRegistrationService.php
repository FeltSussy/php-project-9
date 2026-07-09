<?php

namespace App\Service;

use App\Repository\SiteRepository;
use Valitron\Validator;

class SiteRegistrationService
{
    private SiteRepository $repository;

    public function __construct(
        SiteRepository $repository
    )
    {
        $this->repository = $repository;
    }

    public function add (string $url)
    {
        $validator = new Validator(['website' => $url]);
        $validator->rules([
            'url' => [
                ['website']
            ]
        ]);
        if (!$validator->validate()) {
            $errors = 'error';
        }
        
        // $params = [
        //     'content' => $site['url'],
        // ];
        // return $this->renderer->render($response, 'layout.phtml', $params);
    }
}
