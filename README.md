### F3PYPL
F3PYPL is a Fat Free Framework plugin that helps quickly implement PayPal Express Checkout via the PayPal Classic API.

### Quick Start Config
Add the following custom section to your project config.

```
[PAYPAL]
user=
pass=
signature=
endpoint=sandbox
apiver=124.0
return=http://
cancel=http://
```

- user - Your PayPal API Username
- pass - Your PayPal API Password
- signature - Your PayPal API Signature
- endpoint - API Endpoint, values can be 'sandbox' or 'production'
- apiver - API Version current release is 124.0
- return - The URL that PayPal redirects your buyers to after they have logged in and clicked Continue or Pay
- cancel - The URL that PayPal redirects the buyer to when they click the cancel & return link

Add the PayPal.php file to your lib.

### Quick Start
Create a route that will initialize the transaction (SetExpressCheckout API) & redirect the buyer to PayPal.
```php
//Create & redirect
$paypal=new PayPal;
$result=$paypal->create("Sale","EUR","10.00");
$f3->reroute($result['redirect']);
```

Once the buyer is returned to your website you can simply complete the transaction (DoExpressCheckoutPayment API) by

```php
//Return & Complete
$token=$f3->get('GET.token');
$payerid=$f3->get('GET.PayerID');

$paypal=new PayPal;
$result=$paypal->complete($token, $payerid);
```
$result will contain an associative array of the API response.  Store the useful bits like status & transaction ID.

### Methods

**Setting a Shipping Address**

The setShippingAddress() method allows you to pass the shipping address to PayPal before creating the transaction. The address will appear when the buyer logs into PayPal or will be used to prepopulate the address form fields on the Guest Checkout. This address is also used in the DoExpressCheckoutPayment API call so it will appear on the payment receipt & transaction history.
```php
setShippingAddress($buyerName, $addressLine1, $addressLine2, $townCity, $regionProvince, $zipCode, $countryCode);
```
```php
// Example
$paypal->setShippingAddress('Sherlock Holmes', '221b Baker St', 'Marylebone', 'London City', 'London', 'NW16XE', 'GB');
```
**Passing Cart Line Items**

The setLineItem() method allows you to pass *multiple* shopping cart line items to PayPal. This will appear on the order summary when the buyer is redirected to PayPal and is also used in the DoExpressCheckoutPayment API so a detailed breakdown of the order is available on the PayPal receipts & transaction history.
```php
setLineItem($itemName, $itemQty, $itemPrice);
```
```php
// Example
$paypal->setLineItem("Phone Case", 1, "10.00");
$paypal->setLineItem("Smart Phone", 1, "100.00");
$paypal->setLineItem("Screen Protector", 5, "1.00");
```

**Setting the Shipping Amount**

The setShippingAmt() method allows you to specify an overall shipping amount.
```php
setShippingAmt($amount);
```
```php
// Example
$paypal->setShippingAmt('10.00');
```

**Setting the Tax Amount**

The setTaxAmt() method allows you to specify an overall tax amount.
```php
setTaxAmt($amount);
```
```php
// Example
$paypal->setTaxAmt('10.00');
```

**Creating a transaction**

The create() method will setup the transaction using the SetExpressCheckout API call. If successful a unique token is returned that identifies your newly created transaction. The buyer should then be redirected to PayPal with the EC-Token appened to the URL where they will be prompted to login or checkout as a guest.

```php
create($paymentAction,$currencyCode,$totalAmount);
```
```php
// Example
$result=$paypal->create("Sale","EUR","171.00");

// Check the API call was successful
if ($result['ACK']!='Success'){
// Handle the API error
die('Error with API call -'.$result["L_ERRORCODE0"]);
} else {
// Redirect the buyer to PayPal
$f3->reroute($result['redirect']);
}
```

**Retrieving the Buyers Shipping Address**

The getShippingAddress() method can be used to retrieve the shipping address the buyer selected or added during the PayPal checkout via the GetExpressCheckoutDetails API. 

The address is returned as an array and will be automatically included as part of the final API call so the address will appear on payment receipts.

```php
getShippingAddress($ecToken);
```
```php
// Example
// Retrieve EC Token from the URL
$token=$f3->get('GET.token');
// Retreive the buyers shipping address via the GetExpressCheckout API
$address=$paypal->getShippingAddress($token);
```

**Completing the Transaction**

The complete() method calls the DoExpressCheckoutPayment API and completes the transaction. 

```php
complete($ecToken, $payerId);
```
```php
// Example
// Retrieve EC Token & PayerID from the URL
$token=$f3->get('GET.token');
$payerid=$f3->get('GET.PayerID');

//Complete the Transaction
$result=$paypal->complete($token, $payerid);

// Check the API call was successful
if ($result['ACK']!='Success'){
// Handle the API error
die('Error with API call -'.$result["L_ERRORCODE0"]);
} else {
// Redirect the buyer a receipt or order confirmation page
// Store the status & transaction ID for your records
}
```