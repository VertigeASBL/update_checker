#!/usr/bin/php
<?php

require 'vendor/autoload.php';

/* L'url du site est donnée par la 6ème colonne du tableau donné en
   entrée */
define(NO_COL_URL, 5);

$options = getopt('vk:o:', array('verbose', 'key:', 'output:'));

$verbose = (array_key_exists('v', $options) or array_key_exists('verbose', $options));
$key = $options['k'] ?: $options['key'];

if (! $key) {
	die('il faut donner la clé du document en option via le paramètre --key');
}

$input_file  = 'https://docs.google.com/spreadsheets/d/' . $key . '/pub?output=csv';
$output_file = $options['output'] ?: $options['o'] ?: 'output.csv';

$response = get_request($input_file);
if (is_string($response)) {
	die($response);
}

if (! $response->success) {
	die('Impossible de trouver le document demandé. Avez-vous la bonne clé ?');
}

var_dump($output_file);

$csv = array_map('str_getcsv', explode("\n", $response->body));

/**
 * Main
 *
 * On lit le fichier csv passé en argument. Pour chaque ligne, on teste
 * le site est ajoute des colonnes contenant les infos. On écrit alors
 * ceci dans un nouveau fichier csv.
 */
if (($out_handle = fopen($output_file, 'w')) !== false) {

	foreach ($csv as $i => $data) {

        $url = $data[NO_COL_URL];

        if (!empty($url)) {

            $infos = spip_get_infos($url);
            if ($infos) {
                $data[] = $infos['version'];
                $data[] = $infos['ecran_securite'];
                $data[] = $infos['serveur'];

                if ($verbose) {
	                echo "Mise à jour des informations de l'url $url\n";
                }
            } else {
	            if ($verbose) {
		            echo "Aucune information de version trouvée pour l'url $url\n";
	            }
            }
        }

        fputcsv($out_handle, $data);
    }
}

if ($out_handle) {
    fclose($out_handle);
} else {
	die('erreur lors de l\'écriture du fichier');
}


/**
 * Vérifie le format d'une url, et teste que le site existe bien.
 * Retourne un url formattée pour la fonction spip_get_infos.
 */
function prepare_url($url) {

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
function get_request($url) {

    try {
        $response = Requests::get($url);
    } catch (Exception $e) {
        echo "Erreur lors du chargement de la page : $url\n";
    }

    return $response;
}

/**
 * Trouve la version d'un site spip.
 *
 * @param $home_url  L'url de la racine du site. Sans / à la fin.
 */
function spip_get_version($home_url) {

    $response = get_request($home_url . '/spip.php?page=login');

    return preg_replace('/SPIP ([0-9.]+).*$/', '$1', $response->headers['composed-by']);
}

/**
 * Trouve la version de l'écran de sécurité d'un site spip. Retourne
 * false si pas d'écran de sécurité.
 *
 * @param $home_url  L'url de la racine du site. Sans / à la fin.
 */
function spip_get_ecran_securite($home_url) {

    $response = get_request($home_url . '/spip.php?test_ecran_securite');

    if ($response->status_code  !== 403) {
        return false;
    }

    return preg_replace('/^.*\(test ([0-9.]+)\).*$/', '$1', $response->body);
}

function get_server_name($home_url) {

    // Lire les informations de l'url
    $url_info = parse_url($home_url);

    // S'il y a un host de trouvé, on traite les dns
    if ($url_info['host']) {
        // Récupération des infor DNS
        $dns = dns_get_record($url_info['host'], DNS_A);

        // Convertir l'IP en nom de serveur
        if (!empty($dns[0]['ip'])) {
            return gethostbyaddr($dns[0]['ip']);
        } else {
            echo "erreur: pas d'IP pour $home_url\n";
        }
    } else {
        return false;
    }
}

/**
 * Retourne les infos de version et de sécurité d'un site spip.
 */
function spip_get_infos($home_url) {

    $home_url = prepare_url($home_url);

    if ($home_url) {
        return array(
            'version'        => spip_get_version($home_url),
            'ecran_securite' => spip_get_ecran_securite($home_url),
            'serveur' => get_server_name($home_url)
        );
    }
}
