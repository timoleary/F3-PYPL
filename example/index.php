<?php
require_once('vendor/autoload.php');

$f3 = \Base::instance();
$f3->config('config.ini');
$f3->set('AUTOLOAD','controllers/');

$f3->route('GET /',
    function ($f3) {

        $basket = new \Basket();
        $basket->drop();

        // add item
        $basket->set('name', 'Coffee');
        $basket->set('amount', '5.00');
        $basket->set('qty', '2');
        $basket->save();
        $basket->reset();
        // add item
        $basket->set('name', 'Moka pot');
        $basket->set('amount', '10.00');
        $basket->set('qty', '1');
        $basket->save();
        $basket->reset();

        $cart = $basket->find();
        foreach ($cart as $item) {
            $subtotal += $item['amount'] * $item['qty'];
            $itemcount+=$item['qty'];
        }


        $f3->set('itemcount', $itemcount);
        $f3->set('cartitems', $cart);
        $f3->set('subtotal', sprintf("%01.2f", $subtotal));
        echo \Template::instance()->render('cart.html');
    }
);

$f3->route('GET /ecs',
    function ($f3) {

        $basket = new \Basket();
        $cartitems = $basket->find();

        $paypal = new PayPal;
        $subtotal = $paypal->copyBasket($cartitems);
        $options=array('BRANDNAME'=>'F3PYPL');
        $result = $paypal->create("Sale", "EUR", $subtotal, $options);

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

$f3->route('GET /cancel',
    function ($f3) {
            // Redirect Buyer to PayPal
            $f3->reroute('/');
});


$f3->route('GET /ecreview',
    function ($f3) {
        // grab token & PayerID from URL
        $token = $f3->get('GET.token');
        $payerid = $f3->get('GET.PayerID');

        if (empty($token)) {
            $f3->reroute('/');
        }

        $paypal = new PayPal;
        $buyerdetails = $paypal->getDetails($token);

        $paypal->updateShippingAddress(
        	$token, 
        	$buyerdetails[SHIPTONAME], 
        	$buyerdetails[SHIPTOSTREET], 
        	$buyerdetails[SHIPTOSTREET2], 
        	$buyerdetails[SHIPTOCITY], 
        	$buyerdetails[SHIPTOSTATE], 
        	$buyerdetails[SHIPTOZIP], 
        	$buyerdetails[SHIPTOCOUNTRYCODE]);

        $shippingaddress = array(
            $buyerdetails[SHIPTONAME],
            $buyerdetails[SHIPTOSTREET],
            $buyerdetails[SHIPTOSTREET2],
            $buyerdetails[SHIPTOCITY],
            $buyerdetails[SHIPTOSTATE],
            $buyerdetails[SHIPTOZIP],
            $buyerdetails[SHIPTOCOUNTRY]);

        $paypal->updateShippingAmt($token, '5.00');

        $basket = new \Basket();
        $cart = $basket->find();
        foreach ($cart as $item) {
            $subtotal += $item['amount'] * $item['qty'];
        }

        $f3->set('cartitems', $cart);

        $shipping = "5.00";
        $f3->set('cartitems', $cart);
        $f3->set('itemcount', $basket->count());
        $f3->set('paypalemail', $buyerdetails['EMAIL']);
        $f3->set('subtotal', sprintf("%01.2f", $subtotal));
        $f3->set('shipping', sprintf("%01.2f", $shipping));
        $f3->set('ordertotal', sprintf("%01.2f", $subtotal + $shipping));
        $f3->set('shippingaddress', $shippingaddress);
        $f3->set('complete', "complete?token=$token&PayerID=$payerid");
        echo \Template::instance()->render('ecreview.html');
    }
);


$f3->route('GET /checkout',
    function ($f3) {

        $basket = new \Basket();
        $f3->set('itemcount', $basket->count());
        echo \Template::instance()->render('checkout.html');

    }
);

$f3->route('POST /review',
    function ($f3) {


        $shippingaddress = array(
            'name' => $f3->get('POST.name'),
            'address1' => $f3->get('POST.address1'),
            'address2' => $f3->get('POST.address2'),
            'city' => $f3->get('POST.city'),
            'state' => $f3->get('POST.state'),
            'postcode' => $f3->get('POST.postcode'),
            'country' => $f3->get('POST.country')
        );

        $f3->set('SESSION.address', serialize($shippingaddress));

        $basket = new \Basket();
        $cart = $basket->find();
        foreach ($cart as $item) {
            $subtotal += $item['amount'];
        }

        $f3->set('cartitems', $cart);
        $shipping = "5.00";

        $f3->set('itemcount', $basket->count());
        $f3->set('cartitems', $cart);
        $f3->set('subtotal', sprintf("%01.2f", $subtotal));
        $f3->set('shipping', sprintf("%01.2f", $shipping));
        $f3->set('ordertotal', sprintf("%01.2f", $subtotal + $shipping));
        $f3->set('shippingaddress', $shippingaddress);
        echo \Template::instance()->render('review.html');
    }
);


$f3->route('GET /ecm',
    function ($f3) {

        $basket = new \Basket();
        $cartitems = $basket->find();

        $paypal = new PayPal;
        $subtotal = $paypal->copyBasket($cartitems);


        $shippingaddress = unserialize($f3->get('SESSION.address'));

        $paypal->setShippingAddress(
            $shippingaddress['name'],
            $shippingaddress['address1'],
            $shippingaddress['address2'],
            $shippingaddress['city'],
            $shippingaddress['state'],
            $shippingaddress['postcode'],
            $shippingaddress['country']);


        $shipping = "5.00";
        $paypal->setShippingAmt($shipping);

        $ordertotal = $subtotal + $shipping;


        $options = array('ADDROVERRIDE' => '1', 'RETURNURL' => 'http://yourdomain.tld/complete',
        				 'BRANDNAME'=>'F3PYPL');
        $result = $paypal->create("Sale", "EUR", $ordertotal, $options);

        // Reroute buyer to PayPal with resulting transaction token
        if ($result['ACK'] != 'Success') {
            // Handle API error code
            die('Error with API call - ' . $result["L_ERRORCODE0"]);
        } else {
            // Redirect Buyer to PayPal
            $f3->reroute($result['redirect'] . '&useraction=commit');
        }

    }
);


$f3->route('GET /complete',
    function ($f3) {

        // grab token & PayerID from URL
        $token = $f3->get('GET.token');
        $payerid = $f3->get('GET.PayerID');

        // complete the transaction
        $paypal = new PayPal;
        $result = $paypal->complete($token, $payerid);

        if ($result['ACK'] != 'Success') {
            // Handle API error code
            die('Error with API call - ' . $result["L_ERRORCODE0"]);
        } else {

            // Update back office - save transaction id, payment status etc
            // Display thank you/receipt to the buyer.
            $f3->set('itemcount', 0);
            $f3->set('txnid',$result['PAYMENTINFO_0_TRANSACTIONID']);
            echo \Template::instance()->render('receipt.html');
        }

    }
);



$f3->route('GET /refund',
    function ($f3) {

        // Get transaction id from URL
        $txnid = $f3->get('GET.txnid');

        // Refund the transaction
        $paypal = new PayPal;
        $result = $paypal->refund($txnid);

        if ($result['ACK'] != 'Success') {
            // Handle API error code
            die('Error with API call - ' . $result["L_ERRORCODE0"]);
        } else {

        		echo "<pre>";
				print_r($result);
				echo "</pre>";

        }

    }
);


$f3->run();