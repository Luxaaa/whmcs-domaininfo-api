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
    // call is_available function of the registrar
    $res = call_user_func('\\WHMCS\\Module\\Registrar\\' . $registrar . '\\' . $registrar . '_CheckAvailability', $domain);
    return $res;
}