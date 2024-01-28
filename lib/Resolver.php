<?php

namespace WHMCS\Module\Addon\DomainInfoAPI;

use WHMCS\Database\Capsule;

class Resolver {

    private function createJSONResponse($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        return json_encode($data);
    }

    private function notFound() {
        return $this->createJSONResponse(['status' => 'error', 'message' => 'Not Found'], 404);
    }

    function domainPricing($params) {
        $tlds_registration = Capsule::table('tbldomainpricing')
            ->join('tblpricing', 'tbldomainpricing.id', '=', 'tblpricing.relid')
            ->select('tbldomainpricing.id', 'tbldomainpricing.extension', 'tblpricing.msetupfee')
            ->where('tblpricing.type', '=', 'domainregister')
            ->get();
        $tlds_transfer = Capsule::table('tbldomainpricing')
            ->join('tblpricing', 'tbldomainpricing.id', '=', 'tblpricing.relid')
            ->select('tbldomainpricing.id', 'tbldomainpricing.extension', 'tblpricing.msetupfee')
            ->where('tblpricing.type', '=', 'domaintransfer')
            ->get();
        // above query only returns the transfer price, so we need to get the renew prices as well
        $tlds_renew = Capsule::table('tbldomainpricing')
            ->join('tblpricing', 'tbldomainpricing.id', '=', 'tblpricing.relid')
            ->select('tbldomainpricing.id', 'tbldomainpricing.extension', 'tblpricing.msetupfee')
            ->where('tblpricing.type', '=', 'domainrenew')
            ->get();

        $zipped = array_map(null, $tlds_registration, $tlds_transfer, $tlds_renew);
        $items = [];
        foreach ($zipped as $tld) {
            $items[] = [
                'tld' => $tld[0]->extension,
                'registration' => $tld[0]->msetupfee,
                'transfer' => $tld[1]->msetupfee,
                'renew' => $tld[2]->msetupfee
            ];
        }
        
        $result = [
            'status' => 'success',
            'items' => $items,
        ];
        
        return $this->createJSONResponse($result);
    }

    function resolve($params) {
        $endpoint = $params['endpoint'];

        switch ($endpoint) {
            case 'pricing':
                return $this->domainPricing($params);
        }

        return $this->notFound();
    }

}