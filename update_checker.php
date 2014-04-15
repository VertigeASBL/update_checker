#!/usr/bin/php
<?php

require 'vendor/autoload.php';


function spip_get_version ($home_url) {

    $response = Requests::get($home_url . '/spip.php?page=login');

    return preg_replace('/SPIP ([0-9.]+).*$/', '$1', $response->headers['composed-by']);
}

function spip_get_ecran_securite ($home_url) {

    $response = Requests::get($home_url . '?test_ecran_securite');

    if ($response->status_code  !== 403) {
        return false;
    }

    return preg_replace('/^.*\(test ([0-9.]+)\).*$/', '$1', $response->body);
}

function spip_get_infos ($home_url) {

    return array(
        'version'        => spip_get_version($home_url),
        'ecran_securite' => spip_get_ecran_securite($home_url),
    );
}

$infos = spip_get_infos('http://demandezleprogramme.be');

var_dump($infos);