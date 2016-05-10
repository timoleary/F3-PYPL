<?php

class PayPal {
protected $f3;
private $creds=array();
public $endpoint;
public $redirect;
public $lineitems=array();
public $itemcounter=0;
public $itemtotal=0;
public $shippingaddress=array();
public $shippingamt;
public $taxamt;
public $returnurl;
public $cancelurl;

// Setup default values, endpoint, API creds etc
function __construct() {
	$f3=Base::instance();
	@session_start();
	$f3->sync('SESSION');

	if ($f3->get('PAYPAL.endpoint')=="production"){
		$this->endpoint='https://api-3t.paypal.com/nvp';
		$this->redirect='https://www.paypal.com/webscr&cmd=_express-checkout&token=';
	} else {
		$this->endpoint='https://api-3t.sandbox.paypal.com/nvp';
		$this->redirect='https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=';
	}

	$this->creds['USER']=$f3->get('PAYPAL.user');
	$this->creds['PWD']=$f3->get('PAYPAL.pass');
	$this->creds['SIGNATURE']=$f3->get('PAYPAL.signature');
	$this->creds['VERSION']=$f3->get('PAYPAL.apiver');
	$this->returnurl=$f3->get('PAYPAL.return');
	$this->cancelurl=$f3->get('PAYPAL.cancel');
}

// full flexibilty for any classic API call; returns array
function apireq($method, $nvp) {
	$arg=array_merge($this->creds, $nvp);
	$arg['METHOD']=$method;

	$options = array(
	'method'  => 'POST',
	'content' => http_build_query($arg),
	);

	$result=\Web::instance()->request($this->endpoint, $options);
	parse_str($result['body'],$output);
	return($output);
}

// Build array of line items & calculating item total.
function setLineItem($itemname, $itemqty=1, $itemprice){
	$i=$this->itemcounter++;
	$this->lineitems["L_PAYMENTREQUEST_0_NAME$i"]=$itemname;
	$this->lineitems["L_PAYMENTREQUEST_0_QTY$i"]=$itemqty;
	$this->lineitems["L_PAYMENTREQUEST_0_AMT$i"]=$itemprice;
	$this->itemtotal+=($itemqty*$itemprice);
}

// Set shipping address used for Express Checkout Mark.
function setShippingAddress($name, $street1, $street2, $city, $state, $zip, $countrycode){
	$this->shippingaddress['PAYMENTREQUEST_0_SHIPTONAME']=$name;
	$this->shippingaddress['PAYMENTREQUEST_0_SHIPTOSTREET']=$street1;
	$this->shippingaddress['PAYMENTREQUEST_0_SHIPTOSTREET2']=$street2;
	$this->shippingaddress['PAYMENTREQUEST_0_SHIPTOCITY']=$city;
	$this->shippingaddress['PAYMENTREQUEST_0_SHIPTOSTATE']=$state;
	$this->shippingaddress['PAYMENTREQUEST_0_SHIPTOZIP']=$zip;
	$this->shippingaddress['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE']=$countrycode;
}

// Set shipping amount.
function setShippingAmt($shippingamt){
	$this->shippingamt=$shippingamt;
}

// Set tax amount.
function setTaxAmt($taxamt){
	$this->taxamt=$taxamt;
}

// Setup Express Checkout Payment (SetExpressCheckout API Request)
// The request is stored in a session using ExpressCheckout Token as they key
// this is retrieved later to complete the transaction.
function create($paymentaction,$currency,$amt,$additional=NULL) {
	$nvp=array();
	$nvp['RETURNURL']=$this->returnurl;
	$nvp['CANCELURL']=$this->cancelurl;
	$nvp['PAYMENTREQUEST_0_PAYMENTACTION']=$paymentaction;
	$nvp['PAYMENTREQUEST_0_CURRENCYCODE']=$currency;
	$nvp['PAYMENTREQUEST_0_AMT']=$amt;

	if(isset( $this->shippingaddress ) )
	{
	$nvp=array_merge($nvp, $this->shippingaddress);
	}

	if(isset( $this->shippingamt ) )
	{
	$nvp['PAYMENTREQUEST_0_SHIPPINGAMT']=$this->shippingamt;
	}

	if(isset( $this->lineitems ) )
	{
	$this->lineitems["PAYMENTREQUEST_0_ITEMAMT"]=sprintf('%0.2f', $this->itemtotal);
	$nvp=array_merge($nvp, $this->lineitems);
	}

	if(isset( $this->taxamt ) )
	{
	$nvp['PAYMENTREQUEST_0_TAXAMT']=$this->taxamt;
	}

	if (isset($additional))
	{
	$nvp=array_merge($nvp, $additional);
	}
	

	$setec=$this->apireq('SetExpressCheckout',$nvp);
	// store for reuse
	unset($nvp['RETURNURL'],$nvp['CANCELURL']);
	$_SESSION[$setec['TOKEN']]=serialize($nvp);

	$setec['redirect']="https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=".$setec['TOKEN'];
	return $setec;
}

// Complete Express Checkout Payment (DoExpressCheckoutPayment API Request)
// Retrieve the original request from the session append EC Token & buyers PayerID.
function complete($token,$payerid) {
	$nvp=unserialize($_SESSION[$token]);
	$nvp['PAYERID']=$payerid;
	$nvp['TOKEN']=$token;

	$doec=$this->apireq('DoExpressCheckoutPayment',$nvp);
	return $doec;
}

// Get Transation & buyer details (GetExpressCheckoutDetails API Request)
function getDetails($token) {
	$nvp['TOKEN']=$token;
	$getec=$this->apireq('GetExpressCheckoutDetails',$nvp);
	return $getec;
}

// Used for shortcut method where buyers address is not known.
// Using GetExpressCheckoutDetails the buyers shipping address is added to the
// stored session.
function getShippingAddress($token) {
	$shippingaddress=$this->getDetails($token);
	$orderdetails=unserialize($_SESSION[$token]);
	$orderdetails['PAYMENTREQUEST_0_SHIPTONAME']=$shippingaddress['PAYMENTREQUEST_0_SHIPTONAME'];
	$orderdetails['PAYMENTREQUEST_0_SHIPTOSTREET']=$shippingaddress['PAYMENTREQUEST_0_SHIPTOSTREET'];
	$orderdetails['PAYMENTREQUEST_0_SHIPTOSTREET2']=$shippingaddress['PAYMENTREQUEST_0_SHIPTOSTREET2'];
	$orderdetails['PAYMENTREQUEST_0_SHIPTOCITY']=$shippingaddress['PAYMENTREQUEST_0_SHIPTOCITY'];
	$orderdetails['PAYMENTREQUEST_0_SHIPTOSTATE']=$shippingaddress['PAYMENTREQUEST_0_SHIPTOSTATE'];
	$orderdetails['PAYMENTREQUEST_0_SHIPTOZIP']=$shippingaddress['PAYMENTREQUEST_0_SHIPTOZIP'];
	$orderdetails['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE']=$shippingaddress['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE'];
	$_SESSION[$token]=serialize($orderdetails);
}

}