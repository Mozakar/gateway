<?php

namespace Mozakar\Gateway\Saman;

use Exception;
use SoapClient;
use Carbon\Carbon;
use Mozakar\Gateway\PortAbstract;
use Mozakar\Gateway\PortInterface;
use Illuminate\Support\Facades\Request;

class Saman extends PortAbstract implements PortInterface
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
    protected $serverUrl = 'https://sep.shaparak.ir/Payment.aspx';
    protected $serverVerifyUrl = "https://verify.sep.ir/Payments/ReferencePayment.asmx?wsdl";
    protected $token_url = "https://sep.shaparak.ir/Payments/InitPayment.asmx?wsdl";
    protected $gateUrl = "https://sep.shaparak.ir/Payment.aspx";
	protected $connection_timeout = 60;
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
			$soap = new SoapClient($this->token_url, [
				'encoding'				=> 'UTF-8',
				'trace'					=> 1,
				'exceptions'			=> 1,
				'connection_timeout'	=> $this->connection_timeout,
			]);
			$token = $soap->RequestToken(
				$this->config->get('gateway.saman.merchant'),
				$this->transactionId(),
				$this->amount
			);
		} catch(\SoapFault $e) {
            $this->newLog('SoapFault', $e->getMessage());
			$this->transactionFailed();
			throw $e;
		} catch(Exception $e){
            $this->newLog('Exception', $e->getMessage());
			$this->transactionFailed();
			throw $e;
		}

		if (is_numeric($token)) {
			$e	= new SamanException($token);
            $this->newLog('Token', $e->getMessage());
			$this->transactionFailed();
			throw $e;
		} else {
			$this->token = $token;
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
            'merchant'      => $this->config->get('gateway.saman.merchant'),
            'token'         => $this->token,
            'resNum'        => $this->transactionId(),
            'callBackUrl'   => $this->getCallback()
        ];

        $data = array_merge($main_data, $this->optional_data);

        return \View::make('gateway::saman-token-redirector')->with($data)->with('gateUrl',$this->gateUrl);
    }

    /**
     * {@inheritdoc}
     */
    public function verify($transaction)
    {
        parent::verify($transaction);

        $this->userPayment();
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
            $this->callbackUrl = $this->config->get('gateway.saman.callback-url');

        $url = $this->makeCallback($this->callbackUrl, ['transaction_id' => $this->transactionId()]);

        return $url;
    }


    /**
     * Check user payment
     *
     * @return bool
     *
     * @throws SamanException
     */
    protected function userPayment()
    {
        $this->trackingCode = Request::input('TRACENO');
        // $this->cardNumber = Request::input('SecurePan'); , will cause mysql error : Data too long for column 'card_number' !
        $payRequestRes = Request::input('State');
        $payRequestResCode = Request::input('Status');

        $this->refId = Request::input('RefNum');
        $this->getTable()->whereId($this->transactionId)->update([
            'ref_id' => $this->refId,
            'tracking_code' => $this->trackingCode,
            // 'card_number' => $this->cardNumber, will cause mysql error : Data too long for column 'card_number' !
            'updated_at' => Carbon::now(),
        ]);

        if ($payRequestRes == 'OK') {
            return true;
        }

        $this->transactionFailed();
        $this->newLog($payRequestResCode, @SamanException::$errors[$payRequestRes]);
        throw new SamanException($payRequestRes);
    }


    /**
     * Verify user payment from bank server
     *
     * @return bool
     *
     * @throws SamanException
     * @throws SoapFault
     */
    protected function verifyPayment()
    {
        $fields = array(
            "merchantID" => $this->config->get('gateway.saman.merchant'),
            "RefNum" => $this->refId,
            "password" => $this->config->get('gateway.saman.password'),
        );

        try {
            $soap = new SoapClient($this->serverVerifyUrl);
            $response = $soap->VerifyTransaction($fields["RefNum"], $fields["merchantID"]);

        } catch (\SoapFault $e) {
            $this->transactionFailed();
            $this->newLog('SoapFault', $e->getMessage());
            throw $e;
        }

        $response = intval($response);

        if ($response == $this->amount) {
            $this->transactionSucceed();
            return true;
        }

        //Reverse Transaction
        if($response>0){
            try {
                $soap = new SoapClient($this->serverVerifyUrl);
                $response = $soap->ReverseTransaction($fields["RefNum"], $fields["merchantID"], $fields["password"], $response);

            } catch (\SoapFault $e) {
                $this->transactionFailed();
                $this->newLog('SoapFault', $e->getMessage());
                throw $e;
            }
        }

        //
        $this->transactionSetRefId();
        $this->transactionFailed();
        $this->newLog($response, SamanException::$errors[$response]);
        throw new SamanException($response);



    }


}
