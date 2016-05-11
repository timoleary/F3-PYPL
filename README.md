### F3PYPL
F3PYPL is a Fat Free Framework plugin that helps quickly implement PayPal Express Checkout via the PayPal Classic API.

### Quick Start
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

Add the PayPal.php file to your lib or 

### Quick Start
Create a route that will initialize the transaction & redirect the buyer to PayPal.
```php
//Create & redirect
$paypal=new PayPal;
$result=$paypal->create("Sale","EUR","10.00");
$f3->reroute($result['redirect']);
```

Once the buyer is returned to your website you can simply complete the transaction by

```php
//Return & Complete
$token=$f3->get('GET.token');
$payerid=$f3->get('GET.PayerID');

$paypal=new PayPal;
$result=$paypal->complete($token, $payerid);
```