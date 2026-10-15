<?php

namespace Mozakar\Gateway\JahanPay;

use Illuminate\Support\Facades\Request;
use Mozakar\Gateway\Currency;
use Mozakar\Gateway\Enum;
use SoapClient;
use Mozakar\Gateway\PortAbstract;
use Mozakar\Gateway\PortInterface;

class JahanPay extends PortAbstract implements PortInterface
{
    protected ?string $callbackConfig = 'gateway.jahanpay.callback-url';

    /**
     * JahanPay expects the amount in Toman.
     */
    protected string $gatewayCurrency = Currency::TOMAN;

    /**
     * Address of main SOAP server
     *
     * @var string
     */
    protected $serverUrl = 'http://www.jahanpay.com/webservice?wsdl';

    /**
     * Address of gate for redirect
     *
     * @var string
     */
    protected $gateUrl = 'http://www.jahanpay.com/pay_invoice/';

    /**
     * {@inheritdoc}
     */
    public function ready()
    {
        $this->sendPayRequest();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function redirect()
    {
        return redirect()->to($this->gateUrl.$this->refId());
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
     * Send pay request to server
     *
     * @return void
     *
     * @throws JahanPayException
     */
    protected function sendPayRequest()
    {
        $this->newTransaction();

        try {
            $soap = new SoapClient($this->serverUrl);
            $response = $soap->requestpayment(
                $this->config->get('gateway.jahanpay.api'),
                $this->amount,
                $this->getCallback(),
                $this->transactionId(),
                ''
            );

        } catch(\SoapFault $e) {
            $this->transactionFailed();
            $this->newLog('SoapFault', $e->getMessage());
            throw $e;
        }

        if (intval($response) >= 0) {
            $this->refId = $response;
            $this->transactionSetRefId();
            return true;
        }

        $this->transactionFailed();
        $this->newLog($response, JahanPayException::$errors[$response]);
        throw new JahanPayException($response);
    }

    /**
     * Check user payment
     *
     * @return bool
     *
     * @throws JahanPayException
     */
    protected function userPayment()
    {
        $refId = Request::input('au');

        if ($this->refId() != $refId) {
            $this->transactionFailed();
            $this->newLog(-30, JahanPayException::$errors[-30]);
            throw new JahanPayException(-30);
        }

        return true;
    }

    /**
     * Verify user payment from bank server
     *
     * @return bool
     *
     * @throws JahanPayException
     */
    protected function verifyPayment()
    {
        try {
            $soap = new SoapClient($this->serverUrl);
            $response = $soap->verification(
                $this->config->get('gateway.jahanpay.api'),
                $this->amount,
                $this->refId
            );

        } catch(\SoapFault $e) {
            $this->transactionFailed();
            $this->newLog('SoapFault', $e->getMessage());
            throw $e;
        }

        if (intval($response) == 1) {
            $this->transactionSucceed();
            $this->newLog($response, Enum::TRANSACTION_SUCCEED_TEXT);
            return true;
        }

        $this->transactionFailed();
        $this->newLog($response, JahanPayException::$errors[$response]);
        throw new JahanPayException($response);
    }
}
