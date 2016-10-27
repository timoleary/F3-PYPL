# F3-PYPL [![Build Status](https://travis-ci.org/kreative/F3-PYPL.svg?branch=master)](https://travis-ci.org/kreative/F3-PYPL)
F3-PYPL is a Fat Free Framework plugin that helps quickly implement PayPal Express Checkout via the PayPal Classic API.

**Express Checkout Shortcut & Mark Demo**  
*Please use a sandbox account to make payments.*

**[F3-PYPL Integration Demo](https://timoleary.net/f3pypl)**



## Quick Start Config
Add the following custom section to your project config.

```
[PAYPAL]
user=
pass=
signature=
endpoint=sandbox
apiver=204.0
return=http://
cancel=http://
log=0
```

- user - Your PayPal API Username
- pass - Your PayPal API Password
- signature - Your PayPal API Signature
- endpoint - API Endpoint, values can be 'sandbox' or 'production'
- apiver - API Version current release is 204.0
- return - The URL that PayPal redirects your buyers to after they have logged in and clicked Continue or Pay
- cancel - The URL that PayPal redirects the buyer to when they click the cancel & return link
- log - logs all API requests & responses to paypal.log

If you prefer you can also pass an array with above values when you instantiate the classes.

```php
// F3-PYPL config
$ppconfig = array(
'user'=>'apiusername',
'pass'=>'apipassword',
'signature'=>'apisignature',
'endpoint'=>'sandbox',
'apiver'=>'204.0',
'return'=>'http://',
'cancel'=>'http://',
'log'=>'1'
);

// Instantiate the class with config
$paypal=new PayPal($ppconfig);
```


**Manual Install**
Copy the `lib/paypal.php` file into your `lib/` or your AUTOLOAD folder.  
or  
**Automatic Install** via [Composer](https://packagist.org/packages/kreative/f3-pypl)


## Quick Start
### PayPal Express Checkout
Create a route that will initialize the transaction (SetExpressCheckout API) & redirect the buyer to PayPal.
```php
// Create & redirect
$paypal=new PayPal;
$result=$paypal->create("Sale","EUR","10.00");
$f3->reroute($result['redirect']);
```

Once the buyer is returned to your website you can simply complete the transaction (DoExpressCheckoutPayment API) by

```php
// Return & Complete
$token=$f3->get('GET.token');
$payerid=$f3->get('GET.PayerID');

$paypal=new PayPal;
$result=$paypal->complete($token, $payerid);
```
$result will contain an associative array of the API response.  Store the useful bits like status & transaction ID.

### PayPal Pro
```php
// Process credit/debit card payment
$paypal=new PayPal;
$result=$paypal->dcc("Sale", "EUR", "10.00", $cardtype, $cardnumber, $expdate, $cvv, $ipaddress);
```
$result will contain an associative array of the API response.  Store the useful bits like status & transaction ID.

## Methods

**Setting a Shipping Address**

The setShippingAddress() method allows you to pass the shipping address to PayPal before creating the transaction. The address will appear when the buyer logs into PayPal or will be used to populate the address form fields on the Guest Checkout. This address is also used in the *DoExpressCheckoutPayment* API call so it will appear on the payment receipt & transaction history.
```php
setShippingAddress($buyerName, $addressLine1, $addressLine2, $townCity, $regionProvince, $zipCode, $countryCode);
```
```php
// Example
$paypal->setShippingAddress('Sherlock Holmes', '221b Baker St', 'Marylebone', 'London City', 'London', 'NW16XE', 'GB');
```
**Passing Cart Line Items**

The setLineItem() method allows you to pass *multiple* shopping cart line items to PayPal. This will appear on the order summary when the buyer is redirected to PayPal and is also used in the *DoExpressCheckoutPayment* API so a detailed breakdown of the order is available on the PayPal receipts & transaction history.
```php
setLineItem($itemName, $itemQty, $itemPrice);
```
```php
// Example
$paypal->setLineItem("Phone Case", 1, "10.00");
$paypal->setLineItem("Smart Phone", 1, "100.00");
$paypal->setLineItem("Screen Protector", 5, "1.00");
```

**Passing Cart Line Items from Basket**

The copyBasket() method allows you to transfer the F3 Basket line items to PayPal and returns a subtotal. If not specified quantity will default to 1.
```php
copyBasket($basket, $name, $amount);
```
```php
// Example if using the default example naming conventions
$basket->set('name','peach');
$basket->set('amount','10.00');
$basket->set('qty','1');
$basket->save();
$basket->reset();

$basketcontents = $basket->find();  // array of basket items

$paypal = new PayPal;
$itemtotal=$paypal->copyBasket($basketcontents);
```

```php
// Example if using your own naming conventions
$basket->set('item','peach');
$basket->set('cost','10.00');
$basket->set('quantity','2');
$basket->save();
$basket->reset();

$basketcontents = $basket->find();  // array of basket items

$paypal = new PayPal;
$itemtotal=$paypal->copyBasket($basketcontents,'item','quantity','cost'); // returns 20.
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

The create() method will setup the transaction using the *SetExpressCheckout* API call. If successful a unique token is returned that identifies your newly created transaction. The buyer should then be redirected to PayPal with the EC-Token appended to the URL where they will be prompted to login or checkout as a guest.

```php
create($paymentAction,$currencyCode,$totalAmount,$optional[]);
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

**Retrieving details from PayPal**

Once the buyer is returned from PayPal to your return URL the getDetails() method can be used to retrieve all the transaction and buyer details via *GetExpressCheckoutDetails* API before completing the transaction. All details are returned as an array.

```php
getDetails($ecToken);
```
```php
// Example
// Retrieve EC Token from the URL
$token=$f3->get('GET.token');
// Retrieve the buyers shipping address via the GetExpressCheckout API
$address=$paypal->getDetails($token);
```

**Updating the Buyers Shipping Address**

The updateShippingAddress() method can be used to update the shipping address the buyer selected or added during the PayPal checkout via the *GetExpressCheckoutDetails* API. (Use getDetails() to retrieve this info).

The address is either added or updated in the current user session associated with the Express Checkout token. The address is used in the final API call so the address will appear on payment receipts.

```php
updateShippingAddress($token, $name, $street1, $street2, $city, $state, $zip, $countrycode);
```
```php
// Example
// Retrieve EC Token from the URL
$token=$f3->get('GET.token');
// Retreive the buyers shipping address via the GetExpressCheckout API
$paypal = new PayPal;
$buyerdetails = $paypal->getDetails($token);

$paypal->updateShippingAddress($token, $buyerdetails[SHIPTONAME], $buyerdetails[SHIPTOSTREET], $buyerdetails[SHIPTOSTREET2], $buyerdetails[SHIPTOCITY], $buyerdetails[SHIPTOSTATE], $buyerdetails[SHIPTOZIP], $buyerdetails[SHIPTOCOUNTRYCODE]);
```

**Completing the Transaction**

The complete() method calls the *DoExpressCheckoutPayment* API and completes the transaction. 

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
if ($result['ACK'] != 'Success' && $result['ACK'] != 'SuccessWithWarning'){
// Handle the API error
die('Error with API call -'.$result["L_ERRORCODE0"]);
} else {
// Redirect the buyer a receipt or order confirmation page
// Store the status & transaction ID for your records
}
```

**Refunding a Transaction**

The refund() method calls the *RefundTransaction* API and refunds the transaction. There are two types of refunds Full which will refund the full amount or partial which will refund an amount specified.

```php
refund($txnId, [$type, $currencycode, $amt]);
```

```php
// Fake Transaction ID for 20..
$txnId='FAKETXNID';

//Partial Refund
refund($txnId, 'Partial', 'EUR', '10.00']);

//Full Refund
refund($txnId);
```

## Express Checkout Mark (ECM)
The following is a quick guide on implementing PayPal Express Checkout as a payment method (Express Checkout Mark) and creating a Sale transaction where funds are immediately captured.  In this flow, buyers initiate the Express Checkout flow after you have collected all their information such as name, email, shipping & billing address.

![ECM payment flow](https://www.paypalobjects.com/webstatic/en_US/developer/docs/ec/ec-page-flow.png)

When the buyer chooses to pay with PayPal, the Express Checkout flow commences.

Define a new route that will be used to setup the Express Checkout transaction and redirect the buyer to PayPal.

```php
$f3->route('GET /expresscheckout',
function ($f3) {

//Instantiate the class
$paypal = new PayPal;

// Set the shipping address (if required).
$paypal->setShippingAddress('John Doe', 'Test Address 1', 'Test Address 2', 'Test City', 'Test Province', 'D15', 'IE');

// Set Cart Items
$paypal->setLineItem("Phone Case", 1, "10.00"); //10.00
$paypal->setLineItem("Smart Phone", 1, "200.00"); //200.00
$paypal->setLineItem("Screen Protector", 5, "1.00"); //5.00

// Set Shipping amount
$paypal->setShippingAmt("10.00");

// Set Tax
$paypal->setTaxAmt("21.00");

/*
Prevent the buyer from changing the 
shipping address on the PayPal website.
*/
$optional=array('ADDROVERRIDE'=>1);

// Create Transaction, Total amount = Cart Items + Shipping Amount + Tax Amount
$result = $paypal->create("Sale", "EUR", "246.00", $optional);

// Reroute buyer to PayPal with resulting transaction token
if ($result['ACK'] != 'Success') {
// Handle API error code
die('Error with API call - ' . $result["L_ERRORCODE0"]);
} else {
// Redirect Buyer to PayPal
$f3->reroute($result['redirect']);
}

}
);
```
When we create the transaction a token value will be returned in the response. The buyer is redirected to a specific URL with the token value defined so PayPal knows what transaction to display the buyer. 

For simplicity the correct URL is returned from the create() method as the 'redirect' value. 


After the buyer logs in or fills out their payment information on the guest checkout flow they will be redirected back to the URL defined in the PayPal section of your project config. 

The URL will have two values appended to it, **token** & **PayerID**. The token will be the same EC token that is returned when you first created the transaction and the PayerID is a unique identifier for the buyers PayPal account.

At this stage in the checkout you can either show an order review with an option to Complete or simply complete the transaction and display an order receipt/summary.

###Order Review Page - optional step
To display an order review page we can request all the transaction details from PayPal using the **getDetails()** method. This will include everything defined when you created the transaction and if the buyer has changed their shipping address on PayPal we can get the updated address from here.

```php
$f3->route('GET /review',
function($f3) {
// grab token from URL
$token=$f3->get('GET.token');

//Instantiate the Class
$paypal=new PayPal;

// Get Express Checkout details from PayPal
$result=$paypal->getDetails($token);

// Check for successful response
if ($result['ACK'] != 'Success') {
// Handle API error code
die('Error with API call - ' . $result["L_ERRORCODE0"]);
} else {
// Use details to render an order review page
// Show shipping address order details
}

}
);
```

###Complete Transaction / Order Summary
You can simply complete the transaction using the complete() method and display an order summary/receipt page to the buyer.

```php
$f3->route('GET /summary',
function($f3) {
// grab token & PayerID from URL
$token=$f3->get('GET.token');
$payerid=$f3->get('GET.PayerID');

//Instantiate the Class
$paypal=new PayPal;

// complete the transaction
$result=$paypal->complete($token, $payerid);

// Check for successful response
if ($result['ACK'] != 'Success' && $result['ACK'] != 'SuccessWithWarning') {
// Handle API error code
die('Error with API call - ' . $result["L_ERRORCODE0"]);
} else {
// Update back office - save transaction id, payment status etc
// Display thank you/receipt to the buyer.
}

}
);
```

## Express Checkout Shortcut (ECS)
The following is a quick guide on implementing PayPal Express Checkout on the basket and creating a Sale transaction where funds are immediately captured.  In this flow, buyers initiate the Express Checkout Shortcut flow from the shopping cart/basket bypassing sign up and address forms as we leverage the API to retrieve those details from PayPal.

![ECS payment flow](https://www.paypalobjects.com/webstatic/en_US/developer/docs/ec/ec-page-shortcut-flow.png)

When the buyer clicks the Checkout with PayPal, the Express Checkout flow commences.

Define a new route that will be used to setup the Express Checkout transaction and redirect the buyer to PayPal.

```php
$f3->route('GET /expresscheckout',
function ($f3) {

//Instantiate the class
$paypal = new PayPal;

// Set Cart Items manually or use copyBasket method.
$paypal->setLineItem("Phone Case", 1, "10.00"); //10.00
$paypal->setLineItem("Smart Phone", 1, "200.00"); //200.00

// Set Tax
$paypal->setTaxAmt("21.00");

// Create Transaction, Total amount = Cart Items + Shipping Amount + Tax Amount
$result = $paypal->create("Sale", "EUR", "231.00", $optional);

// Reroute buyer to PayPal with resulting transaction token
if ($result['ACK'] != 'Success') {
// Handle API error code
die('Error with API call - ' . $result["L_ERRORCODE0"]);
} else {
// Redirect Buyer to PayPal
$f3->reroute($result['redirect']);
}

}
);
```
When we create the transaction a token value will be returned in the response. The buyer is redirected to a specific URL with the token value defined so PayPal knows what transaction to display the buyer. 

For simplicity the correct URL is returned from the create() method as the 'redirect' value. 


After the buyer logs in or fills out their payment information on the guest checkout flow they will be redirected back to the URL defined in the PayPal section of your project config. 

The URL will have two values appended to it, **token** & **PayerID**. The token will be the same EC token that is returned when you first created the transaction and the PayerID is a unique identifier for the buyers PayPal account.

At this stage in the checkout you can either show an order review with an option to Complete or simply complete the transaction and display an order receipt/summary.

###Order Review Page
To display an order review page we can request all the transaction details from PayPal using the **getDetails()** method. This will include everything defined when you created the transaction and if the buyer has changed their shipping address on PayPal we can get the updated address from here.

```php
$f3->route('GET /review',
function($f3) {
// grab token from URL
$token=$f3->get('GET.token');

//Instantiate the Class
$paypal=new PayPal;

// Get Express Checkout details from PayPal
$buyerdetails=$paypal->getDetails($token);

// Check for successful response
if ($buyerdetails['ACK'] != 'Success') {
// Handle API error code
die('Error with API call - ' . $buyerdetails["L_ERRORCODE0"]);
} else {
// Use details of $result to render an order review page
// Show shipping address order details

// Update the session to store the new shipping address
// this address is passed in the final API call
$paypal->updateShippingAddress($token, $buyerdetails[SHIPTONAME], $buyerdetails[SHIPTOSTREET], $buyerdetails[SHIPTOSTREET2], $buyerdetails[SHIPTOCITY], $buyerdetails[SHIPTOSTATE], $buyerdetails[SHIPTOZIP], $buyerdetails[SHIPTOCOUNTRYCODE]);

// Update the session & order total with a new shipping amount
$paypal->updateShippingAmt($token, '10.00');

}

}
);
```

###Complete Transaction / Order Summary
You can simply complete the transaction using the complete() method and display an order summary/receipt page to the buyer.

```php
$f3->route('GET /summary',
function($f3) {
// grab token & PayerID from URL
$token=$f3->get('GET.token');
$payerid=$f3->get('GET.PayerID');

//Instantiate the Class
$paypal=new PayPal;

// complete the transaction
$result=$paypal->complete($token, $payerid);

// Check for successful response
if ($result['ACK'] != 'Success' && $result['ACK'] != 'SuccessWithWarning') {
// Handle API error code
die('Error with API call - ' . $result["L_ERRORCODE0"]);
} else {
// Update back office - save transaction id, payment status etc
// Display thank you/receipt to the buyer.
}

}
);
```

## Recurring Payments
The following is a quick guide on implementing a recurring payment (subscription) via the classic API.

Define a new route that will be used to setup the Recurring Payment and redirect the buyer to PayPal.

```php
$f3->route('GET /rp',
function ($f3) {

//Instantiate the Recurring Payments Class
$paypal = new PayPalRP;

//Set a descriptive name for the Recurring Payment
$result = $paypal->setupRP("Test Subscription");

// Reroute buyer to PayPal with resulting transaction token
if ($result['ACK'] != 'Success') {
// Handle API error code
die('Error with API call - ' . $result["L_ERRORCODE0"]);
} else {
// Redirect Buyer to PayPal
$f3->reroute($result['redirect']);
}
}
);
```

Just like Express Checkout (we're leveraging the same API call) when we create the Recurring Payment a token value will be returned in the response. The buyer is redirected to a specific URL with the token value defined so PayPal knows what transaction to display the buyer. 

For simplicity the correct URL is returned from the create() method as the 'redirect' value. 

After the buyer logs and agree's to the Recurring Payment they will be redirected back to the URL defined in the PayPal section of your project config.

The URL will have one value appended to it **token**. The token will be the same token that is returned when you first created the recurring payment.

We now setup the terms of the recurring payment and create the profile.

```php
$f3->route('GET /rpcreate',
function ($f3) {

//Instantiate the Recurring Payments Class
$paypal = new PayPalRP;

//Define the terms of the recurring payment profile.
$amt="10.00";
$startdate=date('Y-m-d')."T00:00:00Z"; // UTC/GMT format eg 2016-10-25T18:00:00Z
$period="Day"; // Day, Week, SemiMonth, Month, Year
$frequency="2"; // Cannot exceed one year
$currency="EUR";

$paypal->setRPDetails($amt, $startdate, $period, $frequency, $currency);

// grab token from URL
$token = $f3->get('GET.token');

//Create Recurring Payment Profile
$result = $paypal->createRP($token);

// Reroute buyer to PayPal with resulting transaction token
if ($result['ACK'] != 'Success' && $result['ACK'] != 'SuccessWithWarning') {
// Handle API error code
die('Error with API call - ' . $result["L_ERRORCODE0"]);
} else {
exit(print_r($result));
}

}
);
````

## Reference Transactions / Billing Agreements
The following is a quick guide on implementing Express Checkout Reference Transactions via the classic API.

Define a new route that will be used to setup the billing agreement and redirect the buyer to PayPal.

```php
$f3->route('GET /basetup',
function ($f3) {

//Instantiate the Reference Transactions Class
$paypal = new PayPalRT;

//Set a descriptive name for the Recurring Payment
$result = $paypal->setupRP("Test Subscription");

// Reroute buyer to PayPal with resulting transaction token
if ($result['ACK'] != 'Success') {
// Handle API error code
die('Error with API call - ' . $result["L_ERRORCODE0"]);
} else {
// Redirect Buyer to PayPal
$f3->reroute($result['redirect']);
}
}
);
```

When the buyer returns from PayPal we use the EC Token to create the Billing agreement using the CreateBillingAgreement API request. A successful response will contain a ['BILLINGAGREEMENTID'] value. Save this value as it is required to create future reference transactions.

```php
$f3->route('GET /bacreate',
    function ($f3) {

        // grab token & PayerID from URL
        $token = $f3->get('GET.token');

        // complete the transaction
        $paypal = new PayPalRT;
        $result = $paypal->createBA($token);

		if ($result['ACK'] != 'Success' && $result['ACK'] != 'SuccessWithWarning') {
            // Handle API error code
            die('Error with API call - ' . $result["L_ERRORCODE0"]);
        } else {
            print_r($result);
            // Update back office - save the billing agreement id.
            // Display thank you/receipt to the buyer.
        }

    }
);
```

Once you have a valid billing agreement ID for the buyer you can create/complete a transaction on their behalf using the DoReferenceTransaction API.

```php
// Create the transaction
$paypal = new PayPalRT;
$result = $paypal->doRT($billingAgreementId, 'Sale', 'EUR', '10.00');

if ($result['ACK'] != 'Success' && $result['ACK'] != 'SuccessWithWarning') {
    // Handle API error code
    die('Error with API call - ' . $result["L_ERRORCODE0"]);
} else {
    print_r($result);
    // Update back office - save transaction id, payment status etc
    // Display thank you/receipt to the buyer if present.
}
```

## PayPal Pro
To process credit/debit card numbers directly you can use the dcc() method.

```php
$f3->route('GET /dcc',
function ($f3) {

//Instantiate the PayPal Class
$paypal = new PayPal;

$paymentaction="Sale"; // Can be Sale or Authorization
$currencycode="EUR"; // 3 Character currency code
$amount="10.00"; // Amount to charge
$cardtype='Visa'; // Visa, MasterCard, Discover etc
$cardnumber='XXXXXXXXXXXXXXXX'; // Valid card number
$expdate='122020'; // format MMYYYY
$cvv='123'; // Valid security code
$ipaddress='127.0.0.1';

$result=$paypal->dcc($paymentaction, $currencycode, $amount, $cardtype, $cardnumber, $expdate, $cvv, $ipaddress);

// $result will contain an associative array of the API response.  Store the useful bits like status & transaction ID.

if ($result['ACK'] != 'Success' && $result['ACK'] != 'SuccessWithWarning') {
// Handle API error code
die('Error with API call - ' . $result["L_ERRORCODE0"]);
} else {
exit(print_r($result));
}

}
);
````

## License
F3-PYPL is licensed under GPL v.3