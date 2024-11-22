<?php

namespace Mozakar\Gateway\Sadad;

use DateTime;
use Exception;
use Mozakar\Gateway\PortAbstract;
use Mozakar\Gateway\PortInterface;

class Sadad extends PortAbstract implements PortInterface
{
	private const REQUEST_URL = 'https://sadad.shaparak.ir/vpg/api/v0/Request/PaymentRequest';

	private const VERIFY_URL = 'https://sadad.shaparak.ir/vpg/api/v0/Advice/Verify';

	private const TOKEN_URL = 'https://sadad.shaparak.ir/VPG/Purchase?Token=%s';

	private ?string $appName = '';


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
		$paymentUrl = sprintf(self::TOKEN_URL, $this->token);
		return redirect($paymentUrl);
	}

	/**
	 * {@inheritdoc}
	 */
	public function verify($transaction)
	{
		parent::verify($transaction);

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
			$this->callbackUrl = $this->config->get('gateway.sadad.callback-url');

		return $this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]);
	}

	  /**
     * Set Application Name function
     *
     * @return $this
     */
    public function setAppName(string $name): self
    {
        $this->appName = $name;

        return $this;
    }

    /**
     * Get Application Name function
     */
    public function getAppName(): ?string
    {
        return $this->appName;
    }


	/**
	 * Send pay request to server
	 *
	 * @return void
	 *
	 * @throws SadadException
	 */
	protected function sendPayRequest()
	{
		$this->newTransaction();
		try {
			$terminalId = $this->config->get('gateway.sadad.terminalId');
			$orderId = $this->getOrderId();
			$signData = $this->signData("{$terminalId};{$orderId};{$this->amount}");
			$dateTime = new DateTime();
			$data = [
					'TerminalId' => $terminalId,
					'MerchantId' => $this->config->get('gateway.sadad.merchant'),
					'Amount' => $this->amount,
					'SignData' => $signData,
					'ReturnUrl' =>$this->getCallback(),
					'LocalDateTime' => $dateTime->format('Y-m-d H:i:s'),
					'OrderId' => $orderId,
					'UserId' => $this->getMobile(),
					'ApplicationName' => $this->getAppName(),
			];

			$result = json_decode($this->curl_post(self::REQUEST_URL, $data));

			if (isset($result->ResCode) && $result->ResCode != 0) {
				throw new SadadException($result->ResCode, $result->Description ?? null);
			}

			if (! isset($result->Token)) {
					throw new SadadException(-2,json_encode($result));
			}

			$this->token = $result->Token;
			$this->refId = $result->Token;
			$this->transactionSetRefId();

		} catch (Exception $e) {
			$this->transactionFailed();
			$this->newLog('Exception', $e->getMessage());
			throw $e;
		}
	}

	/**
	 * Verify user payment from bank server
	 *
	 * @throws SadadException
	 */
	protected function verifyPayment()
	{
		$token = $this->refId;

		$data = [
				'Token' => $token,
				'SignData' => $this->signData($token),
		];

		try {
				$result = json_decode($this->curl_post(self::VERIFY_URL, $data));

				if (isset($result->ResCode) && $result->ResCode != 0) {
					$this->transactionFailed();
					throw new SadadException($result->ResCode);
				}
				if (
						! isset(
								$result->Amount,
								$result->SystemTraceNo,
								$result->RetrivalRefNo,
								$result->ResCode
						)
				) {
						$this->transactionFailed();
						throw new SadadException(-3);
				}

				if ($result->ResCode != 0) {
					$this->transactionFailed();
					throw new SadadException($result->ResCode);
				}
				
				
				$data = array_merge($data, ['verify' => $result]);
				$this->transactionSetData($data);
				$this->trackingCode = $result->SystemTraceNo;
				$this->transactionSucceed();
			} catch (Exception $ex) {
				$this->newLog('Exception', $ex->getMessage());
				$this->transactionFailed();
				throw $ex;
			}
	}

	 /**
     * Sign Data function
     */
    private function signData(string $str): string
    {
        $key = base64_decode($this->config->get('gateway.sadad.transactionKey'));
        $ciphertext = openssl_encrypt($str, 'DES-EDE3', $key, OPENSSL_RAW_DATA);

        return base64_encode($ciphertext);
    }

		/**
     * curl_post
     *
     * @param  string $url
     * @param  array $params
     * @return string
     * @throws SadadException
     */
    function curl_post(string $url, array $params = []): string
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=UTF-8',
        ]);
        $res = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        

        if($info["http_code"] == 200 || $info["http_code"] == "200")
            return $res;

        $this->transactionFailed();
        $response = json_decode($res);
        if (isset($response->ResCode)) {
            throw new SadadException($response->ResCode);
        }
        $this->newLog($info["http_code"], $res);
        throw new SadadException(-1);
    }

}
