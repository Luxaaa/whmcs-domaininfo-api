# WHMCS Domain Info API

This module allows to query information abount domain pricing and availability status from outside WHMCS.

## Endpoints

### Domain Pricing

Returns information abount the registration, transfer and renew prices for each TLD.

Endpoint: `GET <WHMCS_URL>/index.php?m=domaininfoapi&endpoint=pricing`

Parameters

| Parameter   | Description                                                                                                                            | Type                       |
|-------------|----------------------------------------------------------------------------------------------------------------------------------------|----------------------------|
| `selection` | return only information for the provided TLDs. If not set, all TLDs are returned                                                       | `string[]`                 |
| `groups`    | return only information for tlds which are in the specified groups. The group a a TLD is defined in the WHMCS domain pricing settings. | `('hot'\|'new'\|'sale')[]` |


Response
```
{
  "status": "success",
  "items": [
    {
        "tld": "string",
        "group": "string" | null,
        "registration": number,
        "transfer": number,
        "renew": number
    }
  ]
}
```

Example
```bash
curl "<WHMCS_URL>/index.php?m=domaininfoapi&endpoint=pricing&selection[]=com&selection[]=de"
```
