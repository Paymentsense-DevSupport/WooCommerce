<?php

/*
Plugin Name: WooCommerce Paymentsense Gateway
Plugin URI: http://developers.paymentsense.co.uk/woocommerce/
Description: Extends WooCommerce. Provides a Paymentsense gateway for WooCommerce.
Version: 2.2.8
Author: Paymentsense
Author URI: http://www.paymentsense.co.uk
Last Modified: 25/03/2014
*/

//  Copyright 2014  Paymentsense  (email : devsupport@paymentsense.com)

add_action('plugins_loaded', 'init_paymentsense', 0);

function init_paymentsense()
{
	class WC_Gateway_Paymentsense extends WC_Payment_Gateway
	{
		var $notify_url;
		
		public function __construct()
		{
			$this->id							= 	'paymentsense';
			
			$this->has_fields					= 	false;
			$this->method_title					= 	__( 'Paymentsense', 'woocommerce' );
			$this->description					= 	'Process Payment via Paymentsense Gateway';
			
			$this->init_form_fields();
			$this->init_settings();
			
			$this->enabled						= 	$this->get_option('enabled');
			$this->title						= 	$this->get_option('title');
			$this->description					= 	$this->get_option('description');
			$this->order_description_prefix		= 	$this->get_option('order_description_prefix');
			
			$this->gateway_merchant_id			= 	$this->get_option('gateway_merchant_id');
			$this->gateway_password				= 	$this->get_option('gateway_password');
			$this->gateway_presharedkey			= 	$this->get_option('gateway_presharedkey');
			
			$this->email_address_editable		= 	$this->get_option('email_address_editable');
			$this->phone_number_editable		= 	$this->get_option('phone_number_editable');
			
			$this->cv2_mandatory 				= 	'true';
			$this->address1_mandatory			= 	$this->get_option('address1_mandatory');
			$this->city_mandatory				= 	$this->get_option('city_mandatory');
			$this->state_mandatory				= 	$this->get_option('state_mandatory');
			$this->postcode_mandatory			= 	$this->get_option('postcode_mandatory');
			$this->country_mandatory			= 	$this->get_option('country_mandatory' );
			$this->amex_accepted = ($this->settings['amex_accepted'] == "yes" ? "TRUE" : "FALSE");
			
			$this->liveurl						= 	'https://mms.paymentsensegateway.com/Pages/PublicPages/PaymentForm.aspx';
			$this->notify_url					= 	esc_url(add_query_arg( 'wc-api', 'WC_Gateway_Paymentsense', home_url( '/' )));
			
			if ( "TRUE" == $this->amex_accepted )
			{
			    $this->icon = apply_filters('woocommerce_paymentsense_direct_icon', plugins_url('images/paymentsense-logos-with-amex.png', __FILE__));
			}
			else
			{
			    $this->icon = apply_filters('woocommerce_paymentsense_direct_icon', plugins_url('images/paymentsense-logos-no-amex.png', __FILE__ ));
			}
			
			add_action('valid-paymentsense-standard-request', array($this, 'successful_request'));
			add_action('woocommerce_receipt_paymentsense', array($this, 'receipt_page'));
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
			
			// Payment listener/API hook
			//die('test');
			add_action('woocommerce_api_wc_gateway_paymentsense', array($this, 'check_paymentsense_response'));
		}
		
		public function init_form_fields()
		{
			$this->form_fields = array(
				'enabled' => array(
					'title' 		=>		__( 'Enable/Disable:', 'woocommerce' ),
					'type' 			=>		'checkbox',
					'label'			=>		__( 'Enable Card Payment', 'woocommerce' ),
					'default' 		=> 		'yes'
				),
				
				'module_options' => array(
					'title' 		=> 		__( 'Module Options', 'woocommerce' ),
					'type' 			=> 		'title',
					'description' 			=> 		__('The following options affect how the Paymentsense Module is displayed on the frontend.', 'woocommerce')
				),
				
				'title' => array(
					'title' 		=> 		__( 'Title:', 'woocommerce' ),
					'type' 			=> 		'text',
					'description' 	=> 		__( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
					'default' 		=> 		__( 'Card Payment', 'woocommerce' ),
					'desc_tip'      => 		true 
				),
						
				'description' => array(
					'title' 		=> 		__( 'Message to Customer:', 'woocommerce' ),
					'type' 			=> 		'text',
					'default' 		=> 		''
				),
					
				'order_description_prefix' => array(
					'title'       	=> 		__( 'Order Description Prefix', 'woocommerce' ),
					'type'        	=> 		'text',
					'description' 	=> 		__( 'Please enter a prefix for Order Descriptions.', 'woocommerce' ),
					'default'     	=> 		'WC-',
					'desc_tip'    	=> 		true
				),

				'gateway_details' => array(
					'title' 		=> 		__( 'Gateway Details', 'woocommerce' ),
					'type' 			=> 		'title',
					'description' 			=> 		__('These are the gateway deatils to allow you to connect with the Paymentsense gateway. (These are not the details used to login to the MMS.)', 'woocommerce')
				),
					
				'gateway_merchant_id' => array(
					'title' 		=> 		__( 'Gateway MerchantID:', 'woocommerce' ),
					'type' 			=> 		'text',
					'description' 	=> 		__( 'This is the gateway MerchantID not used with the MMS login. The Format should match the following ABCDEF-1234567', 'woocommerce' ),
					'default' 		=> 		__( '', 'woocommerce' ),
					'desc_tip'      => 		true
				),
						
				'gateway_password' => array(
					'title' 		=> 		__( 'Gateway Password:', 'woocommerce' ),
					'type' 			=> 		'text',
					'description' 	=> 		__( 'This is the gateway Password not used with the MMS login. The Password should use lower case and uppercase letters, and numbers only.', 'woocommerce' ),
					'default' 		=> 		__( '', 'woocommerce' ),
					'desc_tip'      => 		true
				),
						
				'gateway_presharedkey' => array(
					'title' 		=> 		__( 'Gateway PreSharedKey:', 'woocommerce' ),
					'type' 			=> 		'text',
					'description' 	=> 		__( 'This is located within the MMS under "Account Admin Settings" > "Account Settings".', 'woocommerce' ),
					'default'		=> 		__( '', 'woocommerce' ),
					'desc_tip'      => 		true
				),
					
				'hosted_payment_form_additional_field' => array(
					'title' 		=> 		__( 'Payment Form Additional Field', 'woocommerce' ),
					'type' 			=> 		'title',
					'description' 			=> 		__('These options allow the customer to change the email address and phone number on the payment form.', 'woocommerce')
				),
					
				'email_address_editable' => array(
					'title' 		=> 		__( 'Email Address can be altered on payment form:', 'woocommerce' ),
					'type' 			=> 		'select',
					'description' 	=> 		__( 'This option allows the customer to change the email address that entered during checkout. By default the Paymentsense module will pass the customers email address that they entered during checkout.', 'woocommerce' ),
					'default' 		=> 		'false',
					'desc_tip'      => 		true,
					'options' 		=> 		array(
							'true' 		=> 		__( 'Yes', 'woocommerce' ),
							'false'		=> 		__( 'No', 'woocommerce' )
					)
				),
									
				'phone_number_editable' => array(
					'title' 		=> 		__( 'Phone Number can be altered on payment form:', 'woocommerce' ),
					'type' 			=> 		'select',
					'description' 	=> 		__( 'This option allows the customer to change the phone number that entered during checkout. By default the Paymentsense module will pass the customers phone number that they entered during checkout.', 'woocommerce' ),
					'default' 		=> 		'false',
					'desc_tip'      =>	 	true,
					'options' 		=> 		array(
							'true' 		=> 		__( 'Yes', 'woocommerce' ),
							'false'    	=> 		__( 'No', 'woocommerce' )
					)
				),
					
				'hosted_payment_form_mandatory_field' => array(
					'title' 		=> 		__( 'Payment Form Mandatory Fields', 'woocommerce' ),
					'type' 			=> 		'title',
					'description' 			=> 		__('These options allow you to change what fields are mandatory for the customers to complete on the payment form. (The default settings are recommended by Paymentsense)', 'woocommerce')
				),
					
				'address1_mandatory' => array(
					'title' 		=> 		__( 'Address Line 1 Mandatory:', 'woocommerce' ),
					'type' 			=> 		'select',
					'description' 	=> 		__( 'Define the Address Line 1 as a Mandatory field on the Payment form. This is used for the Address Verification System (AVS) check on the customers card. Recommended Setting "Yes".', 'woocommerce' ),
					'default' 		=> 		'true',
					'desc_tip'      => 		true,
					'options' 		=> 		array(
							'true' 		=> 		__( 'Yes', 'woocommerce' ),
							'false'    	=> 		__( 'No', 'woocommerce' )
					)
				),
					
				'city_mandatory' => array(
					'title' 		=> 		__( 'City Mandatory:', 'woocommerce' ),
					'type' 			=> 		'select',
					'description' 	=> 		__( 'Define the City as a Mandatory field on the Payment form.', 'woocommerce' ),
					'default' 		=> 		'false',
					'desc_tip'      => 		true,
					'options' 		=> 		array(
							'true' 		=> 		__( 'Yes', 'woocommerce' ),
							'false'		=> 		__( 'No', 'woocommerce' )
					)
				),
					
				'state_mandatory' => array(
					'title' 		=> 		__( 'State/County Mandatory:', 'woocommerce' ),
					'type' 			=> 		'select',
					'description' 	=> 		__( 'Define the State/County as a Mandatory field on the Payment form.', 'woocommerce' ),
					'default' 		=> 		'false',
					'desc_tip'      => 		true,
					'options' 		=> 		array(
							'true' 		=> 		__( 'Yes', 'woocommerce' ),
							'false'    	=> 		__( 'No', 'woocommerce' )
					)
				),
					
				'postcode_mandatory' => array(
					'title' 		=> 		__( 'Post Code Mandatory:', 'woocommerce' ),
					'type' 			=> 		'select',
					'description' 	=> 		__( 'Define the Post Code as a Mandatory field on the Payment form. This is used for the Address Verification System (AVS) check on the customers card. Recommended Setting "Yes".', 'woocommerce' ),
					'default' 		=> 		'true',
					'desc_tip'      => 		true,
					'options' 		=> 		array(
							'true' 		=> 		__( 'Yes', 'woocommerce' ),
							'false'    	=> 		__( 'No', 'woocommerce' )
					)
				),
				
				'country_mandatory' => array(
					'title' 		=> 		__( 'Country Mandatory:', 'woocommerce' ),
					'type' 			=> 		'select',
					'description' 	=> 		__( 'Define the Country as a Mandatory field on the Payment form.', 'woocommerce' ),
					'default' 		=> 		'false',
					'desc_tip'      => 		true,
					'options' 		=> 		array(
							'true' 		=> 		__( 'Yes', 'woocommerce' ),
							'false'    	=> 		__( 'No', 'woocommerce' )
					)
				),
			    'amex_accepted' => array(
			        'title' => __( 'Accept American Express?', 'woothemes' ),
			        'type' => 'checkbox',
			        'label' => __( 'Only tick if you have an American Express MID associated with your Paymentsense gateway account.', 'woothemes' ),
			        'default' => 'no'
			    ),
			);
		}		
		
		function admin_options()
		{
			?>
				 <h2><?php _e('Paymentsense','woocommerce'); ?></h2>
				 <table class="form-table">
				 <?php $this->generate_settings_html(); ?>
				 </table> <?php
		}
		
		function get_paymentsense_args($order)
		{
			
			switch (get_woocommerce_currency())
			{
				case GBP:
					$wcCurrencyCode = 826;
				break;
				case USD:
					$wcCurrencyCode = 840;
					break;
				case EUR:
					$wcCurrencyCode = 978;
					break;
				
			}
			
			$countriesArray = array(
					'AL' => '8',
					'DZ' => '12',
					'AS' => '16',
					'AD' => '20',
					'AO' => '24',
					'AI' => '660',
					'AG' => '28',
					'AR' => '32',
					'AM' => '51',
					'AW' => '533',
					'AU' => '36',
					'AT' => '40',
					'AZ' => '31',
					'BS' => '44',
					'BH' => '48',
					'BD' => '50',
					'BB' => '52',
					'BY' => '112',
					'BE' => '56',
					'BZ' => '84',
					'BJ' => '204',
					'BM' => '60',
					'BT' => '64',
					'BO' => '68',
					'BA' => '70',
					'BW' => '72',
					'BR' => '76',
					'BN' => '96',
					'BG' => '100',
					'BF' => '854',
					'BI' => '108',
					'KH' => '116',
					'CM' => '120',
					'CA' => '124',
					'CV' => '132',
					'KY' => '136',
					'CF' => '140',
					'TD' => '148',
					'CL' => '152',
					'CN' => '156',
					'CO' => '170',
					'KM' => '174',
					'CG' => '178',
					'CD' => '180',
					'CK' => '184',
					'CR' => '188',
					'CI' => '384',
					'HR' => '191',
					'CU' => '192',
					'CY' => '196',
					'CZ' => '203',
					'DK' => '208',
					'DJ' => '262',
					'DM' => '212',
					'DO' => '214',
					'EC' => '218',
					'EG' => '818',
					'SV' => '222',
					'GQ' => '226',
					'ER' => '232',
					'EE' => '233',
					'ET' => '231',
					'FK' => '238',
					'FO' => '234',
					'FJ' => '242',
					'FI' => '246',
					'FR' => '250',
					'GF' => '254',
					'PF' => '258',
					'GA' => '266',
					'GM' => '270',
					'GE' => '268',
					'DE' => '276',
					'GH' => '288',
					'GI' => '292',
					'GR' => '300',
					'GL' => '304',
					'GD' => '308',
					'GP' => '312',
					'GU' => '316',
					'GT' => '320',
					'GN' => '324',
					'GW' => '624',
					'GY' => '328',
					'HT' => '332',
					'VA' => '336',
					'HN' => '340',
					'HK' => '344',
					'HU' => '348',
					'IS' => '352',
					'IN' => '356',
					'ID' => '360',
					'IR' => '364',
					'IQ' => '368',
					'IE' => '372',
					'IL' => '376',
					'IT' => '380',
					'JM' => '388',
					'JP' => '392',
					'JO' => '400',
					'KZ' => '398',
					'KE' => '404',
					'KI' => '296',
					'KP' => '408',
					'KR' => '410',
					'KW' => '414',
					'KG' => '417',
					'LA' => '418',
					'LV' => '428',
					'LB' => '422',
					'LS' => '426',
					'LR' => '430',
					'LY' => '434',
					'LI' => '438',
					'LT' => '440',
					'LU' => '442',
					'MO' => '446',
					'MK' => '807',
					'MG' => '450',
					'MW' => '454',
					'MY' => '458',
					'MV' => '462',
					'ML' => '466',
					'MT' => '470',
					'MH' => '584',
					'MQ' => '474',
					'MR' => '478',
					'MU' => '480',
					'MX' => '484',
					'FM' => '583',
					'MD' => '498',
					'MC' => '492',
					'MN' => '496',
					'MS' => '500',
					'MA' => '504',
					'MZ' => '508',
					'MM' => '104',
					'NA' => '516',
					'NR' => '520',
					'NP' => '524',
					'NL' => '528',
					'AN' => '530',
					'NC' => '540',
					'NZ' => '554',
					'NI' => '558',
					'NE' => '562',
					'NG' => '566',
					'NU' => '570',
					'NF' => '574',
					'MP' => '580',
					'NO' => '578',
					'OM' => '512',
					'PK' => '586',
					'PW' => '585',
					'PA' => '591',
					'PG' => '598',
					'PY' => '600',
					'PE' => '604',
					'PH' => '608',
					'PN' => '612',
					'PL' => '616',
					'PT' => '620',
					'PR' => '630',
					'QA' => '634',
					'RE' => '638',
					'RO' => '642',
					'RU' => '643',
					'RW' => '646',
					'SH' => '654',
					'KN' => '659',
					'LC' => '662',
					'PM' => '666',
					'VC' => '670',
					'WS' => '882',
					'SM' => '674',
					'ST' => '678',
					'SA' => '682',
					'SN' => '686',
					'SC' => '690',
					'SL' => '694',
					'SG' => '702',
					'SK' => '703',
					'SI' => '705',
					'SB' => '90',
					'SO' => '706',
					'ZA' => '710',
					'ES' => '724',
					'LK' => '144',
					'SD' => '736',
					'SR' => '740',
					'SJ' => '744',
					'SZ' => '748',
					'SE' => '752',
					'CH' => '756',
					'SY' => '760',
					'TW' => '158',
					'TJ' => '762',
					'TZ' => '834',
					'TH' => '764',
					'TG' => '768',
					'TK' => '772',
					'TO' => '776',
					'TT' => '780',
					'TN' => '788',
					'TR' => '792',
					'TM' => '795',
					'TC' => '796',
					'TV' => '798',
					'UG' => '800',
					'UA' => '804',
					'AE' => '784',
					'GB' => '826',
					'US' => '840',
					'UY' => '858',
					'UZ' => '860',
					'VU' => '548',
					'VE' => '862',
					'VN' => '704',
					'VG' => '92',
					'VI' => '850',
					'WF' => '876',
					'EH' => '732',
					'YE' => '887',
					'ZM' => '894',
					'ZW' => '716'
			);
				
			if (in_array($order->billing_country,array_keys($countriesArray))) 
			{
				$this->countryISO = $countriesArray[$order->billing_country];
			}
			else
			{
				$this->countryISO = "";
			}
			
			
			$MerchantID = $this->gateway_merchant_id;
			$Amount = number_format( $order->get_total(), 2, '.', '' )*100;
			$CurrencyCode = $wcCurrencyCode;
			$OrderID = ltrim( $order->get_order_number(), '#');
			$TransactionType = 'SALE';
			$TransactionDateTime = date('Y-m-d H:i:s P');
			$CallbackURL = $this->notify_url;
			$OrderDescription = $this->order_description_prefix . ltrim( $order->get_order_number(), '#');
			$CustomerName = $order->billing_first_name.' '.$order->billing_last_name;
			$Address1 = $order->billing_address_1;
			$Address2 = $order->billing_address_2;
			$Address3 = '';
			$Address4 = '';
			$City = $order->billing_city;
			$State = $order->billing_state;
			$PostCode = $order->billing_postcode;
			$CountryCode = $this->countryISO;
			$EmailAddress = $order->billing_email;
			$PhoneNumber = $order->billing_phone;
			$EmailAddressEditable = $this->email_address_editable;
			$PhoneNumberEditable = $this->phone_number_editable;
			$CV2Mandatory = $this->cv2_mandatory;
			$Address1Mandatory = $this->address1_mandatory;
			$CityMandatory = $this->city_mandatory;
			$StateMandatory = $this->state_mandatory;
			$PostCodeMandatory = $this->postcode_mandatory;
			$CountryMandatory = $this->country_mandatory;
			$ResultDeliveryMethod = 'POST';
			$ServerResultURL = '';
			$PaymentFormDisplaysResult = 'false';
			
			
			$str1  =  		'PreSharedKey='.$this->gateway_presharedkey;
			$str1 .= 		'&MerchantID='.$MerchantID;
			$str1 .= 		'&Password='.$this->gateway_password;
			$str1 .= 		'&Amount='.$Amount;
			$str1 .= 		'&CurrencyCode='.$CurrencyCode;
			$str1 .= 		'&OrderID='.$OrderID;
			$str1 .= 		'&TransactionType='.$TransactionType;
			$str1 .= 		'&TransactionDateTime='.$TransactionDateTime;
			$str1 .= 		'&CallbackURL='.$CallbackURL;
			$str1 .= 		'&OrderDescription='.$OrderDescription;
			$str1 .= 		'&CustomerName='.$CustomerName;
			$str1 .= 		'&Address1='.$Address1;
			$str1 .= 		'&Address2='.$Address2;
			$str1 .= 		'&Address3='.$Address3;
			$str1 .= 		'&Address4='.$Address4;
			$str1 .= 		'&City='.$City;
			$str1 .= 		'&State='.$State;
			$str1 .= 		'&PostCode='.$PostCode;
			$str1 .= 		'&CountryCode='.$CountryCode;
			$str1 .= 		'&EmailAddress='.$EmailAddress;
			$str1 .= 		'&PhoneNumber='.$PhoneNumber;
			$str1 .= 		'&EmailAddressEditable='.$EmailAddressEditable;
			$str1 .= 		'&PhoneNumberEditable='.$PhoneNumberEditable;
			$str1 .= 		'&CV2Mandatory='.$CV2Mandatory;
			$str1 .= 		'&Address1Mandatory='.$Address1Mandatory;
			$str1 .= 		'&CityMandatory='.$CityMandatory;
			$str1 .= 		'&PostCodeMandatory='.$PostCodeMandatory;
			$str1 .= 		'&StateMandatory='.$StateMandatory;
			$str1 .= 		'&CountryMandatory='.$CountryMandatory;
			$str1 .= 		'&ResultDeliveryMethod='.$ResultDeliveryMethod;
			$str1 .= 		'&ServerResultURL='.$ServerResultURL;
			$str1 .= 		'&PaymentFormDisplaysResult='.$PaymentFormDisplaysResult;
			
			$HashDigest = sha1($str1);
			
			$paymentsense_args = array(
							'HashDigest' => $HashDigest,
							'MerchantID' => $MerchantID,
							'Amount' => $Amount,
							'CurrencyCode' => $CurrencyCode,
							'OrderID' => $OrderID,
							'TransactionType' => $TransactionType,
							'TransactionDateTime' => $TransactionDateTime,
							'CallbackURL' => $CallbackURL,
							'OrderDescription' => $OrderDescription,
							'CustomerName' => $CustomerName,
							'Address1' => $Address1,
							'Address2' => $Address2,
							'Address3' => $Address3,
							'Address4' => $Address4,
							'City' => $City,
							'State' => $State,
							'PostCode' => $PostCode,
							'CountryCode' => $CountryCode,
							'EmailAddress' => $EmailAddress,
							'PhoneNumber' => $PhoneNumber,
							'EmailAddressEditable' => $EmailAddressEditable,
							'PhoneNumberEditable' => $PhoneNumberEditable,
							'CV2Mandatory' => $CV2Mandatory,
							'Address1Mandatory' => $Address1Mandatory,
							'CityMandatory' => $CityMandatory,
							'PostCodeMandatory' => $PostCodeMandatory,
							'StateMandatory' => $StateMandatory,
							'CountryMandatory' => $CountryMandatory,
							'ResultDeliveryMethod' => $ResultDeliveryMethod,
							'ServerResultURL' => $ServerResultURL,
							'PaymentFormDisplaysResult' => $PaymentFormDisplaysResult);	
		
			$paymentsense_args = apply_filters( 'woocommerce_paymentsense_args', $paymentsense_args );
			return $paymentsense_args;
		}
		
		function generate_paymentsense_form( $order_id )
		{
		
			$order = new WC_Order( $order_id );
			$paymentsense_adr = $this->liveurl . '?';
			
		
			$paymentsense_args = $this->get_paymentsense_args( $order );
		
			$paymentsense_args_array = array();
		
			foreach ( $paymentsense_args as $key => $value )
			{
				$paymentsense_args_array[] = '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
			}
			
			return '<p>
		              <img src="' . $this->logo . '" />
	                </p>
				<form action="' . $paymentsense_adr . '" method="post" id="paymentsense_payment_form" target="_top">
				' . implode( '', $paymentsense_args_array ) . '
				<!-- Button Fallback -->
				<div class="payment_buttons">
				    <input type="submit" class="button alt" id="submit_paymentsense_payment_form" value="' . __( 'Pay via Paymentsense', 'woocommerce' ) . '" /> <a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Cancel order &amp; restore cart', 'woocommerce' ) . '</a>
				</div>
				<script type="text/javascript">
                    jQuery(function(){
                    jQuery("body").block(
                            {
                                message: "' . esc_js( __( 'We are now redirecting you to Paymentsense to complete your payment.', 'woocommerce' ) ) . '",
                                    overlayCSS:
                            {
                                background: "#fff",
                                    opacity: 0.6
                        },
                        css: {
                            padding:        20,
                                textAlign:      "center",
                                color:          "#555",
                                border:         "3px solid #aaa",
                                backgroundColor:"#fff",
                                cursor:         "wait",
                                lineHeight:"32px"
                        }
                        });
                        jQuery("#submit_paymentsense_payment_form").click();});
			    </script>
			</form>';
		
		}
		
		function process_payment( $order_id ) 
		{
			$order = new WC_Order( $order_id );

			return array(
						'result' 	=> 'success',
						'redirect'	=> $order->get_checkout_payment_url( true )
				);
		}
		
		function receipt_page($order)
		{
			echo '<p>' . __( 'Thank you - your order is now pending payment. You should be automatically redirected to Paymentsense to make payment.', 'woocommerce' ) . '</p>';
		
			echo $this->generate_paymentsense_form( $order );
		}
				
		function check_paymentsense_response()
		{
			
			@ob_clean();

			$paymentsense_response = ! empty( $_POST ) ? $_POST : false;

			if ( $paymentsense_response) 
			{
				header( 'HTTP/1.1 200 OK' );
				
				do_action( "valid-paymentsense-standard-request", $paymentsense_response );

			}
			else 
			{

				wp_die( "Paymentsense Request Failure", "Paymentsense", array( 'response' => 200 ) );

			}
		}
		
		function successful_request($posted)
		{

			global $woocommerce;
			$order = new WC_Order( $_POST['OrderID'] );
			$posted = stripslashes_deep($posted);
			
			$return_string = 'PreSharedKey=' . $this->gateway_presharedkey;
			$return_string .= '&MerchantID=' . $this->gateway_merchant_id;
			$return_string .= '&Password=' . $this->gateway_password;
			$return_string .= '&StatusCode=' . $_POST['StatusCode'];
			$return_string .= '&Message=' . $_POST['Message'];
			$return_string .= '&PreviousStatusCode=' . $_POST['PreviousStatusCode'];
			$return_string .= '&PreviousMessage=' . $_POST['PreviousMessage'];
			$return_string .= '&CrossReference=' . $_POST['CrossReference'];
			$return_string .= '&Amount=' . $_POST['Amount'];
			$return_string .= '&CurrencyCode=' . $_POST['CurrencyCode'];
			$return_string .= '&OrderID=' . $_POST['OrderID'];
			$return_string .= '&TransactionType=' . $_POST['TransactionType'];
			$return_string .= '&TransactionDateTime=' . $_POST['TransactionDateTime'];
			$return_string .= '&OrderDescription=' . $_POST['OrderDescription'];
			$return_string .= '&CustomerName=' . $_POST['CustomerName'];
			$return_string .= '&Address1=' . $_POST['Address1'];
			$return_string .= '&Address2=' . $_POST['Address2'];
			$return_string .= '&Address3=' . $_POST['Address3'];
			$return_string .= '&Address4=' . $_POST['Address4'];
			$return_string .= '&City=' . $_POST['City'];
			$return_string .= '&State=' . $_POST['State'];
			$return_string .= '&PhoneNumber=' . $_POST['PhoneNumber'];
			
			$return_hash = sha1($return_string);
			if ($return_hash == $_POST['HashDigest'])
			{
				$hash_check = 'passed';
			}
			else 
			{
				$hash_check = 'failed';
			}
				
			switch ($_POST['StatusCode'])
			{
				case 0:
					$transaction_status = 'success';
					break;
				
				case 4:
					$transaction_status = 'failed';
					break;
				
				case 5:
					$transaction_status = 'failed';
					break;
				
				case 20:
					if ($_POST['PreviousStatusCode'] == 0)
					{
						$transaction_status = 'success';
					}
					else
					{
						$transaction_status = 'failed';
					}
					break;
					
				case 30:
					$transaction_status = 'failed';
					break;		
			}
			
			if($transaction_status == 'success')
			{
				$order->payment_complete();
				$order->add_order_note('Payment Successful: '.$_POST['Message'].'<br />',0);
				return (wp_redirect(wc_get_endpoint_url( 'order-received', $order->id, $order->get_checkout_order_received_url() )));
			}
			elseif ($transaction_status == 'failed')
			{
				$order->get_checkout_payment_url(false);
				$order->update_status('failed', sprintf( __( 'Payment Failed due to: %s .<br />', 'woocommerce' ), strtolower( $_POST['Message'] ) ));
				wc_add_notice(__('Payment Failed due to: ', 'woothemes') . $_POST['Message']. '<br /> Please check your card details and try again.', 'error');
				return (wp_redirect(wc_get_endpoint_url( 'order-received', $order->id, $order->get_checkout_payment_url(false)),200));
			}
		}
	}
}

function add_paymentsense($methods) 
{
	$methods[] = 'WC_Gateway_Paymentsense';
	return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_paymentsense');
?>