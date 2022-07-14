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
