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

function _get_config_for_registrar($registrar) {
    $rows = Capsule::table('tblregistrars')
        ->where('registrar', $registrar)
        ->get();
    $result = [];
    foreach ($rows as $row) {
        $result[$row->setting] = $row->value;
    }
    return $result;
}

function is_domain_available($sld, $tld, $registrar) {
    // include registrar file
    require_once __DIR__ . '/../../../registrars/' . $registrar . '/' . $registrar . '.php';

    // prepare params
    $params = _get_config_for_registrar($registrar);

    $params['searchTerm'] = $sld . '.' . $tld;
    $params['sld'] = $sld;
    $params['tlds'] = [$tld];

    return call_user_func($registrar . '_CheckAvailability', $params);
}