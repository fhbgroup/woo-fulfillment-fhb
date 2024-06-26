# Fullfilment by FHB - woocommerce plugin
Plugin for integration woocommerce store with ZOE fullfilment system

## History of changes

## Version 3.25 - 2024-04-17
- Order group feature - checkbox to activate in setting

## Version 3.24 - 2024-04-15
- Order detail box - show fhb specific order attributes
- Cron job err handling - improve job stability

## Version 3.23
- Retrieves delivery point code from custom field _fhb-delivery-point-code

## Version 3.22
- Automatic export - selecting orders created in last 14 days (previously 2 days)

## Version 3.21
- Carrier mapping contains name - REVERT
- Save woocommerce carrier name to order parameter

## Version 3.20
- Carrier mapping contains name
- notify process - change status at last (when trackingNumber, trackingLinks and carrier name are filled)

## Version 3.19
- API id into variable symbol setting

## Version 3.18
- open settings refresh available carriers

## Version 3.17
- var_symbol prefix - removed dash

## Version 3.16
- InPost delivery point support

## Version 3.15
- saving trackingNumber, trackingLinks and carrier name to order meta data after sending (fields _fhb-api-tracking-number, _fhb-api-tracking-link, _fhb-api-carrier)

## Version 3.14
- added support for new Packeta plugin

## Version 3.13
- do not mark product as exported when exporting to DEV

## Version 3.12
- autoimport settings

## Version 3.1
- grouping orders from same customer into one (before exporting)
- var_symbol add prefix

## Version 3.0
- plugin moved into new repository

### Version 2.28
- ignore countries setting - country codes of countries that will be ignored, separated by comma

### Version 2.27
- ignored product prefix setting - product wich SKU starts with set prefix will be ignored

### Version 2.26
- invoice prefix placeholder {order_id}
- tracking saving into order note (customer note)

### Version 2.25
- bulk order export from order list page

### Version 2.24
- payment gateways setting fix

### Version 2.23
- if shipping address is empty, set billing address as delivery address