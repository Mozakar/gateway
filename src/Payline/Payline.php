<?php

namespace Mozakar\Gateway\Payline;

use Illuminate\Support\Facades\Request;
use Mozakar\Gateway\Enum;
use Mozakar\Gateway\PortAbstract;
use Mozakar\Gateway\PortInterface;

class Payline extends PortAbstract implements PortInterface
{
	protected ?string $callbackConfig = 'gateway.payline.callback-url';

	protected bool $encodeCallback = true;

	/**
	 * Address of main CURL server
	 *
	 * @var string
	 */
	protected $serverUrl = 'https://pay.ir/payment/send';

	/**
	 * Address of CURL server for verify payment
	 *
	 * @var string
	 */
	protected $serverVerifyUrl = 'https://pay.ir/payment/verify';

	/**
	 * Address of gate for redirect
	 *
	 * @var string
	 */
	protected $gateUrl = 'https://pay.ir/payment/gateway/';

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
		return redirect()->to($this->gateUrl . $this->refId);
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
	 * Send pay request to server
	 *
	 * @return void
	 *
	 * @throws PaylineSendException
	 */
	protected function sendPayRequest()
	{
		$this->newTransaction();

		$fields = array(
			'api' => $this->config->get('gateway.payline.api'),
			'amount' => $this->amount,
			'redirect' => $this->getCallback(),
		);

		$response = $this->http()->post($this->serverUrl, http_build_query($fields), [], [
			'verify_host' => null,
		])->body();

		if (is_numeric($response) && $response > 0) {
			$this->refId = $response;
			$this->transactionSetRefId();

			return true;
		}

		$this->transactionFailed();
		$this->newLog($response, PaylineSendException::$errors[$response]);
		throw new PaylineSendException($response);
	}

	/**
	 * Check user payment with GET data
	 *
	 * @return bool
	 *
	 * @throws PaylineReceiveException
	 */
	protected function userPayment()
	{
		$this->refIf = Request::input('id_get');
		$trackingCode = Request::input('trans_id');

		if (is_numeric($trackingCode) && $trackingCode > 0) {
			$this->trackingCode = $trackingCode;
			return true;
		}

		$this->transactionFailed();
		$this->newLog(-4, PaylineReceiveException::$errors[-4]);
		throw new PaylineReceiveException(-4);
	}

	/**
	 * Verify user payment from zarinpal server
	 *
	 * @return bool
	 *
	 * @throws PaylineReceiveException
	 */
	protected function verifyPayment()
	{
		$fields = array(
			'api' => $this->config->get('gateway.payline.api'),
			'id_get' => $this->refId(),
			'trans_id' => $this->trackingCode()
		);

		$response = $this->http()->post($this->serverVerifyUrl, http_build_query($fields), [], [
			'verify_host' => null,
		])->body();

		if ($response == 1) {
			$this->transactionSucceed();
			$this->newLog($response, Enum::TRANSACTION_SUCCEED_TEXT);

			return true;
		}

		$this->transactionFailed();
		$this->newLog($response, PaylineReceiveException::$errors[$response]);
		throw new PaylineReceiveException($response);
	}
}
