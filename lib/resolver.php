<?php

namespace WHMCS\Module\Addon\DomainInfoAPI;

class Resolver {

    private function createJSONResponse($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        return json_encode($data);
    }

    function test() {
        return $this->createJSONResponse(['status' => 'success', 'message' => 'Test']);
    }

}