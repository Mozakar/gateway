<?php

namespace Mozakar\Gateway\Miligold;

use Exception;
use Mozakar\Gateway\Currency;
use Mozakar\Gateway\PortAbstract;
use Mozakar\Gateway\PortInterface;

class Miligold extends PortAbstract implements PortInterface
{
	private const PURCHASE_PATH = 'psp/v1/purchase';
	private const VERIFY_PATH = 'psp/v1/purchase/%s/payment/verify';

	private const INQUIRY_PATH = 'psp/v1/purchase/%s/payment/inquiry';
	private const REVERSE_PAYMENT_PATH = 'psp/v1/purchase/%s/payment/reverse';
	private const PRICE_PATH = 'milli-api/v1/public/milli-price';

	private $paymentUrl = "";
	private $platform = "WEB";
	private $channel = "MILLI_SHOP";
	private $clientVersion = "1.0.0";

	public function setPlatform(string $platform): static {
		$allowedPlatforms = ["WEB", "PWA", "ANDROID", "IOS"];
		$platform = strtoupper($platform);
		if (!in_array($platform, $allowedPlatforms)) {
			$platform = "WEB";
		}
		$this->platform = $platform;
		return $this;
	}

	public function getPlatform(): string {
		return $this->platform;
	}

	public function setChannel(string $channel): static {
		$this->channel = $channel;
		return $this;
	}

	public function getChannel(): string {
		return $this->channel;
	}

	public function setClientVersion(string $clientVersion): static {
		$this->clientVersion = $clientVersion;
		return $this;
	}

	public function getClientVersion(): string {
		return $this->clientVersion;
	}
	/**
	 * {@inheritdoc}
	 */
	public function set($amount): static
	{
		$this->amount = intval($amount);
		return $this;
	}


