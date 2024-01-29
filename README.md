# WHMCS Domain Info API

This module allows to query information abount domain pricing and availability status from outside WHMCS.

## Endpoints

### Domain Pricing

Returns information about the registration, transfer and renew prices for each TLD.

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

### Domain availability

Returns information about the availability of the provided domain.

Endpoint: `GET <WHMCS_URL>/index.php?m=domaininfoapi&endpoint=domainstatus`

Parameters

| Parameter          | Description                                       | Type       |
|--------------------|---------------------------------------------------|------------|
| `domain`           | the domain to query                               | `string`   |
| `alternative_tlds` | alternative tlds to query registration status for | `string[]` |

Response
Success
```json
{
  "status": "success",
  "domain": {
    "domain": "string",
    "tld": "string",
    "is_available": boolean,
    "registration_price": number,
    "transfer_price": number,
  },
  "alternatives": [{
    "domain": "string",
    "tld": "string",
    "is_available": boolean,
    "registration_price": number,
    "transfer_price": number,
  }]
}
```

Error:
returned if the given domain is invalid or one of the alternative tlds is invalid
```json
{
  "status": "error",
  "message": "invalid Domain"
}
```
}
}
```

Example

```bash
curl "<WHMCS_URL>/index.php?m=domaininfoapi&endpoint=domainstatus&domain=example.com&alternative_tlds[]=de"
```
