<?php

namespace Mozakar\Gateway\Digipay;

use DateTime;
use Exception;
use Mozakar\Gateway\PortAbstract;
use Mozakar\Gateway\PortInterface;
use Mozakar\Gateway\Digipay\DigipayException;

class Digipay extends PortAbstract implements PortInterface
{
    protected ?string $callbackConfig = 'gateway.digipay.callback-url';

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

    private const VERIFY_URL = 'purchases/verify/?type=%s';

    private const DELIVER_URL = 'purchases/deliver?type=%s';

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
    function setOptionalData (array $data): self
    {
        $this->optional_data = $data;
        return $this;
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

    protected function callbackQuery(): array
    {
        return ['providerId' => $this->getOrderId()];
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
            $providerId = $callbackData['providerId'] ?? '';
            $params = [
                'trackingCode' =>  $this->trackingCode,
                'providerId' =>  $providerId,
            ];
            $endpoint = sprintf(self::VERIFY_URL, $type);
            $response = json_decode($this->curl_post($endpoint, $params), true);
            if (isset($response['result']['status']) && $response['result']['status'] !== 0) {
                throw new DigipayException($response['result']['status']);
            }
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

    public function deliver(string $trackingCode, string $invoiceNumber, array $products, ?DateTime $deliverTime = null): array
    {
        if (is_null($deliverTime)) {
            $deliverTime = new DateTime();
        }

        $params = [
            'deliveryDate' => $deliverTime->format('Uv'),
            'invoiceNumber' => $invoiceNumber,
            'trackingCode' => $trackingCode,
            'products' => $products,
        ];

        try {
            $endpoint = sprintf(self::DELIVER_URL, $this->getTicketType());
            $response = json_decode($this->curl_post($endpoint, $params, false, false), true);
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

    public function refund(string $orderId, int $amount, string $trackingCode): array
    {
        $params = [
            'providerId' => $orderId,
            'amount' => $amount,
            'saleTrackingCode' => $trackingCode,
        ];

        try {
            $endpoint = sprintf(self::REFUND_URL, $this->getTicketType());
            $response = json_decode($this->curl_post($endpoint, $params, false, false), true);
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
            return json_decode($this->curl_post($endpoint, [], false, false), true);
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
    function curl_post(string $url, array $params = [], bool $isAccessTokenRequest = false, bool $log = true): string
    {
        if ($this->config->get('gateway.digipay.sandbox', false)) {
            $this->baseUrl =  self::SANDBOX_URL;
        }

        $this->oauth();

        $response = $this->http()->postJson($this->baseUrl . $url, $params, [
            'Agent: WEB',
            'Digipay-Version: ' . self::VERSION,
            'Authorization: Bearer '. $this->accessToken,
            'Content-Type: application/json; charset=UTF-8',
        ]);

        if ($response->status() === 401 && !$isAccessTokenRequest) {
            $this->oauth(true);
            return $this->curl_post($url, $params, true);
        }

        if ($response->successful()) {
            return $response->body();
        }

        if ($log) {
            $this->transactionFailed();
        }
        $decoded = $response->json(true);
        if (isset($decoded['result']['status']) && isset($decoded['result']['message'])) {
            throw new DigipayException($decoded['result']['status'], $decoded['result']['message']);
        }
        if (isset($decoded['result']['status']) && $decoded['result']['status'] !== 0) {
            throw new DigipayException($decoded['result']['status']);
        }
        if ($log) {
            $this->newLog($response->status(), $response->body());
        }
        throw new DigipayException(-2);
    }

    /** 
     * bool $forgotCache
     * @throws DigipayException
     */
    private function oauth(bool $forgotCache = false): void
    {
        $this->accessToken = $this->rememberToken(
            $this->cacheKey('digipay_token'),
            $forgotCache,
            function (): array {
                $params = [
                    'username' => $this->config->get('gateway.digipay.username'),
                    'password' => $this->config->get('gateway.digipay.password'),
                    'grant_type' => 'password',
                ];
                $clientId = $this->config->get('gateway.digipay.client_id') ?: $this->config->get('gateway.digipay.clientId');
                $clientSecret = $this->config->get('gateway.digipay.client_secret') ?: $this->config->get('gateway.digipay.clientSecret');
                $httpCode = 0;

                try {
                    $response = $this->http()->post(
                        $this->baseUrl . self::AUTH_URL,
                        $params,
                        [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Basic ' . base64_encode("{$clientId}:{$clientSecret}"),
                        ],
                        [
                            'userpwd' => "{$clientId}:{$clientSecret}",
                            'verify_host' => null,
                        ]
                    );
                    $httpCode = $response->status();

                    if ($response->error() !== '') {
                        throw new DigipayException($httpCode, $response->error());
                    }

                    $result = $response->json();
                } catch (Exception $ex) {
                    throw new DigipayException($httpCode, $ex->getMessage());
                }

                if ($httpCode !== 200) {
                    throw new DigipayException($httpCode);
                }

                if (!isset($result->access_token)) {
                    throw new DigipayException(-1);
                }

                $ttl = isset($result->expires_in) ? (int) $result->expires_in : 60 * 60;

                return [$result->access_token, $ttl];
            }
        );
    }

}