	/**
	 * {@inheritdoc}
	 */
	public function ready(): static
	{
		$this->newTransaction();
		$this->purchaseRequest();
		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function redirect(): mixed
	{
		return redirect($this->paymentUrl);
	}

	/**
	 * {@inheritdoc}
	 */
	public function verify($transaction): static
	{
		parent::verify($transaction);

		$this->verifyPayment();
		return $this;
	}

	/**
	 * Sets callback url
	 * @param $url
	 */
	function setCallback($url): static
	{
		$this->callbackUrl = $url;
		return $this;
	}

	/**
	 * Gets callback url
	 * @return string
	 */
	function getCallback(): string
	{
		if (!$this->callbackUrl)
		{
			$this->callbackUrl = $this->config->get('gateway.mili_gold.callback-url');
		}

		return $this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]);
	}

    /**
	 * Send pay request to server
	 *
	 * @return void
	 *
	 * @throws MiligoldException
	 */
	protected function purchaseRequest(): void
	{
		try {
			$miliAmount = $this->amount;
			$miliPrice = 0;
			if($this->getCurrency() != Currency::MILI) {
				$price = $this->getPrice();
				if (!isset($price['statusCode']) && !isset($price['code'])) {
					throw new MiligoldException(-3);
				}
				$statusCode = $price['statusCode'] ?? $price['code'];
				if ($statusCode != 0) {
					throw new MiligoldException($statusCode, $price['message'] ?? null);
				}
				if($this->getCurrency() == Currency::TOMAN) {
					$this->amount = $this->amount * 10;
				}
				$miliPrice = $price['data']['price'];
				$miliAmount = $this->amount / $price['data']['price'];
				$miliAmount = ceil($miliAmount);
			}
			$mili = [
				'amount' => $miliAmount,
				'price' => $miliPrice,
			];
			$data = [
					'merchantNumber' 		=> $this->config->get('gateway.mili_gold.merchant'),
					'terminalNumber' 		=> $this->config->get('gateway.mili_gold.terminal'),
					'amount' 				=> $miliAmount,
					'clientReferenceNumber'	=> $this->transactionId(),
					'clientCallbackUrl' 	=> $this->getCallback(),
					'mobileNumber' 			=> $this->normalize_iran_mobile($this->getMobile()),
					'clientAdditionalData' 	=> $this->getCustomDesc(),
			];
			$result = json_decode($this->curl_post(self::PURCHASE_PATH, $data));
			$this->transactionSetData(['miligold' => $mili, 'purchase' => $result]);
			if (isset($result->code) && $result->code != 0) {
				throw new MiligoldException($result->code, $result->message ?? null);
			}

			if (! isset($result->data->referenceNumber)) {
                throw new MiligoldException(-2,json_encode($result));
			}

			$this->token = $result->data->referenceNumber;
			$this->refId = $result->data->referenceNumber;
			$this->paymentUrl = $result->data->redirectUrl;
			if (!strstr($this->paymentUrl, 'http://') && !strstr($this->paymentUrl, 'https://')) {
				$this->paymentUrl = "https://" . $this->paymentUrl;
			}
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
	 * @throws MiligoldException
	 */
	protected function verifyPayment(): void
	{
		try {
				$path = sprintf(self::VERIFY_PATH, $this->refId());
				$result = json_decode($this->curl_post($path, []));
				$this->transactionSetData(['verify' => $result]);

				if (!isset($result->code) || $result->code != 0) {
					$this->transactionFailed();
					throw new MiligoldException($result->code);
				}
				$this->transactionSucceed();
			} catch (Exception $ex) {
				$this->newLog('Exception', $ex->getMessage());
				$this->transactionFailed();
				throw $ex;
			}
	}


	/**
	 * inquiry
	 *
	 * @param  string $referenceNumber
     * @throws Exception
	 * @return array
	 */
	public function inquiry(string $referenceNumber): array
	{
		$path = sprintf(self::INQUIRY_PATH, $referenceNumber);
		try {
			return json_decode($this->curl_get($path), true);
        } catch (Exception $ex) {
            throw $ex;
        }
	}

    /**
	 * reverse payment
	 *
	 * @param  string $referenceNumber
     * @throws Exception
	 * @return array
	 */
	public function reversePayment(string $referenceNumber): array
	{
		$path = sprintf(self::REVERSE_PAYMENT_PATH, $referenceNumber);
		try {
		    return json_decode($this->curl_post($path, []), true);
        } catch (Exception $ex) {
            throw $ex;
        }
	}

	/**
	 * get price
	 *
     * @throws Exception
	 * @return array
	 *
	 * example:
	 * {
	 * 	"code": 0,
	 * 	"message": "عملیات با موفقیت انجام شد",
	 * 	"data": {
	 * 		"price": 10000
	 * 	}
	 * }
	 */
	public function getPrice(): array
	{
		try {
		    return json_decode($this->curl_get(self::PRICE_PATH), true);
        } catch (Exception $ex) {
            throw $ex;
        }
	}


    /**
     * curl_get
     *
     * @param  string $path
     * @return string
     * @throws MiligoldException
     */
    function curl_get(string $path): string
    {
        $baseUrl = trim($this->config->get('gateway.mili_gold.base_url'), '/');
        $url = $baseUrl . '/' . $path;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'X-API-KEY:' . $this->config->get('gateway.mili_gold.api_key'),
			'X-Platform:' . $this->getPlatform(),
			'X-Channel:' . $this->getChannel(),
			'X-Client-Version:' . $this->getClientVersion(),
            'Content-Type: application/json; charset=UTF-8',
        ]);
        $res = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        if($info["http_code"] == 200 || $info["http_code"] == "200") {
			return $res;
		}
        $response = json_decode($res);
        if (isset($response->code)) {
            $message = isset($response->message) ? $response->message : '';
            throw new MiligoldException($response->code, $message);
        }
        throw new MiligoldException(-1);
    }

	/**
     * curl_post
     *
     * @param  string $path
     * @param  array $params
     * @return string
     * @throws MiligoldException
     */
    function curl_post(string $path, array $params = []): string
    {
		$baseUrl = trim($this->config->get('gateway.mili_gold.base_url'), '/');
        $url = $baseUrl . '/' . $path;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'X-API-KEY:' . $this->config->get('gateway.mili_gold.api_key'),
			'X-Platform:' . $this->getPlatform(),
			'X-Channel:' . $this->getChannel(),
			'X-Client-Version:' . $this->getClientVersion(),
            'Content-Type: application/json; charset=UTF-8',
        ]);
        $res = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        if($info["http_code"] == 200 || $info["http_code"] == "200") {
			return $res;
		}

        $response = json_decode($res);
        if (isset($response->code)) {
            $message = isset($response->message) ? $response->message : '';
            throw new MiligoldException($response->code, $message);
        }
        throw new MiligoldException(-1);
    }


	/**
	 * Normalize Iranian mobile numbers to format: +989XXXXXXXXX
	 *
	 * @param string $input
	 * @return string|false Normalized number or false if invalid
	 */
	function normalize_iran_mobile($input): bool|string {
		if(empty($input)) {
			return false;
		}
		$digits = preg_replace('/\D+/', '', $input);

		if ($digits === '') {
			return false;
		}
		if (strpos($digits, '00') === 0 && strpos($digits, '0098') === 0) {
			$digits = substr($digits, 2); // '0098912...' -> '98912...'
		}

		if (strlen($digits) === 11 && $digits[0] === '0' && $digits[1] === '9') {
			$national = substr($digits, 1); // '9123456789'
			return '+98' . $national;
		}

		if (strlen($digits) === 10 && $digits[0] === '9') {
			return '+98' . $digits;
		}

		if (strlen($digits) === 12 && strpos($digits, '98') === 0 && $digits[2] === '9') {
			return '+' . $digits;
		}

		if (strlen($digits) === 13 && strpos($digits, '0098') === 0) {
			$digits2 = substr($digits, 2); // '0098...' -> '98...'
			if (strlen($digits2) === 11 && strpos($digits2, '98') === 0) {
				return '+' . $digits2;
			}
		}

		if (preg_match('/^989\d{9}$/', $digits)) {
			return '+' . $digits;
		}
		return false;
	}

}