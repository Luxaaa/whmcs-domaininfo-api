<?php

namespace WHMCS\Module\Addon\DomainInfoAPI;

use WHMCS\Database\Capsule;

require_once 'init.php';

require_once 'helpers.php';

class Resolver
{

    private function createJSONResponse($data, $status = 200)
    {
        header('Content-Type: application/json');
        // CORS
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET');

        http_response_code($status);
        return json_encode($data);
    }

    private function notFound()
    {
        return $this->createJSONResponse(['status' => 'error', 'message' => 'Not Found'], 404);
    }

    function domainPricing($params)
    {
        // get parameters
        $selection = $params['selection'];
        $groups = $params['groups'];

        $tlds_registration = Capsule::table('tbldomainpricing')
            ->join('tblpricing', 'tbldomainpricing.id', '=', 'tblpricing.relid')
            ->select('tbldomainpricing.id', 'tbldomainpricing.extension', 'tbldomainpricing.group', 'tblpricing.msetupfee')
            ->where('tblpricing.type', '=', 'domainregister')
            ->get()->toArray();
        $tlds_transfer = Capsule::table('tbldomainpricing')
            ->join('tblpricing', 'tbldomainpricing.id', '=', 'tblpricing.relid')
            ->select('tbldomainpricing.id', 'tbldomainpricing.extension', 'tbldomainpricing.group', 'tblpricing.msetupfee')
            ->where('tblpricing.type', '=', 'domaintransfer')
            ->get()->toArray();
        // above query only returns the transfer price, so we need to get the renew prices as well
        $tlds_renew = Capsule::table('tbldomainpricing')
            ->join('tblpricing', 'tbldomainpricing.id', '=', 'tblpricing.relid')
            ->select('tbldomainpricing.id', 'tbldomainpricing.extension', 'tbldomainpricing.group', 'tblpricing.msetupfee')
            ->where('tblpricing.type', '=', 'domainrenew')
            ->get()->toArray();

        $zipped = array_map(null, $tlds_registration, $tlds_transfer, $tlds_renew);
        $items = [];
        foreach ($zipped as $tld) {
            // filter by selection
            if (!empty($selection)) {
                $ltd = $tld[0]->extension;
                $plain_tld = ltrim($ltd, '.');
                if (!in_array($ltd, $selection) && !in_array($plain_tld, $selection)) {
                    continue;
                }
            }

            // filter by group
            if (!empty($groups)) {
                $group = $tld[0]->group;
                if (!in_array($group, $groups)) {
                    continue;
                }
            }


            $items[] = [
                'tld' => $tld[0]->extension,
                'group' => $tld[0]->group ? $tld[0]->group : null,
                'registration' => (float)$tld[0]->msetupfee,
                'transfer' => (float)$tld[1]->msetupfee,
                'renew' => (float)$tld[2]->msetupfee
            ];
        }

        $result = [
            'status' => 'success',
            'items' => $items,
        ];

        if ($params['returnDataDirectly']) {
            return $result;
        }

        return $this->createJSONResponse($result);
    }
    
    function domainStatus($params)
    {
        $domain = $params['domain'];
        $alternative_tlds = $params['alternative_tlds']  ?: [];
        // split input domain into name and tld
        $domain_parts = explode('.', $domain);
        $domain_part = $domain_parts[0];
        $main_tld = implode('.', array_slice($domain_parts, -(sizeof($domain_parts) -1)));
        // input domain tld + alternative tlds
        $all_tlds = array_unique([$main_tld, ...$alternative_tlds]);
        // get pricing details for tlds
        $pricingDetails = $this->domainPricing([
            'selection' => $all_tlds,
            'returnDataDirectly' => true,
        ])['items'];

        $results = [];
        foreach ($all_tlds as $ltd) {
            // check if domain is available
            $d = $domain_part . '.' . $ltd;
            $res = localAPI('DomainWhois', array(
                'domain' => $d
            ));
            // if domain is not valid, skip it
            if ($res['result'] != 'success') {
                continue;
            }

            $registrar = find_registrar_for_tld($ltd);

            // find pricing
            $pricing = null;
            foreach ($pricingDetails as $pricing_item) {
                if (('.' . $ltd) == $pricing_item['tld']) {
                    $pricing = $pricing_item;
                }
            }

            $results[] = [
                'domain' => $d,
                'tld' => $ltd,
                'is_available' => $res['status'] == 'available',
                'registration_price' => $pricing['registration'],
                'transfer_price' => $pricing['transfer'],
                'registrar' => $registrar
            ];

        }
        // find data for input domain and alternatives
        $requested = null;
        $alternatives = null;
        foreach ($results as $item) {
            if ($item['tld'] == $main_tld) {
                $requested = $item;
            } else {
                $alternatives[] = $item;
            }
        }

        // create response
        $resp = [
            'status' => 'success',
            'domain' => $requested,
            "alternatives" => $alternatives
        ];
        
        return $this->createJSONResponse($resp);
    }

    function resolve($params)
    {
        $endpoint = $params['endpoint'];

        switch ($endpoint) {
            case 'pricing':
                return $this->domainPricing($params);
            case 'domainstatus':
                return $this->domainStatus($params);
        }

        return $this->notFound();
    }

}