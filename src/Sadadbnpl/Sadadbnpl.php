<?php

namespace Mozakar\Gateway\Sadadbnpl;

use Exception;
use Mozakar\Gateway\PortAbstract;
use Mozakar\Gateway\PortInterface;
use Mozakar\Gateway\Currency;

class Sadadbnpl extends PortAbstract implements PortInterface
{
	private const VERIFY_URL = 'BNPL/Financial/CPG/VerifyTransaction';

	private const TOKEN_URL = 'CPG/Security/Token/RequestToken';

	private string $paymentUrl = '';

    protected string $currency = Currency::RIAL;
	/**
	 * {@inheritdoc}
	 */
	public function set($amount)
	{
		$this->amount = intval($amount);

		return $this;
	}
	/**
	 * Set currency
	 * @param string $currency
	 * @return $this
	 */
    protected function setCurrency(string $currency): static
	{
		$currency = strtoupper($currency);
        $allowedCurrencies = [Currency::RIAL, Currency::TOMAN];
		if (!in_array($currency, $allowedCurrencies)) {
			throw new \Exception("Currency is not valid, allowed currencies are: " . implode(", ", $allowedCurrencies));
		}
		$this->currency = $currency;
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
		return redirect($this->paymentUrl);
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
            $encryptedPassword = $this->encryptAES($this->config->get('gateway.sadad_bnpl.password'));
			$amount = $this->amount;
            if($this->currency == Currency::TOMAN) {
				$amount = $this->amount * 10;
			}
            $data = [
					'ServiceUserName' => $this->config->get('gateway.sadad_bnpl.username'),
					'ServicePassword' => $encryptedPassword,
					'MerchantNumber' => $this->config->get('gateway.sadad_bnpl.merchant'),
					'Amount' => $amount,
					'ReturnUrl' =>$this->getCallback(),
					'CellNumber' => $this->getMobile(),

			];
			$result = json_decode($this->curl_post(self::TOKEN_URL, $data), true);
			$hasErrors = $result['notification']['hasErrors'] ?? false;
			if ($hasErrors) {
				$messages = array_column($result['notification']['errors'] ?? [], 'message');
				$message = implode(', ', $messages);
				$code = $result['notification']['errors'][0]['code'] ?? -1;
            	throw new SadadBnplException($code, $message);
			}

			$this->refId = $result['result']['entity']['token'];
			$this->paymentUrl = $result['result']['entity']['redirectURL'] ?? '';
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
		$token =  $this->request->get('tv', '');
		$encryptedPassword = $this->encryptAES($this->config->get('gateway.sadad_bnpl.password'));
		$data = [
				'Token' => $token,
				'serviceUserName' => $this->config->get('gateway.sadad_bnpl.username'),
                'servicePassword' => $encryptedPassword,
		];

		try {
				$result = json_decode($this->curl_post(self::VERIFY_URL, $data), true);
                $hasErrors = $result['notification']['hasErrors'] ?? false;
                if ($hasErrors) {
                    $messages = array_column($result['notification']['errors'] ?? [], 'message');
                    $message = implode(', ', $messages);
                    $code = $result['notification']['errors'][0]['code'] ?? -1;
                    throw new SadadBnplException($code, $message);
                }
				if (!isset($result['Result']['entity']['IsApproved']) || $result['Result']['entity']['IsApproved'] == false) {
					$this->transactionFailed();
					throw new SadadBnplException(-100);
				}

				$data = array_merge($data, ['verify' => $result]);
				$this->transactionSetData($data);
				$this->trackingCode =$result['Result']['entity']['RefNumber'] ?? '';
				$this->transactionSucceed();
			} catch (Exception $ex) {
				$this->newLog('Exception', $ex->getMessage());
				$this->transactionFailed();
				throw $ex;
			}
	}

	 /**
     * Encrypt AES function
     * @param string $input
     * @return string
     */
    private function encryptAES(string $input): string
    {
        try {
            // Decode the base64 encoded key and IV
            $keyDecoded = base64_decode($this->config->get('gateway.sadad_bnpl.encryptionKey'));
            $ivDecoded = base64_decode($this->config->get('gateway.sadad_bnpl.encryptionVector'));

            // Encrypt using AES-128-CBC with PKCS7 padding (default in OpenSSL)
            $encrypted = openssl_encrypt(
                $input,
                'AES-128-CBC',
                $keyDecoded,
                OPENSSL_RAW_DATA,
                $ivDecoded
            );

            if ($encrypted === false) {
                return '';
            }

            // Return base64 encoded result
            return base64_encode($encrypted);

        } catch (Exception $e) {
            return '';
        }
    }
	/**
     * curl_post
     *
     * @param  string $serviceName
     * @param  array $params
     * @return string
     * @throws SadadBnplException
     */
    function curl_post(string $serviceName, array $params = []): string
    {
		$url = $this->config->get('gateway.sadad_bnpl.url', 'https://op-cpg-wrapper.bmicc.ir:44377/WEBAPIWrapper/ConsumerExternalWebapiWrapper');
		$body = [
			'ServiceName' => $serviceName,
			'InputValue' => $params,
		];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=UTF-8',
        ]);
        $res = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        if($info["http_code"] == 200 || $info["http_code"] == "200") {
			return $res;
		}
        $response = json_decode($res, true);
        if (isset($response['notification']['errors'])) {
            $messages = array_column($response['notification']['errors'] ?? [], 'message');
			$message = implode(', ', $messages);
			$code = $response['notification']['errors'][0]['code'] ?? -1;
            throw new SadadBnplException($code, $message);
        }
        $this->newLog($info["http_code"], $res);
        throw new SadadBnplException(-100);
    }

}
