<?php
namespace Mozakar\Gateway;

use Carbon\Carbon;
use Mozakar\Gateway\Enum;
use Mozakar\Gateway\Currency;
use Illuminate\Support\Facades\Request;

abstract class PortAbstract
{
	/**
	 * request
	 *
	 * @var Request
	 */
	protected $request;

	/**
	 * Transaction id
	 *
	 * @var null|int
	 */
	protected $transactionId = null;

	/**
	 * Transaction row in database
	 */
	protected $transaction = null;

	/**
	 * Customer card number
	 *
	 * @var string
	 */
	protected $cardNumber = '';

	/**
	 * @var Config
	 */
	protected $config;

	/**
	 * Port id
	 *
	 * @var int
	 */
	protected $portName;

	/**
	 * Reference id
	 *
	 * @var string
	 */
	protected $refId;

	/**
	 * Amount in Rial
	 *
	 * @var int
	 */
	protected $amount;

	/**
	 * Description of transaction
	 *
	 * @var string
	 */
	protected $description;

	/**
	 * Custom Invoice Number of transaction
	 *
	 * @var string
	 */
	protected $customInvoiceNo;


	/**
	 * Custom factor Number of transaction
	 *
	 * @var string
	 */
	protected $factorNumber;

	/**
	 * Payer mobile number
	 *
	 * @var string
	 */
	protected $mobile;

	/**
	 * callback URL
	 *
	 * @var url
	 */
	protected $callbackUrl;

	/**
	 * Tracking code payment
	 *
	 * @var string
	 */
	protected $trackingCode;

	/**
	 * Order id
	 *
	 * @var mixed
	 */
	protected $orderId;

	/**
	 * redirect url
	 *
	 * @var string
	 */
	protected string $redirectUrl;

	/**
	 * Currency
	 *
	 * @var string
	 */
	protected string $currency = Currency::RIAL;

	/**
	 * useCache
	 *
	 * @var bool
	 */
	protected bool $useCache = false;
	/**
	 * Initialize of class
	 *
	 * @param Config $config
	 * @param DataBaseManager $db
	 * @param int $port
	 */
	function __construct()
	{
		$this->db = app('db');
		$this->request = app('request');
	}

	/** bootstraper */
	function boot(){

	}

	function setConfig($config)
	{
		$this->config = $config;
	}

	/**
	 * @return mixed
	 */
	function getTable()
	{
		return $this->db->table($this->config->get('gateway.table'));
	}

	/**
	 * @return mixed
	 */
	function getLogTable()
	{
		return $this->db->table($this->config->get('gateway.table') . '_logs');
	}

	/**
	 * get request
	 *
	 * @return Request
	 */
	function getRequest()
	{
		return $this->request;
	}

	/**
	 * Get port id, $this->port
	 *
	 * @return mixed
	 */
	function getPortName()
	{
		return $this->portName;
	}

	/**
	 * Get port id, $this->port
	 *
	 * @return void
	 */
	function setPortName($name)
	{
		$this->portName = $name;
	}

	/**
	 * Set custom description on current transaction
	 *
	 * @param string $description
	 *
	 * @return void
	 */
	function setCustomDesc ($description)
	{
		$this->description = $description;
	}

	/**
	 * Get custom description of current transaction
	 *
	 * @return string | null
	 */
	function getCustomDesc ()
	{
		return $this->description;
	}

	/**
	 * Set custom Invoice number on current transaction
	 *
	 * @param string $description
	 *
	 * @return $this
	 */
	function setCustomInvoiceNo ($invoiceNo)
	{
		$this->customInvoiceNo = $invoiceNo;
		return $this;
	}

	/**
	 * Get custom Invoice number of current transaction
	 *
	 * @return string | null
	 */
	function getCustomInvoiceNo ()
	{
		return $this->customInvoiceNo;
	}
	
	/**
	 * useCache
	 *
	 * @param  bool $use
	 * @return $this;
	 */
	function useCache (bool $use = true)
	{
		$this->useCache = $use;
		return $this;
	}

