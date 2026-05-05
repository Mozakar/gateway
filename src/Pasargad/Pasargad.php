<?php

namespace Mozakar\Gateway\Pasargad;

use Exception;
use Mozakar\Gateway\PortAbstract;
use Mozakar\Gateway\PortInterface;
use Mozakar\Gateway\Pasargad\PasargadException;

class Pasargad extends PortAbstract implements PortInterface
{

    private string $baseUrl = 'https://pep.shaparak.ir/dorsa1/';
    private const AUTH_PATH = 'token/getToken';
    private const PURCHASE_PATH = 'api/payment/purchase';

    private const VERIFY_PATH = 'api/payment/verify-transactions';


    private const CACHE_KEY = 'pasargad_token';

	protected string $token;
    protected string $accessToken;
    protected string $paymentUrl;

    private string $serviceCode = '8';
    private string $serviceType = 'PURCHASE';
    private string $payerMail = '';
    private string $payerName = '';
    private string $pans = '';
    private string $nationalCode = '';
    private string $invoiceDate = '';

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

    public function setServiceCode (string $serviceCode): self
    {
        $this->serviceCode = $serviceCode;
        return $this;
    }

    public function getServiceCode():string
    {
        return $this->serviceCode;
    }

   
    public function setServiceType(string $serviceType):self
    {
        $this->serviceType = $serviceType;
        return $this;
    }

    public function getServiceType():string
    {
        return $this->serviceType;
    }

    public function setPayerMail (string $payerMail): self
    {
        $this->payerMail = $payerMail;
        return $this;
    }

    public function getPayerMail():string
    {
        return $this->payerMail;
    }
    public function setPayerName (string $payerName): self
    {
        $this->payerName = $payerName;
        return $this;
    }

    public function getPayerName():string
    {
        return $this->payerName;
    }
    public function setPans (string $pans): self
    {
        $this->pans = $pans;
        return $this;
    }

    public function getPans():string
    {
        return $this->pans;
    }
    public function setNationalCode (string $nationalCode): self
    {
        $this->nationalCode = $nationalCode;
        return $this;
    }

    public function getNationalCode():string
    {
        return $this->nationalCode;
    }
    public function setInvoiceDate (string $invoiceDate): self
    {
        $this->invoiceDate = $invoiceDate;
        return $this;
    }

    public function getInvoiceDate():string
    {
        if (empty($this->invoiceDate)) {
            return date('Y-m-d');
        }
        return $this->invoiceDate;
    }

    private function purchase()
	{
        $params = [
            'invoice' => $this->transactionId(),
            'invoiceDate' => $this->getInvoiceDate(),
            'mobileNumber' => $this->mobile,
            'amount' => $this->amount,
            'serviceCode' => $this->getServiceCode(),
            'serviceType' => $this->getServiceType(),
            'terminalNumber' => $this->config->get('gateway.pasargad.terminalId'),
            'description' => '',
            'payerMail' => $this->getPayerMail(),
            'payerName' => $this->getPayerName(),
            'pans' => $this->getPans(),
            'nationalCode' => $this->getNationalCode(),
            'callbackApi' => $this->getCallback(),
        ];


		try{
            $response = json_decode($this->curl_post(self::PURCHASE_PATH, $params), true);
            if(isset($response['resultCode']) &&  $response['resultCode'] == 0 && isset($response['data']['url'])) {
                $this->token = $response['data']['urlId'];
                $this->refId = $response['data']['urlId'];
                $this->paymentUrl = $response['data']['url'];
                $this->transactionSetRefId();
            } else {
                if (isset($response['resultCode'])) {
                    $this->newLog($response['resultCode'], 'خطا');
                    $this->transactionFailed();
                    throw new PasargadException($response['resultCode']);
                }
                $this->newLog(-3, 'خطا');
                $this->transactionFailed();
                throw new PasargadException(-3, 'خطا');
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
            $this->callbackUrl = $this->config->get('gateway.pasargad.callback-url');

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
            $callbackData = $this->getRequest()->all();
            $data = array_merge($this->getData(), ['callback_data' => $callbackData]);
            if ($callbackData['status'] != "success") {
                $this->transactionSetData($data);
                $this->transactionFailed();
                throw new PasargadException(1);
            }
            $params = [
                'checkVerify' => true,
                'invoice' => (string)$this->transactionId(),
                'urlId' => $this->refId(),
            ];

            $response = json_decode($this->curl_post(self::VERIFY_PATH, $params), true);
            if (isset($response['resultCode']) && $response['resultCode'] == 0) {
                $data = array_merge($data, ['verify' => $response]);
                $this->transactionSetData($data);
                $this->transactionSucceed();
                return true;
            }
            if (isset($response['resultCode'])) {
                $this->transactionFailed();
                throw new PasargadException($response['resultCode']);
            }
            $this->transactionFailed();
            throw new PasargadException(-1);
           
        } catch(Exception $e){
            $this->newLog('Exception', $e->getMessage());
            $this->transactionFailed();
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
     * @throws PasargadException
     */
    function curl_post(string $url, array $params = [], bool $isAccessTokenRequest = false, bool $log = true): string
    {
        $this->oauth();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
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

        if ($log) {
            $this->transactionFailed();
        }
        $response = json_decode($res, true);
        if (isset($response['resultCode'])) {
            $code = isset($response['resultCode']) ? $response['resultCode'] : -1;
            throw new PasargadException($code);
        }
        if ($log) {
            $this->newLog($info["http_code"], $res);
        }
        throw new PasargadException(-1);
    }

    /** 
     * bool $forgotCache
     * @throws PasargadException
     */
    private function oauth(bool $forgotCache = false): void
    {
        $cacheService = null;
        $cacheKey = $this->cacheKey(self::CACHE_KEY);

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
            'username' => $this->config->get('gateway.pasargad.username'),
            'password' => $this->config->get('gateway.pasargad.password'),
        ];

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->baseUrl . self::AUTH_PATH);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type' => 'application/json'
            ]);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $ch_error = curl_error($ch);
            curl_close($ch);

            if ($ch_error) {
                throw new PasargadException($httpCode, $ch_error);
            }

            $result = json_decode($response);
        } catch (Exception $ex) {
            throw new PasargadException($httpCode, $ex->getMessage());
        }

        if ($httpCode !== 200) {
            throw new PasargadException($httpCode);
        }

        if (!isset($result->token)) {
            throw new PasargadException(-1);
        }
        $this->accessToken = $result->token;
        $ttl = 60 *5;
        if ($this->useCache) {
            $cacheService = $this->cacheService();
            $cacheService::put($cacheKey, $this->accessToken, $ttl);
        }
    }

}
