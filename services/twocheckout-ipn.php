<?php
	//set this in your wp-config.php for debugging
	//define('PMPRO_IPN_DEBUG', true);

	//in case the file is loaded directly
	if(!defined("ABSPATH"))
	{
		global $isapage;
		$isapage = true;

		define('WP_USE_THEMES', false);
		require_once(dirname(__FILE__) . '/../../../../wp-load.php');
	}

	// Require TwoCheckout class
	if(!class_exists("Twocheckout"))
		require_once(PMPRO_DIR . "/includes/lib/Twocheckout/Twocheckout.php");

	//some globals
	global $wpdb, $gateway_environment, $logstr;
	$logstr = "";	//will put debug info here and write to ipnlog.txt

	//validate?
  if( ! pmpro_twocheckoutIPNValidate() )
  {

		echo("!!FAILED VALIDATION!!)");

		//validation failed
		twocheckout_ipnExit();
  }

  // set up some variables
  $order_status = $_REQUEST['ORDER_STATUS'];
  $order_info = isset($_REQUEST['IPN_PURCHASE_ORDER_INFO']) ? $_REQUEST['IPN_PURCHASE_ORDER_INFO'] : '';
  $morder = new MemberOrder();
  $morder->getMemberOrderByID( $_REQUEST['REFNOEXT'] );

  if( empty( $morder->id ) )
  {
    error_log("Couldn't find order # " . $_REQUEST['REFNOEXT'] );
    twocheckout_ipnExit();
  }

  // order pending
  if( in_array( $order_status, array('PENDING', 'PURCHASE_PENDING', 'PENDING_APPROVAL') ) )
  {
    // order is pending, save some details
    $morder->status = 'pending';
    $morder->saveOrder();
  }

  // order complete
  if( $order_status == 'COMPLETE' )
  {
    // is it a chargeback?
    if( !empty($_REQUEST['CHARGEBACK_RESOLUTION']) || !empty($_REQUEST['CHARGEBACK_RESOLUTION']) )
    {
      /* .... */
    }
    else
    {
      // order was successful, save it and activate their membership
      /* .... */
    }
  }

  // order cancelled - note the single "L"
  if( $order_status == 'CANCELED' )
  {
    // did we cancel it?
    if( $order_info == 'CANCELED_API' )
    {
      error_log( "2checkout cancellation notification for order #" . $order->id );
      twocheckout_ipnExit();
    }
    else
    {
      error_log( "2checkout cancelled order #" . $order->id . " order status: "  . $order_info);
      //cancel their membership
      /* .... */
    }
    
  }

  twocheckout_ipnExit();
  
  function pmpro_twocheckoutIPNValidate()
  {

    $secret_key = pmpro_getOption("twocheckout_ipnsecretkey");
    
    // parameters to calculate the MD5 hash for response
    $IPN_parameters = array(
      'GIFT_ORDER' => '',
      'SALEDATE' => '',
      'PAYMENTDATE' => '',
      'REFNO'    => '',
      'REFNOEXT' => '',
      'ORIGINAL_REFNOEXT' => array(),
      'SHOPPER_REFERENCE_NUMBER' => '',
      'ORDERNO'  => '',
      'ORDERSTATUS' => '',
      'PAYMETHOD' => '',
      'PAYMETHOD_CODE' => '',
      'FIRSTNAME' => '',
      'LASTNAME' => '',
      'COMPANY' => '',
      'REGISTRATIONNUMBER' => '',
      'FISCALCODE' => '',
      'TAX_OFFICE' => '',
      'CBANKNAME' => '',
      'CBANKACCOUNT' => '',
      'ADDRESS1' => '',
      'ADDRESS2' => '',
      'CITY'  => '',
      'STATE'  => '',
      'ZIPCODE' => '',
      'COUNTRY' => '',
      'COUNTRY_CODE' => '',
      'PHONE' => '',
      'FAX' => '',
      'CUSTOMEREMAIL' => '',
      'FIRSTNAME_D' => '',
      'LASTNAME_D' => '',
      'COMPANY_D' => '',
      'ADDRESS1_D' => '',
      'ADDRESS2_D' => '',
      'CITY_D' => '',
      'STATE_D' => '',
      'ZIPCODE_D' => '',
      'COUNTRY_D' => '',
      'COUNTRY_D_CODE' => '',
      'PHONE_D' => '',
      'EMAIL_D' => '',
      'IPADDRESS' => '',
      'IPCOUNTRY' => '',
      'COMPLETE_DATE' => '',
      'TIMEZONE_OFFSET' => '',
      'CURRENCY' => '',
      'LANGUAGE' => '',
      'ORDERFLOW' => '',
      'IPN_PID' => array(),
      'IPN_PNAME' => array(),
      'IPN_PCODE' => array(),
      'IPN_EXTERNAL_REFERENCE' => array(),
      'IPN_INFO' => array(),
      'IPN_QTY' => array(),
      'IPN_PRICE' => array(),
      'IPN_VAT' => array(),
      'IPN_VAT_RATE' => array(),
      'IPN_VER' => array(),
      'IPN_DISCOUNT' => array(),
      'IPN_PROMONAME' => array(),
      'IPN_PROMOCODE' => array(),
      'IPN_ORDER_COSTS' => array(),
      'IPN_SKU' => array(),
      'IPN_PARTNER_CODE' => '',
      'IPN_PGROUP' => array(),
      'IPN_PGROUP_NAME' => array(),
      'IPN_LICENSE_PROD' => array(),
      'IPN_LICENSE_TYPE' => array(),
      'IPN_LICENSE_REF' => array(),
      'IPN_LICENSE_EXP' => array(),
      'IPN_LICENSE_START' => array(),
      'IPN_LICENSE_LIFETIME' => array(),
      'IPN_LICENSE_ADDITIONAL_INFO' => array(),
      'IPN_DELIVEREDCODES' => array(),
      'IPN_DOWNLOAD_LINK' => '',
      'IPN_TOTAL' => array(),
      'IPN_TOTALGENERAL' => '',
      'IPN_SHIPPING' => '',
      'IPN_SHIPPING_TAX' => '',
      'AVANGATE_CUSTOMER_REFERENCE' => '',
      'EXTERNAL_CUSTOMER_REFERENCE' => '',
      'IPN_PARTNER_MARGIN_PERCENT' => '',
      'IPN_PARTNER_MARGIN' => '',
      'IPN_EXTRA_MARGIN' => '',
      'IPN_EXTRA_DISCOUNT' => '',
      'IPN_COUPON_DISCOUNT' => '',
      'IPN_ORIGINAL_LINK_SOURCE' => array(),
      'IPN_COMMISSION' => '',
      'REFUND_TYPE' => '',
      'CHARGEBACK_RESOLUTION' => '',
      'CHARGEBACK_REASON_CODE' => '',
      'TEST_ORDER' => '',
      'IPN_ORDER_ORIGIN' => '',
      'FRAUD_STATUS' => '',
      'CARD_TYPE' => '',
      'CARD_LAST_DIGITS' => '',
      'CARD_EXPIRATION_DATE' => '',
      'GATEWAY_RESPONSE' => '',
      'IPN_DATE' => '',
      'FX_RATE' => '',
      'FX_MARKUP' => '',
      'PAYABLE_AMOUNT' => '',
      'PAYOUT_CURRENCY' => '',
    );

    foreach( $IPN_parameters as $key => $val )
    {
      if( is_array( $val ) && isset( $_REQUEST[$key][0] ) )
      {
        $IPN_parameters[$key][0] = $_REQUEST[$key][0];
      }
      elseif( isset( $_REQUEST[$key] ) )
      {
        $IPN_parameters[$key] = $_REQUEST[$key];
      }
      else
      {
        //exit if the request is missing fields
        return false;
      }
    }

    //*********Base string for HMAC_MD5 calculation:*********
    $result = '';
    foreach ($IPN_parameters as $key => $val){
        $result .= ArrayExpand( (array)$val );
    }

    //*********Calculated HMAC_MD5 signature:*********
    $hash =  hmac( $secret_key, $result );
    
    //if the calculated hash isn't the same as the hash on the request, exit
    if( $hash != $_REQUEST['HASH'] )
      return false;

    $IPN_parameters_response = array();
    $IPN_parameters_response['IPN_PID'] = $IPN_parameters['IPN_PID'];
    $IPN_parameters_response['IPN_PNAME'] = $IPN_parameters['IPN_PNAME'];
    $IPN_parameters_response['IPN_DATE'] = $IPN_parameters['IPN_DATE'];
    $IPN_parameters_response['DATE'] = $IPN_parameters['IPN_DATE'];

    //*********Response base string for HMAC_MD5 calculation:*********
    $result_response = '';
    foreach ($IPN_parameters_response as $key => $val){
        $result_response .= ArrayExpand((array)$val);
    }

    //*********Calculated response HMAC_MD5 signature:*********
    $hash =  hmac($secret_key, $result_response);
    $link_params['HASH']=$hash;

    // build the response
    $response = "<EPAYMENT>" . htmlspecialchars($IPN_parameters_response['DATE'], ENT_QUOTES, 'UTF-8') ."|";
    $response .= htmlspecialchars($hash, ENT_QUOTES, 'UTF-8') . "</EPAYMENT>";

    // send response to 2checkout so the IPN message is considered delivered
    echo $response;
    return true;
  }

  //*********FUNCTIONS FOR HMAC*********
  function ArrayExpand($array){
    $retval = "";
                foreach($array as $i => $value){
                                if(is_array($value)){
                                                $retval .= ArrayExpand($value);
                                }
                                else{
                                                $size        = strlen($value);
                                                $retval    .= $size.$value;
                                }
                }    
    return $retval;
  }
  
  function hmac ($key, $data){
    $b = 64; // byte length for md5
    if (strlen($key) > $b) {
        $key = pack("H*",md5($key));
    }
    $key  = str_pad($key, $b, chr(0x00));
    $ipad = str_pad('', $b, chr(0x36));
    $opad = str_pad('', $b, chr(0x5c));
    $k_ipad = $key ^ $ipad ;
    $k_opad = $key ^ $opad;
    return md5($k_opad  . pack("H*",md5($k_ipad . $data)));
  }

  function twocheckout_ipnExit()
  {
    error_log( 'exiting twocheckout IPN script...' );
    exit;
  }

  function twocheckout_getOrderDate( $date_string )
  {
    return $date_string;
  }
