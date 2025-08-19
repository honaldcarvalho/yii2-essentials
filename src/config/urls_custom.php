<?php

function customControllersUrl($controllers,$folder = 'custom'){
    $rules = [];
    foreach ($controllers as $key => $controller) {	
        $rules["{$controller}/<id:\d+>"] = "{$folder}/{$controller}/view";
        $rules["{$controller}/<action>/<id:\d+>"] = "{$folder}/{$controller}/<action>";
        $rules["{$controller}/<action>"] = "{$folder}/{$controller}/<action>";
        $rules["{$controller}"] = "{$folder}/{$controller}";
    }
    return $rules;
}