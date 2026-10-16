# Torod Shipping — CS-Cart Addon

## Overview

Torod Shipping is a CS-Cart Multi-Vendor addon that provides real-time shipping rate
calculation, shipment creation, webhook-driven status tracking, and AWB label printing
via the Torod shipping aggregator API.

It is designed to coexist alongside the existing **Tryoto Shipping** addon. Admins create
duplicate shipping methods (e.g., "SMSA - Express (Torod)") and toggle them Active/Disabled
independently from Tryoto-backed methods.

## Architecture

- Implements `Tygh\Shippings\Services\IService` — the standard CS-Cart shipping service interface.
- Rate calculation calls `POST /partners` on each checkout render (no caching).
- Shipment creation is a 2-step flow: `POST /create` → `POST /ship/process`.
- Status tracking via a single webhook endpoint (`dispatch=torod.webhook`).
- Vendor warehouse codes auto-generated and registered with Torod via `POST /address`.
- SPL address enrichment reuses `is2or_spl_integration` hooks.

## Installation

1. Copy `src/` contents to the CS-Cart addons directory.
2. Install via **Administration → Addons → Torod Shipping → Install**.
3. `?:torod_shipments` table and `?:companies.warehouse_code` columns are created automatically.
4. Configure API credentials under **Settings → Torod Shipping → General**.

## Configuration

### General Settings

| Setting | Description |
|---------|-------------|
| Client ID | Torod API client ID |
| Client Secret | Torod API client secret |
| Staging API URL | Default: `https://demo.stage.torod.co/en/api` |
| Production API URL | Default: `https://torod.co/en/api` |
| Mode | `staging` or `production` |
| Webhook Secret Key | Used to validate incoming webhook requests |
| C.O.D. Payment Method ID | Maps to COD payment method for `payment` field |

### Webhook Status Mapping

8 configurable order-status mappings for each Torod shipment status:

- `READY_FOR_PICKUP`, `IN_TRANSIT`, `DELIVERED`, `FAILED_TO_DELIVER`,
  `FAILED_TO_ATTEMPT`, `CANCELLED`, `RTO`, `DAMAGE`, `LOST`

## Admin Setup

1. Enable the addon.
2. Set API credentials and mode in **Settings → Torod Shipping**.
3. Create a new shipping method (**Administration → Shipping → Add**).
4. Set **Rate calculation** to "Torod".
5. In the **Configure** tab, enter the **Courier Code** and select the **Service Type**.
6. Set status to **Active** when ready to serve rates.

## Warehouse Backfill

Existing approved vendors without a warehouse code can be bulk-processed:

```
dispatch=torod_warehouse.populate_all_warehouses
```

## Webhook Endpoint

```
POST index.php?dispatch=torod.webhook
```

Validates the `Authorization` header against the configured webhook secret key.
Updates `?:torod_shipments`, `?:shipments`, and maps status to configured CS-Cart order status.

## Database

- `?:torod_shipments` — Shipment metadata, tracking, AWB URL, status.
- `?:companies.warehouse_code` — 6-char alphanumeric warehouse code per vendor.
- `?:companies.warehouse_code_created_at` — Epoch timestamp of code creation.

## API Timeout

All HTTP calls to Torod use a 15-second timeout (`CURLOPT_TIMEOUT`), defined as
`TOROD_API_TIMEOUT` in `config.php`.

## License

Proprietary — IS2OR.
