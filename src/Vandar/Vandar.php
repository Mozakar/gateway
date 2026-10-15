<?php

namespace Mozakar\Gateway\Vandar;

use Mozakar\Gateway\Currency;
use Mozakar\Gateway\Http\HttpResponse;
use Mozakar\Gateway\PortAbstract;
use Mozakar\Gateway\PortInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;

class Vandar extends PortAbstract implements PortInterface
{
    protected ?string $callbackConfig = 'gateway.vandar.callback-url';

    /**
     * Vandar expects the amount in Toman.
     */
    protected string $gatewayCurrency = Currency::TOMAN;

    /**
     *
     * @var Array $optional_data An array of optional data
     *  that will be sent with the payment request
     *
     */
    protected $optional_data = [];

    /**
     * Address of main SOAP server
     *
     * @var string
     */

    protected $serverUrl = 'https://ipg.vandar.io/api/v3/send';
    protected $serverPaymentUrl = 'https://ipg.vandar.io/v3/';
    protected $serverVerifyStep2Url = 'https://vandar.io/api/ipg/2step/transaction';
    protected $serverVerifyUrl = 'https://ipg.vandar.io/api/v3/verify';
    protected $businessBaseUrl = 'https://api.vandar.io/v2/business/';
    protected $serverIBanRequestUrl = 'https://api.vandar.io/v3/business/';
    protected $serverLoginUrl = 'https://api.vandar.io/v3/login';
    protected $token;

    /**
     * {@inheritdoc}
     */
    public function ready()
    {
        $this->newTransaction();

        return $this;
    }

    /**
     *
     * Add optional data to the request
     *
     * @param Array $data an array of data
     *
     */
    function setOptionalData (Array $data)
    {
        $this->optional_data = $data;
    }

    public function gatewayPay()
	{

        $params = [
            'api_key' => $this->config->get('gateway.vandar.api_key'),
            'amount' => $this->amount,
            'callback_url' => $this->getCallback(),
            'mobile_number' => $this->mobile,
            'factorNumber' => $this->factorNumber,
            'description' => $this->description,
        ];

        if($this->cardNumber != null && strlen($this->cardNumber) == 16){
            $params['valid_card_number'] = $this->cardNumber;
        }

        $response = $this->curl_post($this->serverUrl, $params);
        $result = json_decode($response);
        if(isset($result->token)){
            $this->token = $result->token;
            return $this;
        }
        if(isset($result->errors))
        {
			$this->transactionFailed();
            throw new VandarException($this->formatErrors($result->errors));
        }

        $this->transactionFailed();
        throw new VandarException($response);
	}

    /**
     * {@inheritdoc}
     */
    public function redirect()
    {
        $this->gatewayPay();
        return redirect($this->serverPaymentUrl . $this->token);
    }

    public  function setp2Verify()
    {
        $token = Request::input('token');
        $params = [
            'api_key' => $this->config->get('gateway.vandar.api_key'),
            'token' => $token,
        ];

        $response = $this->curl_post($this->serverVerifyStep2Url, $params);
        $result = json_decode($response);
        if(isset($result->status) && $result->status == 1){
            return $result;
        }

        if(isset($result->errors))
        {
			$this->transactionFailed();
            throw new VandarException($this->formatErrors($result->errors));
        }

        $this->transactionFailed();
        throw new VandarException($response);
    }

    /**
     * {@inheritdoc}
     */
    public function verify($transaction)
    {
        parent::verify($transaction);

        $this->setp2Verify();
        $this->verifyPayment();

        return $this;
    }

    /**
     * Verify user payment from bank server
     *
     * @return bool
     *
     * @throws VandarException
     * @throws SoapFault
     */
    protected function verifyPayment()
    {
        $token = Request::input('token');
        $params = [
            'api_key' => config('gateway.vandar.api_key'),
            'token' => $token,
        ];

        $response = $this->curl_post($this->serverVerifyUrl, $params);
        $result = json_decode($response);
        if(isset($result->status) && $result->status == 1){
            $this->transactionSucceed();
            return true;
        }
        if(isset($result->errors))
        {
            $this->transactionSetRefId();
            $this->transactionFailed();
            throw new VandarException($this->formatErrors($result->errors));
        }

        $this->transactionSetRefId();
        $this->transactionFailed();
        throw new VandarException($response);
    }




