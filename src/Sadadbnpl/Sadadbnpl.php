<?php

namespace Mozakar\Gateway\Sadadbnpl;

use DateTime;
use Exception;
use Mozakar\Gateway\PortAbstract;
use Mozakar\Gateway\PortInterface;

class Sadadbnpl extends PortAbstract implements PortInterface
{
    private const REDIRECT_URL = '/Home?key=%s';
	private const VERIFY_URL = '/api/v0/BnplAdvice/Verify';

	private const TOKEN_URL = '/Bnpl/GenerateKey';
    private const REVERSE_URL = '/api/v0/BnplReverseRequest';

	private string $appName = 'Bnpl';
    private int $panAuthenticationType = 2;

    private string $nationalCode = "";
    private string $nationalCodeEncrypted = "";
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
		$this->tokenRequest();

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function redirect()
	{
		$paymentUrl = $this->getUrl(sprintf(self::REDIRECT_URL, $this->token));
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
			$this->callbackUrl = $this->config->get('gateway.sadad_bnpl.callback-url');

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
        return $this->appName ?? 'Bnpl';
    }

    /**
     * Set pan authentication type function
     * @param int $panAuthenticationType
     * 1 => national code, 2 => mobile, 3 => encrypted natinal code
     * @return $this
     */
    public function setPanAuthenticationType(int $panAuthenticationType): self
    {
        $this->panAuthenticationType = $panAuthenticationType;
        return $this;
    }

    /**
     * Get pan authentication type
     * @return int
     */
    public function getPanAuthenticationType(): int
    {
        return $this->panAuthenticationType;
    }

    /**
     * Set natinal code function
     * @param string $nationalCode
     * @return $this
     */
    public function setNationalCode(string $nationalCode): self
    {
        $this->nationalCode = $nationalCode;
        return $this;
    }

    /**
     * Get natinal code
     * @return string
     */
    public function getNationalCode(): string
    {
        return $this->nationalCode;
    }

    /**
     * Set encrypted natinal code function
     * @param string $nationalCodeEncrypted
     * @return $this
     */
    public function setNationalCodeEncrypted(string $nationalCodeEncrypted): self
    {
        $this->nationalCodeEncrypted = $nationalCodeEncrypted;
        return $this;
    }

    /**
     * Get encrypted natinal code
     * @return string
     */
    public function getNationalCodeEncrypted(): string
    {
        return $this->nationalCodeEncrypted;
    }

    /**
	 * Send pay request to server
	 *
	 * @return void
	 *
	 * @throws SadadBnplException
	 */
	protected function tokenRequest()
	{
		$this->newTransaction();
		try {
            if ($this->getPanAuthenticationType() == 1) {
                if (empty($this->getNationalCode()) || strlen($this->getNationalCode()) != 10) {

                }
            } elseif($this->getPanAuthenticationType() == 2) {
                if (empty($this->getMobile())) {

                }
            } elseif($this->getPanAuthenticationType() == 3) {
                if (empty($this->getNationalCodeEncrypted())) {

                }
            } else {

            }
			$terminalId = $this->config->get('gateway.sadad_bnpl.terminalId');
			$orderId = $this->getOrderId();
			$dateTime = new DateTime();
			$data = [
					'TerminalId' => $terminalId,
					'MerchantId' => $this->config->get('gateway.sadad_bnpl.merchant'),
					'Amount' => $this->amount,
					'ReturnUrl' =>$this->getCallback(),
					// 'LocalDateTime' => $dateTime->format('Y-m-d H:i:s'),
					'OrderId' => $orderId,
					'UserId' => $this->getMobile(),
					'ApplicationName' => $this->getAppName(),
                    'PanAuthenticationType' => $this->getPanAuthenticationType(),
                    // 'NationalCode' => $this->getNationalCode(),//reqired if panAuthenticationType us 1
                    'CardHolderIdentity' => $this->getMobile(),//reqired if panAuthenticationType us 2
                    'SourcePanList' => '',
                    // 'NationalCodeEnc' => $this->nationalCodeEncrypted,//reqired if panAuthenticationType us 3

			];
			$result = json_decode($this->curl_post($this->getUrl(self::TOKEN_URL), $data));

			if (isset($result->ResponseCode) && $result->ResponseCode != 0) {
				throw new SadadBnplException($result->ResponseCode, $result->Message ?? null);
			}

			if (! isset($result->BnplKey)) {
                throw new SadadBnplException(-2,json_encode($result));
			}

			$this->token = $result->BnplKey;
			$this->refId = $result->BnplKey;
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
	 * @throws SadadBnplException
	 */
	protected function verifyPayment()
	{
		$token =  $this->request->get('Token', '');
		$data = [
				'Token' => $token,
				'SignData' => $this->signData($token),
                'ReturnUrl' => '',
		];

		try {
				$result = json_decode($this->curl_post($this->getUrl(self::VERIFY_URL, false), $data));
				if (isset($result->ResCode) && $result->ResCode != 0) {
					$this->transactionFailed();
					throw new SadadBnplException($result->ResCode);
				}
				if (
                    !isset(
                            $result->Amount,
                            $result->SystemTraceNo,
                            $result->RetrivalRefNo,
                            $result->ResCode
                    )
				) {
						$this->transactionFailed();
						throw new SadadBnplException(-3);
				}

				if ($result->ResCode != 0) {
					$this->transactionFailed();
					throw new SadadBnplException($result->ResCode);
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
	 * reverse
	 *
	 * @param  int $amount
	 * @param  string $token
	 * @return array
     * @throws Exception
	 */
	public function reverse(int $amount, string $token): array
	{
        $terminalId = $this->config->get('gateway.sadad_bnpl.terminalId');
		$data = [
            'TerminalId' => $terminalId,
            'CardAcqId' => $this->config->get('gateway.sadad_bnpl.merchant'),
            'Amount' => $amount,
            'Token' => $token,
            'SignData' => $this->signData($token),
		];

		try {
            return json_decode($this->curl_post($this->getUrl(self::REVERSE_URL, false), $data));
        } catch (Exception $ex) {
            throw $ex;
        }
	}

	 /**
     * Sign Data function
     */
    private function signData(string $str): string
    {
        $key = base64_decode($this->config->get('gateway.sadad_bnpl.transactionKey'));
        $ciphertext = openssl_encrypt($str, 'DES-EDE3', $key, OPENSSL_RAW_DATA);

        return base64_encode($ciphertext);
    }

    /**
     * getUrl
     *
     * @param string $path
     * @param bool $isBnpl
     * @return string
     */
    private function getUrl(string $path, bool $isBnpl = true): string
    {
        $baseUrl = $this->config->get('gateway.sadad_bnpl.url');
        $vpgBaseUrl = $this->config->get('gateway.sadad_bnpl.vpg_url', '');
        if($isBnpl) {
            return "{$baseUrl}{$path}";
        }
        return "{$vpgBaseUrl}{$path}";
    }
	/**
     * curl_post
     *
     * @param  string $path
     * @param  array $params
     * @return string
     * @throws SadadBnplException
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
        if (isset($response->ResponseCode)) {
            $message = isset($response->Message) ? $response->Message : '';
            throw new SadadBnplException($response->ResponseCode, $message);
        }
        if (isset($response->ResCode)) {
            $message = isset($response->Description) ? $response->Description : '';
            throw new SadadBnplException($response->ResCode, $message);
        }
        $this->newLog($info["http_code"], $res);
        throw new SadadBnplException(-1);
    }

}