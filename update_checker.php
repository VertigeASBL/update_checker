#!/usr/bin/php
<?php

require 'vendor/autoload.php';

$input_file  = $argv[1];
$output_file = $argv[2] ?: 'output.csv';

/**
 * Main
 *
 * On lit le fichier csv passé en argument. Pour chaque ligne, on teste
 * le site est ajoute des colonnes contenant les infos. On écrit alors
 * ceci dans un nouveau fichier csv.
 */
if (($handle = fopen($input_file, "r")) !== FALSE) {

    $out_handle = fopen($output_file, "w");

    while (($data = fgetcsv($handle)) !== FALSE) {

        /* L'url du site est donnée par la 7ème colonne du tableau
           donné en entrée */
        $url = $data[6];

        $infos = spip_get_infos($url);
        if ($infos) {
            $data[] = $infos['version'];
            $data[] = $infos['ecran_securite'];
        }

        fputcsv($out_handle, $data);
    }

    fclose($handle);
    fclose($out_handle);
}

/**
 * Vérifie le format d'une url, et teste que le site existe bien.
 * Retourne un url formattée pour la fonction spip_get_infos.
 */
function prepare_url ($url) {

    if (preg_match('#^https?://#', $url) !== 1) {
        $url = 'http://' . $url;
    }

    $url = preg_replace('#/$#', '', $url);

    try {
        Requests::get($url);
        return $url;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Un wrapper autour de la fonction get de la librairie Requests.
 * Attrape les éventuelles exceptions et affiche un message générique à
 * la place.
 */
function get_request ($url) {

    try {
        $response = Requests::get($url);
    } catch (Exception $e) {
        return "Erreur lors du chargement de la page : $url";
    }

    return $response;
}

/**
 * Trouve la version d'un site spip.
 */
function spip_get_version ($home_url) {

    $response = get_request($home_url . '/spip.php?page=login');

    return preg_replace('/SPIP ([0-9.]+).*$/', '$1', $response->headers['composed-by']);
}

/**
 * Trouve la version de l'écran de sécurité d'un site spip. Retourne
 * false si pas d'écran de sécurité.
 */
function spip_get_ecran_securite ($home_url) {

    $response = get_request($home_url . '?test_ecran_securite');

    if ($response->status_code  !== 403) {
        return false;
    }

    return preg_replace('/^.*\(test ([0-9.]+)\).*$/', '$1', $response->body);
}

/**
 * Retourne les infos de version et de sécurité d'un site spip.
 */
function spip_get_infos ($home_url) {

    $home_url = prepare_url($home_url);

    if ($home_url) {
        return array(
            'version'        => spip_get_version($home_url),
            'ecran_securite' => spip_get_ecran_securite($home_url),
        );
    }
}