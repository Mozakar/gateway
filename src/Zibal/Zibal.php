<?php

namespace Mozakar\Gateway\Zibal;

use Mozakar\Gateway\Enum;
use Mozakar\Gateway\PortAbstract;
use Mozakar\Gateway\PortInterface;

class Zibal extends PortAbstract implements PortInterface
{

	/**
	 * Address of base url
	 *
	 * @var string
	 */
	protected $serverUrl = 'https://gateway.zibal.ir/';

	private const  REQUEST_PATH = 'v1/request';
	private const  PAY_PATH = 'start/';
	private const  VERIFY_PATH = 'v1/verify';
	private const INQUIRY_PATH = 'v1/inquiry';


	/**
	 * Payment Description
	 *
	 * @var string
	 */
	protected $description = '';

	/**
	 * ledgerId
	 *
	 * @var string
	 */
	protected $ledgerId = '';

	/**
	 * nationalCode
	 *
	 * @var string
	 */
	protected $nationalCode = '';

	/**
	 * allowedCards
	 *
	 * @var string
	 */
	protected $allowedCards = '';

	/**
	 * check mobile with card
	 *
	 * @var bool
	 */
	protected $checkMobileWithCard = false;


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
	public function set($amount)
	{
		$this->amount = intval($amount);

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function redirect()
	{
		return \Redirect::to($this->serverUrl . self::PAY_PATH  . $this->refId);
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
			$this->callbackUrl = $this->config->get('gateway.zibal.callback-url');

		return $this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]);
	}

	/**
	 * Send pay request to server
	 *
	 * @return void
	 *
	 * @throws ZibalException
	 */
	protected function sendPayRequest()
	{
		$this->newTransaction();

		$fields = [
			'merchant' => $this->config->get('gateway.zibal.merchant'),
			'amount' => $this->amount,
			'callbackUrl' => $this->getCallback(),
			'orderId'	=> $this->getOrderId(),
			'mobile' => $this->getMobile(),
			'allowedCards'	=> $this->allowedCards,
			'ledgerId'	=> $this->ledgerId,
			'nationalCode'	=> $this->nationalCode,
			'checkMobileWithCard' => $this->checkMobileWithCard,
			'description' => $this->description ? $this->description : $this->config->get('gateway.zibal.description', ''),
		];

		try {
			$res = $this->curl_post($this->serverUrl . self::REQUEST_PATH, $fields);
			$res = json_decode($res, true);
			if (isset($res['result'])) {
				if ($res['result'] == 100) {
					$this->refId = $res['trackId'];
					$this->transactionSetRefId();
				} else {
					$this->transactionFailed();
					$this->newLog($res['result'], ZibalException::$errors[$res['result']]);
					throw new ZibalException($res['result']);
				}

			} else {
				$this->transactionFailed();
				$this->newLog(0, ZibalException::$errors[0]);
				throw new ZibalException(0);
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
	 * @throws ZibalException
	 */
	protected function userPayment()
	{
		$this->authority = $this->request->get('trackId', '');
		$success = $this->request->get('success', 0);
		$status = $this->request->get('status', 0);

		if ($success == 1) {
			return true;
		}

		$this->transactionFailed();
		$this->newLog($status, ZibalException::$errors[$status]);
		throw new ZibalException($status);
	}

	/**
	 * Verify user payment from zibal server
	 *
	 * @return bool
	 *
	 * @throws ZibalException
	 */
	protected function verifyPayment()
	{

		$fields = [
			'merchant' => $this->config->get('gateway.zibal.merchant'),
			'trackId' => $this->refId,
		];

		$res = [];
		try {
			$response = $this->curl_post($this->serverUrl . self::VERIFY_PATH, $fields);
			$res = json_decode($response, true);
			$this->transactionSetData(['verify' => $res]);
			if (!isset($res['result']) || $res['result'] != 100) {
				$code = isset($res['data']) ? $res['data'] : 0;
				$this->transactionFailed();
				$this->newLog($code, ZibalException::$errors[$code]);
				throw new ZibalException($code);
			}

		} catch (\Exception $e) {
			$this->transactionFailed();
			$this->newLog(0, $e->getMessage());
			throw $e;
		}

		$this->trackingCode = $res['refNumber'];
		$this->transactionSucceed();
		$this->newLog($res['result'], Enum::TRANSACTION_SUCCEED_TEXT);
		return true;
	}


	/**
	 * inquiry
	 *
	 * @param  string $trackId
     * @throws \Exception
	 * @return array
	 */
	public function inquiry(string $trackId): array
	{
		$data = [
				'merchant' => $this->config->get('gateway.zibal.merchant'),
				'trackId' => $trackId,
		];
		try {
			return json_decode($this->curl_post($this->serverUrl . self::INQUIRY_PATH, $data));
        } catch (\Exception $ex) {
            throw $ex;
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
	 * Set ledgerId
	 *
	 * @param $ledgerId
	 * @return void
	 */
	public function setLedgerId($ledgerId)
	{
		$this->ledgerId = $ledgerId;
	}

	/**
	 * Set Payer nationalCode
	 *
	 * @param $nationalCode
	 * @return void
	 */
	public function setNationalCode($nationalCode)
	{
		$this->nationalCode = $nationalCode;
	}

	/**
	 * Set Payer allowedCards
	 *
	 * @param $allowedCards
	 * @return void
	 */
	public function setAllowedCards($allowedCards)
	{
		$this->allowedCards = $allowedCards;
	}

	/**
	 * Set checkMobileWithCard
	 *
	 * @param $checkMobileWithCard
	 * @return void
	 */
	public function setCheckMobileWithCard(bool $checkMobileWithCard)
	{
		$this->checkMobileWithCard = $checkMobileWithCard;
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

      $result = json_decode($res, true);
      if (isset($result['result'])) {
        throw new ZibalException($result['result']);
      }

		  throw new ZibalException(0);
  }
}
