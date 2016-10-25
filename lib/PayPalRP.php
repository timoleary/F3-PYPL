<?php

//! PayPal Recurring Payments via Classic API
class PayPalRP extends PayPal
{

    public $profiledetails = array();

    /**
     *    Class constructor
     *    Calls parent class constructor where PP config is set at instantiation.
     */
    function __construct($options=null)
    {
        parent::__construct($options);
    }

    /**
     * Setup Recurring Payment Billing Agreement.
     * @param  $description string
     * @param  $additional array
     * @return array
     */
    function setupRP($description, $additional = NULL)
    {
        $nvp = array();
        $nvp['RETURNURL'] = $this->returnurl;
        $nvp['CANCELURL'] = $this->cancelurl;
        $nvp['L_BILLINGTYPE0'] = 'RecurringPayments';
        $nvp['L_BILLINGAGREEMENTDESCRIPTION0'] = $description;

        if (isset($additional)) {
            $nvp = array_merge($nvp, $additional);
        }

        $setec = $this->apireq('SetExpressCheckout', $nvp);
        // store for reuse
        unset($nvp['RETURNURL'], $nvp['CANCELURL'], $nvp['L_BILLINGTYPE0'], $nvp['L_BILLINGAGREEMENTDESCRIPTION0']);
        $nvp['DESC'] = $description;

        $_SESSION[$setec['TOKEN']] = serialize($nvp);

        $setec['redirect'] = $this->redirect . $setec['TOKEN'];
        return $setec;
    }

    /**
     * Setup Recurring Payments Profile Details.
     * @param  $amt string
     * @param  $startdate string
     * @param  $period string
     * @param  $frequency int
     * @param  $currency string
     * @param  $additional array
     */
    function setRPDetails($amt, $startdate, $period, $frequency, $currency, $additional = NULL)
    {
        $this->profiledetails['AMT'] = $amt;
        $this->profiledetails['PROFILESTARTDATE'] = $startdate;
        $this->profiledetails['BILLINGPERIOD'] = $period;
        $this->profiledetails['BILLINGFREQUENCY'] = $frequency;
        $this->profiledetails['CURRENCYCODE'] = $currency;

        if (isset($additional)) {
            $this->profiledetails = array_merge($this->profiledetails, $additional);
        }
    }

    /**
     * Create Recurring Payment Profile.
     * @param  $token string
     * @param  $additional array
     * @return array
     */
    function createRP($token)
    {
        $nvp = unserialize($_SESSION[$token]);
        $nvp['TOKEN'] = $token;

        if (isset($this->profiledetails)) {
            $nvp = array_merge($nvp, $this->profiledetails);
        }

        $createrpp = $this->apireq('CreateRecurringPaymentsProfile', $nvp);
        return $createrpp;
    }

}
