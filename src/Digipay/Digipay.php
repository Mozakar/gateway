<?php

namespace Mozakar\Gateway\Digipay;

use DateTime;
use Exception;
use Mozakar\Gateway\PortAbstract;
use Mozakar\Gateway\PortInterface;
use Mozakar\Gateway\Digipay\DigipayException;

class Digipay extends PortAbstract implements PortInterface
{
    /**
     *
     * @var array $optional_data An array of optional data
     *  that will be sent with the payment request
     *
     */
    protected $optional_data = [];
    private const VERSION = '2022-02-02';

    private string $baseUrl = 'https://api.mydigipay.com/digipay/api/';
    private const SANDBOX_URL = 'https://uat.mydigipay.info/digipay/api/';
    private const AUTH_URL = 'oauth/token';

    private const REQUEST_URL = 'tickets/business?type=%s';

    private const VERIFY_URL = 'purchases/verify/%s?type=%s';

    private const DELIVER_URL = 'purchases/deliver?type={ticketType}';

    private const PARTIAL_REFUND_URL = 'digipay/api/refunds?type=%s';
    private const REFUND_URL = 'refunds?type=%s';
    private const REFUND_STATUS_URL = 'refunds/%s';

	protected string $token;
    protected string $accessToken;
    protected string $paymentUrl;

    private string $ticketType;
    private array $basketItems = [];

    /**
     * {@inheritdoc}
     */
    public function set($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function ready(): self
    {
        $this->newTransaction();
        $this->redirect();
        return $this;
    }

    /**
     *
     * Add optional data to the request
     *
     * @param array $data an array of data
     *
     */
    function setOptionalData (array $data): void
    {
        $this->optional_data = $data;
    }

   
    public function setTicketType(string $ticketType):self
    {
        $this->ticketType = $ticketType;
        return $this;
    }

    public function getTicketType():string
    {
        return $this->ticketType;
    }

    public function setBasketItems(array $basketItems):self
    {
        $this->basketItems = $basketItems;
        return $this;
    }

    public function getBasketItems():array
    {
        return $this->basketItems;
    }

    private function purchase()
	{
        $params = [
            'cellNumber' => $this->mobile,
            'amount' => $this->amount,
            'providerId' => $this->getOrderId(),
            'callbackUrl' => $this->getCallback(),
        ];

        if (count($this->getBasketItems())) {
            $params['basketDetailsDto'] = $this->getBasketItems();
        }

		try{
            $endpoint = sprintf(self::REQUEST_URL, $this->getTicketType());
            $response = json_decode($this->curl_post($endpoint, $params), true);
            if(isset($response['redirectUrl'])  && isset($response['ticket'])) {
                $this->token = $response['ticket'];
                $this->refId = $response['ticket'];
                $this->paymentUrl = $response['redirectUrl'];
                $this->transactionSetRefId();
            } else {
                $this->transactionFailed();

                $this->newLog(-3, 'خطا');
                throw new DigipayException(-3, 'خطا');
            }

		} catch(Exception $e){
            $this->newLog('Exception', $e->getMessage());
			$this->transactionFailed();
			throw $e;
		}

	}

    /**
     * {@inheritdoc}
     */
    public function redirect()
    {
        $this->purchase();
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
            $this->callbackUrl = $this->config->get('gateway.payping.callback-url');

        $url = $this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]);

