<?php

// Kickstart the framework
$f3=require('lib/base.php');

$f3->set('DEBUG',3);
if ((float)PCRE_VERSION<7.9)
	trigger_error('PCRE version is out of date');

// Load configuration
$f3->config('config.ini');
$f3->set('AUTOLOAD','controllers/');

// default page
$f3->route('GET /',
	function($f3) {
echo '<a href="/expresscheckout">Checkout with PayPal</a>';
}
);


// Setup Transaction & Redirect
$f3->route('GET /expresscheckout',
	function($f3) {
$paypal=new PayPal;
// Optional set Shipping address
$paypal->setShippingAddress('Tim Test', 'Test Address 1', 'Test Address 2', 'Test City', 'Test Province', 'D15', 'IE');
// Set Cart Items
$paypal->setLineItem("Phone Case", 5, "10.00");
$paypal->setLineItem("iStuff", 1, "100.00");
// Set Shipping
$paypal->setShippingAmt("10.00");
// Set Tax
$paypal->setTaxAmt("10.00");
// Create Transaction
$result=$paypal->create("Sale","EUR","171.00");
// Reroute buyer with resulting transaction token
if ($result['ACK']!='Success'){
	die('Error with API call -'.$result["L_LONGMESSAGE0"]);
} else {
$f3->reroute($result['redirect']);
}

}
);

// Buyer Returns from PayPal, grab the shipping address & complete the transaction.
$f3->route('GET /return',
function($f3) {
// grab payerid & token from URL
$token=$f3->get('GET.token');
$payerid=$f3->get('GET.PayerID');

$paypal=new PayPal;
// grab shipping address if not captured in advance and update address in session.
$paypal->getShippingAddress($token);
// complete the transaction
$result=$paypal->complete($token, $payerid);
echo "<pre>";
print_r($result);
echo "</pre>";
}
);

$f3->run();