    public function login()
    {
        if(Cache::has('gateway_vandar_access_token'))
            return Cache::get('gateway_vandar_access_token');

        $params = [
            'mobile' =>   config('gateway.vandar.username'),
            'password' => config('gateway.vandar.password')
        ];

        $response = $this->curl_post($this->serverLoginUrl, $params);
        $result = json_decode($response);

        if(isset($result->access_token)){
            $access_token = $result->access_token;
            Cache::put('gateway_vandar_access_token', $access_token, now()->addDays(4));
            return $access_token;
        }

        if(isset($result->errors))
            throw new VandarException($this->formatErrors($result->errors));

        if(isset($result->error))
            throw new VandarException($result->error);

        throw new VandarException($response);
    }

    public function ibanRequest($amount, $iban, $track_id, $payment_number, $is_instant = true)
    {
        $access_token = $this->login();

        $params = [
            'amount' => $amount,
            'iban' => $iban,
            'track_id' => $track_id,
            'payment_number' => $payment_number,
            'is_instant' => $is_instant,
        ];

        $url = $this->serverIBanRequestUrl . config('gateway.vandar.company') .'/settlement/store';
        $response = $this->curl_post($url, $params, $access_token);
        $result = json_decode($response);
        if(isset($result->status) && $result->status == 1){
            return $result;
        }

        if(isset($result->errors))
            throw new VandarException($this->formatErrors($result->errors));

        throw new VandarException($response);
    }


    /**
     * balance
     *
     * @return mixed
     */
    public function balance()
    {
        $url = $this->businessBaseUrl . config('gateway.vandar.company') . '/balance';
        $access_token = $this->login();
        $response = $this->curl_get($url, $access_token);
        $result = json_decode($response);
        if(isset($result->status) && $result->status == 1){
            return $result;
        }

        if(isset($result->errors))
            throw new VandarException($this->formatErrors($result->errors));

        throw new VandarException($response);
    }

    public function transactions()
    {
        $url = $this->businessBaseUrl . config('gateway.vandar.company') . '/transaction?per_page=30&page=1';
        $access_token = $this->login();
        $response = $this->curl_get($url, $access_token);
        $result = json_decode($response);
        if(isset($result->status) && $result->status == 1){
            return $result;
        }

        if(isset($result->errors))
            throw new VandarException($this->formatErrors($result->errors));

        throw new VandarException($response);

    }


    public function curl_get($url, $authorization = '')
    {
        return $this->vandarResponse($this->http()->get($url, $this->vandarHeaders($authorization)));
    }

    public function curl_post($url, $params, $authorization = '')
    {
        return $this->vandarResponse($this->http()->postJson($url, $params, $this->vandarHeaders($authorization)));
    }

    /**
     * @return string[]
     */
    private function vandarHeaders($authorization): array
    {
        $headers = ['Content-Type: application/json'];
        if ($authorization != '') {
            $headers[] = 'Authorization: Bearer ' . $authorization;
        }

        return $headers;
    }

    private function vandarResponse(HttpResponse $response): string
    {
        if ($response->successful()) {
            return $response->body();
        }

        $result = $response->json();

        if (isset($result->error) && isset($result->errors)) {
            throw new VandarException($result->error . ', ' . $this->formatErrors($result->errors));
        }

        if (isset($result->error)) {
            throw new VandarException($result->error);
        }

        if (isset($result->errors)) {
            throw new VandarException($this->formatErrors($result->errors));
        }

        throw new VandarException($response->body());
    }

    private function formatErrors($errors){
        return implode(', ', array_map(function ($entry) {
                                if(is_array($entry))
                                    return $this->formatErrors($entry);
                                return $entry;
                }, (array)$errors));
    }


}