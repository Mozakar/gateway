<?php

namespace Mozakar\Gateway\Payping;

use Exception;
use Mozakar\Gateway\Currency;
use Mozakar\Gateway\PortAbstract;
use Mozakar\Gateway\PortInterface;

class Payping extends PortAbstract implements PortInterface
{
    protected ?string $callbackConfig = 'gateway.payping.callback-url';

    /**
     * PayPing expects the amount in Toman.
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
    protected $serverUrl = 'https://api.payping.ir/v1/pay';
    protected $serverPaymentUrl = 'https://api.payping.ir/v1/pay/gotoipg/';
    protected $serverVerifyUrl = 'https://api.payping.ir/v1/pay/verify';
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
                throw new PaypingException(99, 'خطا');
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
        $response = $this->http()->postJson($url, $params, [
            $this->authorizationHeader($this->config->get('gateway.payping.token')),
            'Content-Type: application/json',
        ]);

        if ($response->successful()) {
            return $response->body();
        }

        $this->transactionFailed();
        $decoded = $response->json();
        $error = 'error';
        if (isset($decoded->Error)) {
            $error = $decoded->Error;
        } else {
            $decoded = (array) $decoded;
            if (is_array($decoded) && count($decoded)) {
                $firstKey = array_key_first($decoded);
                $this->newLog($firstKey, $decoded[$firstKey]);
                throw new PaypingException($firstKey, $decoded[$firstKey]);
            }
        }

        $this->newLog($response->status(), $error);
        throw new PaypingException($response->status(), $error);
    }


}
