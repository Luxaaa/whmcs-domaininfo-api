<?php

namespace WHMCS\Module\Addon\DomainInfoAPI;

class Resolver {

    private function createJSONResponse($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        return json_encode($data);
    }

    private function notFound() {
        return $this->createJSONResponse(['status' => 'error', 'message' => 'Not Found'], 404);
    }

    function resolve($params) {
        $endpoint = $params['endpoint'];

        switch ($endpoint) {
            case 'pricing':
                return $this->createJSONResponse(['status' => 'success', 'data' => ['domain' => ['com' => ['register' => 10.99, 'renew' => 10.99, 'transfer' => 10.99]]]]);
        }

        return $this->notFound();
    }

}