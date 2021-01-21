<?php

namespace Mozakar\Gateway\PayPing;

use Exception;
use Mozakar\Gateway\PortAbstract;
use Mozakar\Gateway\PortInterface;

class PayPing extends PortAbstract implements PortInterface
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
    protected $serverUrl = 'https://api.payping.ir/v1/pay';
    protected $serverPaymentUrl = 'https://api.payping.ir/v1/pay/gotoipg/';
    protected $serverVerifyUrl = 'https://api.payping.ir/v1/pay/verify';
	protected $token;

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

    public function gatewayPay()
	{
		try{

            $main_data = [
                'amount'    => $this->amount,
                'clientRefId'  => $this->transactionId(),
                'returnUrl'  => $this->getCallback(),
            ];

            $request_data = array_merge($main_data, $this->optional_data);

			$response = json_decode($this->curl_post($this->serverUrl, $request_data));
            if(isset($response->code)) {
                $this->token = $response->code;
                $this->refId = $this->transactionId();
                $this->transactionSetRefId();
            } else {
                $this->transactionFailed();
                $this->newLog(99, 'خطا');
                throw new PayPingException(99, 'خطا');
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
        return redirect($this->serverPaymentUrl . $this->token);
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
     * Verify user payment from bank server
     *
     * @return bool
     *
     * @throws PayPingException
     * @throws SoapFault
     */
    protected function verifyPayment()
    {
        $refId = isset($_GET['refid']) ? $_GET['refid'] : 1;
        $request_data = [
            "refId" => $refId,
            'amount'  => $this->amount,
        ];
        try{
            $this->curl_post($this->serverVerifyUrl, $request_data);
            $this->transactionSucceed();
            return true;
        } catch(Exception $e){
            $this->newLog('Exception', $e->getMessage());
            $this->transactionFailed();
            throw $e;
        }

    }

    function curl_post($url, $params)
    {
        $authorization = $this->config->get('gateway.payping.token');
        $authorization = str_replace("Bearer ", "", $authorization);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $authorization,
            'Content-Type: application/json',
        ]);
        $res = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if($info["http_code"] == 200 || $info["http_code"] == "200")
            return $res;

        $this->transactionFailed();
        $response = json_decode($res);
        $error = "error";
        if(isset($response->Error)) {
            $error = $response->Error;
        }else{
            $response = (array)$response;
            if(is_array($response) && count($response)){
                $firstKey = array_key_first($response);
                $this->newLog($firstKey, $response[$firstKey]);
                throw new PayPingException($firstKey, $response[$firstKey]);
            }
        }

        $this->newLog($info["http_code"], $error);
        throw new PayPingException($info["http_code"], $error);
    }


}
