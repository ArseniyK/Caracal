<?php

/**
 * PayPay API helper class.
 *
 * Author: Mladen Mijatov
 */


final class PayPal_Helper {
	private static $api_username;
	private static $api_password;
	private static $api_signature;

	/**
	 * List of commands that can individually override
	 * global API_VERSION in name-value pairs (METHOD => VERSION).
	 *
	 * @var array
	 */
	private static $version_overrides = array();

	const API_LIVE_ENDPOINT = 'ssl://api-3t.paypal.com';
	const API_SANDBOX_ENDPOINT = 'ssl://api-3t.paypal.com';
	const API_VERSION = 109;

	const PAYPAL_LIVE_URL = 'https://www.paypal.com/cgi-bin/webscr';
	const PAYPAL_SANDBOX_URL = 'https://www.sandbox.paypal.com/webscr';

	const METHOD_AddressVerify = 'AddressVerify';
	const METHOD_BAUpdate = 'BAUpdate';
	const METHOD_BillOutstandingAmount = 'BillOutstandingAmount';
	const METHOD_Callback = 'Callback';
	const METHOD_CreateBillingAgreement = 'CreateBillingAgreement';
	const METHOD_CreateRecurringPaymentsProfile = 'CreateRecurringPaymentsProfile';
	const METHOD_DoAuthorization = 'DoAuthorization';
	const METHOD_DoCapture = 'DoCapture';
	const METHOD_DoDirectPayment = 'DoDirectPayment';
	const METHOD_DoExpressCheckoutPayment = 'DoExpressCheckoutPayment';
	const METHOD_DoNonReferencedCredit = 'DoNonReferencedCredit';
	const METHOD_DoReauthorization = 'DoReauthorization';
	const METHOD_DoReferenceTransaction = 'DoReferenceTransaction';
	const METHOD_DoVoid = 'DoVoid';
	const METHOD_GetBalance = 'GetBalance';
	const METHOD_GetBillingAgreementCustomerDetails = 'GetBillingAgreementCustomerDetails';
	const METHOD_GetExpressCheckoutDetails = 'GetExpressCheckoutDetails';
	const METHOD_GetPalDetails = 'GetPalDetails';
	const METHOD_GetRecurringPaymentsProfileDetails = 'GetRecurringPaymentsProfileDetails';
	const METHOD_GetTransactionDetails = 'GetTransactionDetails';
	const METHOD_ManagePendingTransactionStatus = 'ManagePendingTransactionStatus';
	const METHOD_ManageRecurringPaymentsProfileStatus = 'ManageRecurringPaymentsProfileStatus';
	const METHOD_MassPay = 'MassPay';
	const METHOD_RefundTransaction = 'RefundTransaction';
	const METHOD_SetCustomerBillingAgreement = 'SetCustomerBillingAgreement';
	const METHOD_SetExpressCheckout = 'SetExpressCheckout';
	const METHOD_TransactionSearch = 'TransactionSearch';
	const METHOD_UpdateAuthorization = 'UpdateAuthorization';
	const METHOD_UpdateRecurringPaymentsProfile = 'UpdateRecurringPaymentsProfile';

	const COMMAND_ExpressCheckout = '_express-checkout';

	private static $sandbox = false;

	/**
	 * Make class operate on sandbox version of API.
	 *
	 * @param boolean $sandbox
	 */
	public static function setSandbox($sandbox) {
		self::$sandbox = $sandbox;
	}

	/**
	 * Get string name-value pair string from associative array.
	 *
	 * @param array $params
	 * @return string
	 */
	public static function getNameValuePairs($params) {
		$result = '';

		// create data array
		$data = array();
		foreach($params as $key => $value)
			$data[] = $key.'='.urlencode($value);

		// join array elements
		$result = implode('&', $data);

		return $result;
	}

	/**
	 * Get associative array from name-value pair string.
	 *
	 * @param string $content
	 * @return array
	 */
	public static function getArrayFromNVP($content) {
		parse_str($content, $result);
		return $result;
	}

	/**
	 * Perform API call to PayPal using API signature and speicified
	 * parameters. Returns associative array containing response from
	 * the server.
	 *
	 * @param string $method
	 * @param array $params
	 * @return array
	 */
	public static function callAPI($method, $params) {
		// prepare data
		$endpoint_url = self::$sandbox ? self::API_SANDBOX_ENDPOINT : self::API_LIVE_ENDPOINT;

		if (isset(self::$version_overrides[$method]))
			$method_api_version = self::$version_overrides[$method]; else
			$method_api_version = self::API_VERSION;

		$required_params = array(
				'USER'				=> self::$api_username,
				'PWD'				=> self::$api_password,
				'SIGNATURE'			=> self::$api_signature,
				'METHOD'			=> $method,
				'VERSION'			=> $method_api_version,
				'CALLBACKVERSION'	=> self::API_VERSION
			);

		$final_params = array_merge($required_params, $params);
		$nvp_string = self::getNameValuePairs($final_params);

		// make the call
		$header = 'POST /nvp HTTP/1.0\n';
		$header .= 'Content-Type: application/x-www-form-urlencoded\n';
		$header .= 'Content-Length: ' . strlen($nvp_string) . '\n\n';

		$socket = fsockopen($endpoint_url, 443, $error_number, $error_string, 30);

		if ($socket) {
			fputs($socket, $header.$nvp_string);
			$raw_result = fgets($socket);
		}

		// parse the result
		$result = self::getArrayFromNVP($raw_result);

		return $result;
	}

	/**
	 * Method used during express checkout to redirect user to
	 * PayPal web interface with specified command and token.
	 * 
	 * @param string $command
	 * @param string $token
	 */
	public static function redirect($command, $token) {
		$url = self::$sandbox ? self::PAYPAL_SANDBOX_URL : self::PAYPAL_LIVE_URL;
	}
}

?>