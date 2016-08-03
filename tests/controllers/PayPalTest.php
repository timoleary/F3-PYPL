<?php

class PayPalTest extends PHPUnit_Framework_TestCase
{


    public function setUp()
    {
        $_SESSION['test'] = 'testy';
    }


    public function testsetShippingAddress()
    {
        $paypal = new PayPal;
        $paypal->setShippingAddress('name', 'address1', 'address2', 'city', 'state', 'postcode', 'country');
        $address = $paypal->shippingaddress;

        $this->assertArrayHasKey('PAYMENTREQUEST_0_SHIPTONAME', $address);
        $this->assertArrayHasKey('PAYMENTREQUEST_0_SHIPTOSTREET', $address);
        $this->assertArrayHasKey('PAYMENTREQUEST_0_SHIPTOSTREET2', $address);
        $this->assertArrayHasKey('PAYMENTREQUEST_0_SHIPTOCITY', $address);
        $this->assertArrayHasKey('PAYMENTREQUEST_0_SHIPTOSTATE', $address);
        $this->assertArrayHasKey('PAYMENTREQUEST_0_SHIPTOZIP', $address);
        $this->assertArrayHasKey('PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE', $address);
    }


    public function testupdateShippingAddress()
    {
        $address = array('PAYMENTREQUEST_0_SHIPTONAME' => 'testname',
            'PAYMENTREQUEST_0_SHIPTOSTREET' => 'teststreet1',
            'PAYMENTREQUEST_0_SHIPTOSTREET2' => 'teststreet2',
            'PAYMENTREQUEST_0_SHIPTOCITY' => 'testcity',
            'PAYMENTREQUEST_0_SHIPTOSTATE' => 'teststate',
            'PAYMENTREQUEST_0_SHIPTOZIP' => 'testzip',
            'PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE' => 'testcountry');

        $_SESSION['ectoken'] = serialize($address);
        $_SESSION['ectoken'];

        $paypal = new PayPal;
        $paypal->updateShippingAddress('ectoken', 'name', 'address1', 'address2', 'city', 'state', 'postcode', 'country');

        $updatedaddress = unserialize($_SESSION['ectoken']);

        $this->assertArrayHasKey('PAYMENTREQUEST_0_SHIPTONAME', $updatedaddress);
        $this->assertArrayHasKey('PAYMENTREQUEST_0_SHIPTOSTREET', $updatedaddress);
        $this->assertArrayHasKey('PAYMENTREQUEST_0_SHIPTOSTREET2', $updatedaddress);
        $this->assertArrayHasKey('PAYMENTREQUEST_0_SHIPTOCITY', $updatedaddress);
        $this->assertArrayHasKey('PAYMENTREQUEST_0_SHIPTOSTATE', $updatedaddress);
        $this->assertArrayHasKey('PAYMENTREQUEST_0_SHIPTOZIP', $updatedaddress);
        $this->assertArrayHasKey('PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE', $updatedaddress);

        $this->assertEquals($updatedaddress['PAYMENTREQUEST_0_SHIPTONAME'], 'name');
        $this->assertEquals($updatedaddress['PAYMENTREQUEST_0_SHIPTOSTREET'], 'address1');
        $this->assertEquals($updatedaddress['PAYMENTREQUEST_0_SHIPTOSTREET2'], 'address2');
        $this->assertEquals($updatedaddress['PAYMENTREQUEST_0_SHIPTOCITY'], 'city');
        $this->assertEquals($updatedaddress['PAYMENTREQUEST_0_SHIPTOSTATE'], 'state');
        $this->assertEquals($updatedaddress['PAYMENTREQUEST_0_SHIPTOZIP'], 'postcode');
        $this->assertEquals($updatedaddress['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE'], 'country');
    }


