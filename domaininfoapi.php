<?php

use WHMCS\Module\Addon\DomainInfoAPI\Resolver;


if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

zse

function domaininfoapi_config()
{
    return [
        'name' => 'Domain Info API',
        'description' => 'Adds an API to query domain information from outside WHMCS.',
        'author' => 'Luca Drefke',
        'language' => 'english',
        'version' => '1.0',
        'fields' => []
    ];
}


function domaininfoapi_activate() {
    return [
        'status' => 'success',
    ];
}

function domaininfoapi_deactivate() {
    return [
        'status' => 'success',
    ];
}

// function for the public api
function domaininfoapi_clientarea($vars) {

    $resolver = new Resolver();
    echo $resolver->test();
    exit;
}



