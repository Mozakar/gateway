<?php

namespace Mozakar\Gateway\Apsan;

use Exception;
use Mozakar\Gateway\PortAbstract;
use Mozakar\Gateway\PortInterface;
use Illuminate\Support\Facades\Request;

class Apsan extends PortAbstract implements PortInterface
{
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
    protected $serverUrl = 'http://core.Apsanbanking.com/api/pardakht/create';
    protected $serverPaymentUrl = 'https://pay.apsan.co/v1/payment';
    protected $serverTokenUrl = 'https://pay.cpg.ir/api/v1/Token';
    
    protected $serverVerifyUrl = 'https://pay.cpg.ir/api/v1/payment/acknowledge';
	protected $token;

    private $errorMessage = "خطایی رخ داده است.";

    private $uniqueIdentifier = "";

    protected function authHeader($AsArray=false){
        $basic=  $this->config->get('gateway.apsan.username').":". $this->config->get('gateway.apsan.password');
        $basic = base64_encode($basic);
        return $AsArray ?  [ 'Authorization: Basic '.$basic ]  : 'Authorization: Basic '.$basic;
    }

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
    public function ready()
    {
        $this->newTransaction();
        $this->redirect();
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

    protected function getToken(){
        $this->uniqueIdentifier = rand(0, 99999) . (string)time() . rand(0, 99999);

        $data = [
            "amount"            => $this->amount,
            "redirectUri"       => $this->getCallback(),
            "terminalId"        => $this->config->get('gateway.apsan.terminalId'),
            "uniqueIdentifier"  => $this->uniqueIdentifier
        ];
    
        $res = $this->curl_post($this->serverTokenUrl, $data);
        
        $decoded = @json_decode($res);
        if(!$decoded){
            $this->transactionFailed();
            $this->newLog(-1, $res);
            throw new Exception($this->errorMessage, -1);
        }

        if(!isset($decoded->result)){
            $this->transactionFailed();
            $this->newLog(-2, $res);
            throw new Exception($this->errorMessage, -2);
        }
        return $decoded->result;
    }

    public function gatewayPay()
	{
		try{
            $token = $this->getToken();
            if(!empty($token)) {
                $this->token = $token;
                $this->transactionSetData(['token' => $this->token, 'uniqueIdentifier' => $this->uniqueIdentifier]);
            } else {
                $this->transactionFailed();
                $this->newLog(-3, $this->errorMessage);
                throw new Exception($this->errorMessage, -3);
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
        $this->gatewayPay();

        $main_data = [
            'amount'        => $this->amount,
            'token'         => $this->token,
            'resNum'        => $this->transactionId(),
            'callBackUrl'   => $this->getCallback()
        ];

        $data = array_merge($main_data, $this->optional_data);

        return \View::make('gateway::apsan-redirector')->with($data)->with('gateUrl',$this->serverPaymentUrl);
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
            $this->callbackUrl = $this->config->get('gateway.apsan.callback-url');

        $url = $this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]);

        return $url;
    }




    /**
     * Verify user payment from bank server
     *
     * @return bool
     *
     * @throws apsanException
     */
    protected function verifyPayment()
    {
        $data = $this->getData();
        if(!isset($data['token']) && !isset($data['_token'])) {
            $this->newLog(-7, "token not found");
            throw new Exception(-7, $this->errorMessage);
        }
        $request_data = [
            "token" => isset($data['token']) ? $data['token'] : $data['_token'],
        ];
        try{
            $this->transactionSetData(Request::all());
            $this->trackingCode = Request::has('grantId') ? Request::get('grantId') :  $this->trackingCode;
            $response = json_decode($this->curl_post($this->serverVerifyUrl, $request_data));
            if(isset($response->result) && $response->result->acknowledged) {
                $this->transactionSucceed();
                return true;
            } else {
                $this->transactionFailed();
                $this->newLog(-4, json_encode($response));
                throw new Exception(-4, $this->errorMessage);
            }
        } catch(Exception $e){
            $this->newLog('Exception', $e->getMessage());
            $this->transactionFailed();
            throw $e;
        }

    }

    function curl_post($url, $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            $this->authHeader(),
            'accept: application/json',
            'Content-Type: application/json',
        ]);
        $res = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if($info["http_code"] == 200 || $info["http_code"] == "200")
            return $res;

        $this->transactionFailed();
       
        $this->newLog($info["http_code"], $res);
        throw new Exception($this->errorMessage, $info["http_code"]);
    }


}
