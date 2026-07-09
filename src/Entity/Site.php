<?php

namespace App\Entity;

class Site
{
    private int $id;
    private string $name;
    private string $createdAt;

    public function __construct(int $id, string $name, string $createdAt)
    {
        $this->id = $id;
        $this->name = $name;
        $this->createdAt = $createdAt;
    }

    public function 
}