<?php

use WHMCS\Database\Capsule;

/**
 * returns the name of the autoreg for a given TLD
 * @param string $ltd The TLD to find the registrar for (e.g. 'com', 'net', 'org')
 * @return ?string
 */
function find_registrar_for_tld($ltd): ?string
{
    // add leading dot to TLD if it's missing
    if (strpos($ltd, '.') !== 0) {
        $ltd = '.' . $ltd;
    }

    // get tld config from WHMCS
    $config = Capsule::table('tbldomainpricing')
        ->where('extension', $ltd)
        ->first();
    if(!$config) return null;

    return $config->autoreg;
}

function is_domain_available($domain, $registrar) {
    // include registrar file
    require_once __DIR__ . '/../../../registrars/' . $registrar . '/' . $registrar . '.php';

    // prepare params
    $params = call_user_func($registrar . '_getConfigArray');
    $params['searchTerm'] = $domain;

    $res = call_user_func($registrar . '_CheckAvailability', $params);
    return $res;
}