    public function testsetLineItem()
    {
        $paypal = new PayPal;
        $paypal->setLineItem('item1', 1, '1.00');
        $paypal->setLineItem('item2', 5, '1.00');

        $this->assertEquals('item1', $paypal->lineitems['L_PAYMENTREQUEST_0_NAME0']);
        $this->assertEquals(1, $paypal->lineitems['L_PAYMENTREQUEST_0_QTY0']);
        $this->assertEquals('1.00', $paypal->lineitems['L_PAYMENTREQUEST_0_AMT0']);

        $this->assertEquals('item2', $paypal->lineitems['L_PAYMENTREQUEST_0_NAME1']);
        $this->assertEquals(5, $paypal->lineitems['L_PAYMENTREQUEST_0_QTY1']);
        $this->assertEquals('1.00', $paypal->lineitems['L_PAYMENTREQUEST_0_AMT1']);

        $this->assertEquals('6', $paypal->itemtotal);
    }


    public function testsetShippingAmt()
    {
        $paypal = new PayPal;
        $paypal->setShippingAmt('1.00');

        $this->assertEquals('1.00', $paypal->shippingamt);
    }

    public function testupdateShippingAmt()
    {

        $test = array('PAYMENTREQUEST_0_AMT' => '15.00',
            'PAYMENTREQUEST_0_SHIPPINGAMT' => '5.00');

        $_SESSION['ectoken'] = serialize($test);
        $paypal = new PayPal;
        $paypal->updateShippingAmt('ectoken', '1.00');
        $updated = unserialize($_SESSION['ectoken']);

        $this->assertArrayHasKey('PAYMENTREQUEST_0_AMT', $updated);
        $this->assertArrayHasKey('PAYMENTREQUEST_0_SHIPPINGAMT', $updated);
        $this->assertEquals($updated['PAYMENTREQUEST_0_AMT'], '11.00');
        $this->assertEquals($updated['PAYMENTREQUEST_0_SHIPPINGAMT'], '1.00');

        $test = array('PAYMENTREQUEST_0_AMT' => '15.00');

        $_SESSION['ectoken'] = serialize($test);
        $paypal = new PayPal;
        $paypal->updateShippingAmt('ectoken', '5.00');
        $updated = unserialize($_SESSION['ectoken']);

        $this->assertArrayHasKey('PAYMENTREQUEST_0_AMT', $updated);
        $this->assertArrayHasKey('PAYMENTREQUEST_0_SHIPPINGAMT', $updated);
        $this->assertEquals($updated['PAYMENTREQUEST_0_AMT'], '20.00');
        $this->assertEquals($updated['PAYMENTREQUEST_0_SHIPPINGAMT'], '5.00');

    }


    public function testsetTaxAmt()
    {
        $paypal = new PayPal;
        $paypal->setTaxAmt('1.00');

        $this->assertEquals('1.00', $paypal->taxamt);
    }

    public function testupdateTaxAmt()
    {

        $test = array('PAYMENTREQUEST_0_AMT' => '100.00',
            'PAYMENTREQUEST_0_TAXAMT' => '19.00');

        $_SESSION['ectoken'] = serialize($test);
        $paypal = new PayPal;
        $paypal->updateTaxAmt('ectoken', '20.00');
        $updated = unserialize($_SESSION['ectoken']);

        $this->assertArrayHasKey('PAYMENTREQUEST_0_AMT', $updated);
        $this->assertArrayHasKey('PAYMENTREQUEST_0_TAXAMT', $updated);
        $this->assertEquals($updated['PAYMENTREQUEST_0_AMT'], '101.00');
        $this->assertEquals($updated['PAYMENTREQUEST_0_TAXAMT'], '20.00');

        $test = array('PAYMENTREQUEST_0_AMT' => '15.00');

        $_SESSION['ectoken'] = serialize($test);
        $paypal = new PayPal;
        $paypal->updateTaxAmt('ectoken', '1.50');
        $updated = unserialize($_SESSION['ectoken']);

        $this->assertArrayHasKey('PAYMENTREQUEST_0_AMT', $updated);
        $this->assertArrayHasKey('PAYMENTREQUEST_0_TAXAMT', $updated);
        $this->assertEquals($updated['PAYMENTREQUEST_0_AMT'], '16.50');
        $this->assertEquals($updated['PAYMENTREQUEST_0_TAXAMT'], '1.50');

    }

}
