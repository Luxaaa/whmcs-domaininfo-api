<?php

namespace WHMCS\Module\Addon\DomainInfoAPI;

use WHMCS\Database\Capsule;

require_once 'init.php';


class Resolver
{

    private function createJSONResponse($data, $status = 200)
    {
        header('Content-Type: application/json');
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
        $alternative_tlds = $params['alternative_tlds'] || [];   
        
        
        $result = localAPI('DomainWhois', array(
            'domain' => $domain
        ));

        if ($result['result'] != 'success') {
            return $this->createJSONResponse(['status' => 'error', 'message' => 'Invalid domain'], 404);
        }

        $domain_parts = explode('.', $domain);
        $tld = implode('.', array_slice($domain_parts, -(sizeof($domain_parts) -1)));
        $domain_part = $domain_parts[0];
        
        $alternative_results = [];
        foreach ($alternative_tlds as $tld) {
            $alt_domain = $domain_part . '.' . $tld;
            $alt_result = localAPI('DomainWhois', array(
                'domain' => $alt_domain
            ));

            if ($alt_result['result'] != 'success') {
                return $this->createJSONResponse(['status' => 'error', 'message' => 'Invalid domain'], 404);
            }
            
            $alternative_results[] = [
                'domain' => $alt_domain,
                'status' => $alt_result['status'],
            ];
        }
        
        
        $pricingDetails = $this->domainPricing([
            'selection' => [$tld, ...$alternative_tlds],
            'returnDataDirectly' => true,
        ])['items'][0];


        $resp = [
            'status' => 'success',
            'domain' => $domain,
            'domain_available' =>  $result['status'] == 'available',
            'registration_price' => $pricingDetails['registration'],
            'transfer_price' => $pricingDetails['transfer'],
            'alternative': $alternative_results
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