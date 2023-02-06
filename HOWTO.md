### Fix broken purchase
```php
$transactionID = "ze3bbr5ob37sj6dws0260me1rqf98fnj"
$paymentID = "6MJ579830D8460143"
$invoiceID = 48227760

$pds = app()->make(App\Payment\General\PurchaseDataService::class)
$smm = app()->make(App\Managers\ServiceModuleManager::class)
$purchase = $pds->restorePurchase($transactionID)
$purchase->setPayment(["payment_id" => $paymentID, "invoice_id" => $invoiceID])
$serviceModule = $smm->get($purchase->getServiceId());
$boughtServiceId = $serviceModule->purchase($purchase);
```

### Issue invoice
```php
$is = app()->make(App\Payment\Invoice\InvoiceService::class);
$is->create(
    new App\Payment\General\BillingAddress(
        "Dawid Szyna",
        "",
        "ul. Warszawska 1",
        "05-270",
        "Marki"
    ),
    new App\Payment\Invoice\PurchaseItem(
        "ss_license",
        "Licencja Sklep SMS",
        new \App\Support\Money("1944"),
        8,
        "8.5",
        "58.29.40"
    ),
    "uisim63@gmail.com",
)
```