	/**
	 * Set custom factor on current transaction
	 *
	 * @param string $factorNumber
	 *
	 * @return $this
	 */
	function setFactorNumber ($factorNumber)
	{
		$this->factorNumber = $factorNumber;
		return $this;
	}

	/**
	 * Get custom factor of current transaction
	 *
	 * @return string | null
	 */
	function getFactorNumber ()
	{
		return $this->factorNumber;
	}


	/**
	 * Set mobile on current transaction
	 *
	 * @param string $mobile
	 *
	 * @return $this
	 */
	function setMobile ($mobile)
	{
		$this->mobile = $mobile;
		return $this;
	}

	/**
	 * Get mobile of current transaction
	 *
	 * @return string | null
	 */
	function getMobile ()
	{
		return $this->mobile;
	}


	/**
	 * Return card number
	 *
	 * @return string
	 */
	function cardNumber()
	{
		return $this->cardNumber;
	}

	/**
	 * Return tracking code
	 */
	function trackingCode()
	{
		return $this->trackingCode;
	}

	/**
	 * Get transaction id
	 *
	 * @return int|null
	 */
	function transactionId()
	{
		return $this->transactionId;
	}

	/**
	 * Return reference id
	 */
	function refId()
	{
		return $this->refId;
	}

	/**
	 * Sets price
	 * @param $price
	 * @return mixed
	 */
	function price($price)
	{
		return $this->set($price);
	}

	/**
	 * get price
	 */
	function getPrice()
	{
		return $this->amount;
	}

	/**
	 * Sets order id
	 * @param $orderId
	 * @return $this
	 */
	function setOrderId($orderId)
	{
		$this->orderId = $orderId;
		return $this;
	}

	/**
	 * get order id
	 */
	function getOrderId(): mixed
	{
		return $this->orderId;
	}

	/**
	 * Set redirect url
	 * @param $redirectUrl
	 * @return $this
	 */
	function setRedirectUrl($redirectUrl)
	{
		$this->redirectUrl = $redirectUrl;
		return $this;
	}

	/**
	 * get redirectUrl
	 */
	function getRedirectUrl(): mixed
	{
		return $this->redirectUrl;
	}
	

	/**
	 * get data on current transaction
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	function getData ():array
	{
		try {
			$transaction = $this->getTable()->whereId($this->transactionId)->first();
			$data = json_decode($transaction->data, true);
			return is_array($data) ? $data : [];
		}catch(\Exception $ex){
			return [];
		}
		
	}
	/**
	 * Return result of payment
	 * If result is done, return true, otherwise throws an related exception
	 *
	 * This method must be implements in child class
	 *
	 * @param object $transaction row of transaction in database
	 *
	 * @return $this
	 */
	function verify($transaction)
	{
		$this->transaction = $transaction;
		$this->transactionId = $transaction->id;
		$this->amount = intval($transaction->price);
		$this->refId = $transaction->ref_id;
	}

	function getTimeId()
	{
		$genuid = function(){
			return substr(str_pad(str_replace('.','', microtime(true)),12,0),0,12);
		};
		$uid=$genuid();
		while ($this->getTable()->whereId($uid)->first())
			$uid = $genuid();
		return $uid;
	}

	/**
	 * Insert new transaction to poolport_transactions table
	 *
	 * @return int last inserted id
	 */
	protected function newTransaction()
	{
		$uid = $this->getTimeId();

		$this->transactionId = $this->getTable()->insert([
			'id' 			=> $uid,
			'port' 			=> $this->getPortName(),
			'price' 		=> $this->amount,
			'status' 		=> Enum::TRANSACTION_INIT,
			'ip' 			=> Request::getClientIp(),
			'description'	=> $this->description,
			'created_at' 	=> Carbon::now(),
			'updated_at' 	=> Carbon::now(),
		]) ? $uid : null;

		return $this->transactionId;
	}

