<?php

function truncate(?string $text, int $length = 200): string
{
    if ($text === null) {
        return '';
    }

    return mb_strlen($text) > $length
        ? mb_substr($text, 0, $length) . '...'
        : $text;
}
