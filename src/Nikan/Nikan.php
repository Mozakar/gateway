<?php

namespace Mozakar\Gateway\Nikan;

use Exception;
use SoapClient;
use Carbon\Carbon;
use Mozakar\Gateway\PortAbstract;
use Mozakar\Gateway\PortInterface;
use Illuminate\Support\Facades\Request;

class Nikan extends PortAbstract implements PortInterface
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
    protected $serverUrl = 'http://core.nikanbanking.com/api/pardakht/create';
    protected $serverPaymentUrl = 'http://core.nikanbanking.com/api/pardakht/payment';
    protected $serverVerifyUrl = 'http://core.nikanbanking.com/api/pardakht/verify';
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
                'order_id'  => $this->transactionId(),
                'callback'  => $this->getCallback(),
            ];
    
            $request_data = array_merge($main_data, $this->optional_data);

			$response = json_decode($this->curl_post($this->serverUrl, $request_data));
            if($response->status && strtolower($response->message) == 'ok') {
                $this->refId = $response->data->ref_num;
                $this->token = $response->data->token;
                $this->transactionSetRefId();
            } else {
                $this->transactionFailed();
                $this->newLog($response->status, $response->message);
                throw new NikanException($response->status, $response->message);
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

        return \View::make('gateway::nikan-redirector')->with($data)->with('gateUrl',$this->serverPaymentUrl);
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
            $this->callbackUrl = $this->config->get('gateway.nikan.callback-url');

        $url = $this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]);

        return $url;
    }




    /**
     * Verify user payment from bank server
     *
     * @return bool
     *
     * @throws NikanException
     * @throws SoapFault
     */
    protected function verifyPayment()
    {
        $request_data = [
            "ref_num" => $this->refId,
            'amount'  => $this->amount,
        ];
        try{
            $response = json_decode($this->curl_post($this->serverVerifyUrl, $request_data));

            if($response->status && strtolower($response->message) == 'ok' 
                && $response->data->ref_num == $this->refId
                && $response->data->price == $this->amount) {
                $this->transactionSucceed();
                return true;
            } else {
                $this->transactionSetRefId();
                $this->transactionFailed();
                $this->newLog($response->status, $response->message);
                throw new NikanException($response->status, $response->message);
            }
        } catch(Exception $e){
            $this->newLog('Exception', $e->getMessage());
            $this->transactionFailed();
            throw $e;
        }

    }

    function curl_post($url, $params)
    {
        $authorization = $this->config->get('gateway.nikan.token');
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
        curl_close($ch);

        return $res;
    }


}
