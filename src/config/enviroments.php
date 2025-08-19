<?php

function secrets($name = 'enviroments')
{
    $lines = file("/run/secrets/{$name}", FILE_SKIP_EMPTY_LINES|FILE_IGNORE_NEW_LINES);
    $env = [];
    foreach($lines as $line) {
        $parts = explode('=',$line);
        $env[$parts[0]] = $parts[1];
    }
    return $env;
}

function enviroments(){

    $output=null;
    $retval=null;
    exec('env', $output, $retval);
    $env = [];
    foreach($output as $line) {
        $parts = explode('=',$line);
        $env[$parts[0]] = $parts[1];
    }

    return $env;
}