    /**
     * Commit transaction
     * Set status field to success status
     *
     * @param array $fields
     * @return mixed
     */
	protected function transactionSucceed(array $fields = [])
	{
	    $updateFields = [
            'status' => Enum::TRANSACTION_SUCCEED,
            'tracking_code' => $this->trackingCode,
            'card_number' => $this->cardNumber,
            'payment_date' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

	    if (!empty($fields)) {
	        $updateFields = array_merge($updateFields, $fields);
        }

		return $this->getTable()->whereId($this->transactionId)->update($updateFields);
	}

	/**
	 * Failed transaction
	 * Set status field to error status
	 *
	 * @return bool
	 */
	protected function transactionFailed()
	{
		return $this->getTable()->whereId($this->transactionId)->update([
			'status' => Enum::TRANSACTION_FAILED,
			'updated_at' => Carbon::now(),
		]);
	}

	/**
	 * Update transaction refId
	 *
	 * @return void
	 */
	protected function transactionSetRefId()
	{
		return $this->getTable()->whereId($this->transactionId)->update([
			'ref_id' => $this->refId,
			'updated_at' => Carbon::now(),
		]);

	}

	/**
	 * Update transaction data
	 *
	 * @return void
	 */
	protected function transactionSetData(array $data)
	{
		return $this->getTable()->whereId($this->transactionId)->update([
			'data' => json_encode(array_merge($this->getData(), $data)),
			'updated_at' => Carbon::now(),
		]);

	}

	/**
	 * New log
	 *
	 * @param string|int $statusCode
	 * @param string $statusMessage
	 */
	protected function newLog($statusCode, $statusMessage)
	{
		if(!empty($statusMessage) && strlen($statusMessage) > 200){
			$statusMessage = strip_tags($statusMessage); 
			$statusMessage = substr($statusMessage, 0, 200); offset: 
		}
		return $this->getLogTable()->insert([
			'transaction_id' => $this->transactionId,
			'result_code' => $statusCode,
			'result_message' => $statusMessage,
			'log_date' => Carbon::now(),
		]);
	}

	/**
	 * Add query string to a url
	 *
	 * @param string $url
	 * @param array $query
	 * @return string
	 */
	protected function makeCallback($url, array $query)
	{
		return $this->url_modify(array_merge($query, ['_token' => csrf_token()]), url($url));
	}

	/**
	 * manipulate the Current/Given URL with the given parameters
	 * @param $changes
	 * @param  $url
	 * @return string
	 */
	protected function url_modify($changes, $url)
	{
		// Parse the url into pieces
		$url_array = parse_url($url);

		// The original URL had a query string, modify it.
		if (!empty($url_array['query'])) {
			parse_str($url_array['query'], $query_array);
			$query_array = array_merge($query_array, $changes);
		} // The original URL didn't have a query string, add it.
		else {
			$query_array = $changes;
		}

		return (!empty($url_array['scheme']) ? $url_array['scheme'] . '://' : null) .
		(!empty($url_array['host']) ? $url_array['host'] : null) .
		(!empty($url_array['port']) ? ':' . $url_array['port'] : null) .
        (!empty($url_array['path']) ? $url_array['path'] : null) .
        '?' . http_build_query($query_array);
	}

	protected function cacheService() 
	{
			if (class_exists("Illuminate\Support\Facades\Cache")) {
					return  app("Illuminate\Support\Facades\Cache");
			}
			throw new \Exception("Illuminate\Support\Facades\Cache  doesn't");
	}

	protected function cacheKey(string $key): string
	{
		return "Mozakar_Gateway_" . $key;
	}

	protected function getCurrency(): string
	{
		return $this->currency;
	}

	protected function setCurrency(string $currency): static
	{
		$currency = strtoupper($currency);
		if (!in_array($currency, Currency::ALL)) {
			throw new \Exception("Currency is not valid, allowed currencies are: " . implode(", ", Currency::ALL));
		}
		$this->currency = $currency;
		return $this;
	}
}
