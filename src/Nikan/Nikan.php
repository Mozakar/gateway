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
    protected ?string $callbackConfig = 'gateway.nikan.callback-url';

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

        return view('gateway::nikan-redirector', array_merge($data, ['gateUrl' => $this->serverPaymentUrl]));
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
        return $this->http()->postJson($url, $params, [
            $this->authorizationHeader($this->config->get('gateway.nikan.token')),
            'Content-Type: application/json',
        ])->body();
    }


}
