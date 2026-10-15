<?php

namespace Mozakar\Gateway\Sadad;

use DateTime;
use Exception;
use Mozakar\Gateway\PortAbstract;
use Mozakar\Gateway\PortInterface;

class Sadad extends PortAbstract implements PortInterface
{
	protected ?string $callbackConfig = 'gateway.sadad.callback-url';

	private const REQUEST_URL = 'https://sadad.shaparak.ir/vpg/api/v0/Request/PaymentRequest';

	private const VERIFY_URL = 'https://sadad.shaparak.ir/vpg/api/v0/Advice/Verify';

	private const TOKEN_URL = 'https://sadad.shaparak.ir/VPG/Purchase?Token=%s';

	private ?string $appName = '';


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
        $response = $this->http()->postJson($url, $params, [
            'Content-Type: application/json; charset=UTF-8',
        ]);

        if ($response->successful()) {
            return $response->body();
        }

        $this->transactionFailed();
        $decoded = $response->json();
        if (isset($decoded->ResCode)) {
            throw new SadadException($decoded->ResCode);
        }
        $this->newLog($response->status(), $response->body());
        throw new SadadException(-1);
    }

}
