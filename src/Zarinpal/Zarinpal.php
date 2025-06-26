<?php

namespace Mozakar\Gateway\Zarinpal;

use Illuminate\Support\Facades\Request;
use Mozakar\Gateway\Enum;
use Mozakar\Gateway\PortAbstract;
use Mozakar\Gateway\PortInterface;

class Zarinpal extends PortAbstract implements PortInterface
{

	/**
	 * Address of base url
	 *
	 * @var string
	 */
	protected $baseUrl = 'https://payment.zarinpal.com/pg/';

	/**
	 * Address of sandbox
	 *
	 * @var string
	 */
	protected $sandboxServer = 'https://sandbox.zarinpal.com/pg/';
	
	private const  REQUEST_PATH = 'v4/payment/request.json';
	private const  PAY_PATH = 'StartPay/';
	private const  VERIFY_PATH = 'v4/payment/verify.json';
    
	/**
	 * server url
	 *
	 * @var string
	 */
	protected $serverUrl = '';

	/**
	 * Payment Description
	 *
	 * @var string
	 */
	protected $description;

	/**
	 * Payer Email Address
	 *
	 * @var string
	 */
	protected $email;

	/**
	 * Payer Mobile Number
	 *
	 * @var string
	 */
	protected $mobileNumber;


	public function boot()
	{
		$this->setServer();
	}

	/**
	 * {@inheritdoc}
	 */
	public function set($amount)
	{
		$this->amount = ($amount / 10);

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function ready()
	{
		$this->sendPayRequest();

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function redirect()
	{
		return \Redirect::to($this->serverUrl . self::PAY_PATH . $this->refId);
	}

	/**
	 * {@inheritdoc}
	 */
	public function verify($transaction)
	{
		parent::verify($transaction);

		$this->userPayment();
		$this->verifyPayment();

		return $this;
	}

	/**
	 * Sets callback url
	 * @param $url
	 */
	function setCallback($url)
	{
		$this->callbackUrl = $url;
		return $this;
	}

	/**
	 * Gets callback url
	 * @return string
	 */
	function getCallback()
	{
		if (!$this->callbackUrl)
			$this->callbackUrl = $this->config->get('gateway.zarinpal.callback-url');

		return $this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]);
	}

	/**
	 * Send pay request to server
	 *
	 * @return void
	 *
	 * @throws ZarinpalException
	 */
	protected function sendPayRequest()
	{
		$this->newTransaction();

		$fields = [
			'merchant_id' => $this->config->get('gateway.zarinpal.merchant-id'),
			'amount' => $this->amount,
			'callback_url' => $this->getCallback(),
			'description' => $this->description ? $this->description : $this->config->get('gateway.zarinpal.description', ''),
			'metadata' => [
				'email' => $this->email ? $this->email :$this->config->get('gateway.zarinpal.email', ''),
				'mobile' => $this->mobileNumber ? $this->mobileNumber : $this->config->get('gateway.zarinpal.mobile', ''),
			]
		];

		try {
			$res = $this->curl_post($this->serverUrl . self::REQUEST_PATH, $fields);
			$res = json_decode($res, true);
			if (isset($res['data']['code'])) {
				if ($res['data']['code'] == 100) {
					$this->refId = $res['data']['authority'];
					$this->transactionSetRefId();
				} else {
					$this->transactionFailed();
					$this->newLog($res['data']['code'], ZarinpalException::$errors[$res['data']['code']]);
					throw new ZarinpalException($res['data']['code']);
				}
				
			} else {
				$this->transactionFailed();
				$this->newLog(-22, ZarinpalException::$errors[-22]);
				throw new ZarinpalException(-22);
			}
		} catch (\Exception $e) {
			$this->transactionFailed();
			$this->newLog(0, $e->getMessage());
			throw $e;
		}

	}

	/**
	 * Check user payment with GET data
	 *
	 * @return bool
	 *
	 * @throws ZarinpalException
	 */
	protected function userPayment()
	{
		$this->authority = Request::input('Authority');
		$status = Request::input('Status');

		if ($status == 'OK') {
			return true;
		}

		$this->transactionFailed();
		$this->newLog(-22, ZarinpalException::$errors[-22]);
		throw new ZarinpalException(-22);
	}

	/**
	 * Verify user payment from zarinpal server
	 *
	 * @return bool
	 *
	 * @throws ZarinpalException
	 */
	protected function verifyPayment()
	{

		$fields = [
			'merchant_id' => $this->config->get('gateway.zarinpal.merchant-id'),
			'authority' => $this->refId,
			'amount' => $this->amount,
		];

		$res = [];
		try {
			$response = $this->curl_post($this->serverUrl . self::VERIFY_PATH, $fields);
			$res = json_decode($response, true);
			$this->transactionSetData(['verify' => $res]);
			if (!isset($res['data']['code']) || ($res['data']['code'] != 100 && $res['data']['code'] != 101)) {
				$code = isset($res['data']['code']) ? $res['data']['code'] : -22;
				$this->transactionFailed();
				$this->newLog($code, ZarinpalException::$errors[$code]);
				throw new ZarinpalException($code);
			}

		} catch (\Exception $e) {
			$this->transactionFailed();
			$this->newLog(0, $e->getMessage());
			throw $e;
		}

		$this->trackingCode = $res['data']['ref_id'];
		$this->transactionSucceed();
		$this->newLog($res['data']['code'], Enum::TRANSACTION_SUCCEED_TEXT);
		return true;
	}

	/**
	 * Set server
	 *
	 * @return void
	 */
	protected function setServer()
	{
		$server = $this->config->get('gateway.zarinpal.server', 'iran');
		switch ($server) {
			case 'iran':
				$this->serverUrl = $this->baseUrl;
				break;
			case 'test':
				$this->serverUrl = $this->sandboxServer;
				break;
			default:
				$this->serverUrl = $this->baseUrl;
				break;
		}
	}


	/**
	 * Set Description
	 *
	 * @param $description
	 * @return void
	 */
	public function setDescription($description)
	{
		$this->description = $description;
	}

	/**
	 * Set Payer Email Address
	 *
	 * @param $email
	 * @return void
	 */
	public function setEmail($email)
	{
		$this->email = $email;
	}

	/**
	 * Set Payer Mobile Number
	 *
	 * @param $number
	 * @return void
	 */
	public function setMobileNumber($number)
	{
		$this->mobileNumber = $number;
	}

	public function curl_post($url, $params)
    {
        $headers = [
					'Content-Type: application/json',
					'Accept: application/json'
				];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $res = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if($info["http_code"] == 200 || $info["http_code"] == "200")
            return $res;

        $result = json_decode($res);
		
		if (isset($result['data']['code'])) {
			throw new ZarinpalException($result['data']['code']);
		}

		throw new ZarinpalException(0);
    }
}