        return $url;
    }

    /**
     * Verify user payment
     *
     * @return bool
     *
     * @throws Exception
     */
    protected function verifyPayment(): bool
    {
        try{
            $type = $this->getRequest()->get('type', '');
            $callbackData = $this->getRequest()->all();
            $data = array_merge($this->getData(), ['callback_data' => $callbackData]);
            $this->transactionSetData($data);
            $this->trackingCode = $callbackData['trackingCode'] ?? '';

            $endpoint = sprintf(self::VERIFY_URL, $this->trackingCode, $type);
            $response = json_decode($this->curl_post($endpoint, []), true);
            $data = array_merge($data, ['verify' => $response]);
            $this->transactionSetData($data);
            
            $this->transactionSucceed();
            return true;
        } catch(Exception $e){
            $this->newLog('Exception', $e->getMessage());
            $this->transactionFailed();
            throw $e;
        }

    }

    /**
     *
     * @param string $trackingCode
     * @param string $invoiceNumber
     * @param array $products
     * @param DateTime $deliverTime
     * @return array
     *
     * @throws Exception
     */

    public function deliver(string $trackingCode, string $invoiceNumber, array $products, DateTime $deliverTime = null): array
    {
        if (is_null($deliverTime)) {
            $deliverTime = new DateTime();
        }

        $params = [
            'deliveryDate' => $deliverTime->getTimestamp(),
            'invoiceNumber' => $invoiceNumber,
            'trackingCode' => $trackingCode,
            'products' => $products,
        ];

        try {
            $endpoint = sprintf(self::DELIVER_URL, $this->getTicketType());
            $response = json_decode($this->curl_post($endpoint, $params), true);
            $data = array_merge($this->getData(), ['deliver' => $response]);
            $this->transactionSetData($data);
            return $response;
        } catch (Exception $e) {
            throw $e;
        }

    }

    /**
     *
     * @param  string $orderId
     * @param  int $amount
     * @param  string $trackingCodeOrOrderId
     * @return array
     *
     * @throws Exception
     */
    public function partialRefund(string $orderId, int $amount, string $trackingCode): array
    {
        $params = [
            'providerId' => $orderId,
            'amount' => $amount,
            'saleTrackingCode' => $trackingCode,
        ];
        try {
            $endpoint = sprintf(self::PARTIAL_REFUND_URL, $this->getTicketType());
            return json_decode($this->curl_post($endpoint, $params), true);
        } catch (Exception $e) {
            throw $e;
        }

    }

    /**
     *
     * @param  string $orderId
     * @param  int $amount
     * @param  string $trackingCodeOrOrderId
     * @return array
     *
     * @throws Exception
     */

    public function refund(string $orderId, int $amount, string $trackingCode): array
    {
        $params = [
            'providerId' => $orderId,
            'amount' => $amount,
            'saleTrackingCode' => $trackingCode,
        ];

        try {
            $endpoint = sprintf(self::REFUND_URL, $this->getTicketType());
            $response = json_decode($this->curl_post($endpoint, $params), true);
            $data = array_merge($this->getData(), ['refund' => $response]);
            $this->transactionSetData($data);
            return $response;
        } catch (Exception $e) {
            throw $e;
        }

    }
    
    /**
     * refundStatus
     *
     * @param  string $trackingCodeOrOrderId
     * @return array
     * @throws Exception
     */
    public function refundStatus(string $trackingCodeOrOrderId): array
    {
        try {
            $endpoint = sprintf(self::REFUND_STATUS_URL, $trackingCodeOrOrderId);
            return json_decode($this->curl_post($endpoint, []), true);
        } catch (Exception $e) {
            throw $e;
        }

    }
    
    /**
     * curl_post
     *
     * @param  string $url
     * @param  array $params
     * @param  bool $isAccessTokenRequest
     * @return string
     * @throws DigipayException
     */
    function curl_post(string $url, array $params = [], bool $isAccessTokenRequest = false): string
    {
        if ($this->config->get('gateway.digipay.sandbox', false)) {
            $this->baseUrl =  self::SANDBOX_URL;
        }

        $this->oauth();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Agent: WEB',
            'Digipay-Version: ' . self::VERSION,
            'Authorization: Bearer '. $this->accessToken,
            'Content-Type: application/json; charset=UTF-8',
        ]);
        $res = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        if ((int)$info["http_code"] == 401 && !$isAccessTokenRequest) {
            $this->oauth(true);
            return $this->curl_post($url, $params, true);
        }

        if($info["http_code"] == 200 || $info["http_code"] == "200")
            return $res;

        $this->transactionFailed();
        $response = json_decode($res, true);
        if (isset($response['result']['status']) && isset($response['result']['message'])) {
            throw new DigipayException($response['result']['status'], $response['result']['message']);
        }
        if (isset($response['result']['status']) && $response['result']['status'] !== 0) {
            throw new DigipayException($response['result']['status']);
        }
        $this->newLog($info["http_code"], $res);
        throw new DigipayException(-2);
    }

    /** 
     * bool $forgotCache
     * @throws DigipayException
     */
    private function oauth(bool $forgotCache = false): void
    {
        $cacheService = null;
        $cacheKey = $this->cacheKey("digipay_token");

        if ($this->useCache) {
            $cacheService = $this->cacheService();
            if ($cacheService::has($cacheKey)) {
                if (!$forgotCache) {
                    $this->accessToken = $cacheService::get($cacheKey);
                    return;
                }
                $cacheService::forget($cacheKey);
            }
        }

        $params = [
            'username' => $this->config->get('gateway.digipay.username'),
            'password' => $this->config->get('gateway.digipay.password'),
            'grant_type' => 'password',
        ];

        $clientId = $this->config->get('gateway.digipay.client_id');
        $clientSecret = $this->config->get('gateway.digipay.client_secret');
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->baseUrl . self::AUTH_URL);
            curl_setopt($ch, CURLOPT_USERPWD, "{$clientId}:{$clientSecret}");
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic '.base64_encode("{$clientId}:{$clientSecret}"),
            ]);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $ch_error = curl_error($ch);
            curl_close($ch);

            if ($ch_error) {
                throw new DigipayException($httpCode, $ch_error);
            }

            $result = json_decode($response);
        } catch (Exception $ex) {
            throw new DigipayException($httpCode, $ex->getMessage());
        }

        if ($httpCode !== 200) {
            throw new DigipayException($httpCode);
        }

        if (!isset($result->access_token)) {
            throw new DigipayException(-1);
        }
        $this->accessToken = $result->access_token;
        $ttl = 60 *60;
        if (isset($result->expires_in)) {
            $ttl = (int)$result->expires_in;
        }
        if ($this->useCache) {
            $cacheService = $this->cacheService();
            $cacheService::put($cacheKey, $this->accessToken, $ttl);
        }
    }

}
