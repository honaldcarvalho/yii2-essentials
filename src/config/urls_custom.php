<?php

function customControllersUrl(array $controllers, string $folder = 'custom'): array
{
    $rules = [];

    foreach ($controllers as $controller) {
        $c = trim((string)$controller, "/ \t\n\r\0\x0B");
        if ($c === '') {
            continue; // evita blog//...
        }
        // aceita letras, números e hífen no controller id (ex.: post-section)
        if (!preg_match('~^[a-z0-9\-]+$~', $c)) {
            continue;
        }

        $rules["{$c}/<id:\d+>"]                 = "{$folder}/{$c}/view";
        $rules["{$c}/<action:[\w\-]+>/<id:\d+>"] = "{$folder}/{$c}/<action>";
        $rules["{$c}/<action:[\w\-]+>"]          = "{$folder}/{$c}/<action>";
        $rules["{$c}"]                           = "{$folder}/{$c}";
    }

    return $rules;
}