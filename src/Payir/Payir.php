<?php
namespace Mozakar\Gateway\Payir;

use Illuminate\Support\Facades\Request;
use Mozakar\Gateway\Enum;
use Mozakar\Gateway\PortAbstract;
use Mozakar\Gateway\PortInterface;

class Payir extends PortAbstract implements PortInterface
{
    protected ?string $callbackConfig = 'gateway.payir.callback-url';

    protected bool $encodeCallback = true;

    /**
     * Address of main CURL server
     *
     * @var string
     */
    protected $serverUrl = 'https://pay.ir/payment/send';

    /**
     * Address of CURL server for verify payment
     *
     * @var string
     */
    protected $serverVerifyUrl = 'https://pay.ir/payment/verify';
    /**
     * Address of gate for redirect
     *
     * @var string
     */
    protected $gateUrl = 'https://pay.ir/payment/gateway/';


    protected $factorNumber;

    /**
     * تعیین شماره فاکتور (اختیاری)
     *
     * @param $factorNumber
     *
     * @return $this
     */
    public function setFactorNumber($factorNumber)
    {
        $this->factorNumber = $factorNumber;
        return $this;
    }

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
        return redirect()->to($this->gateUrl . $this->refId);
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
     * @throws PayirSendException
     */
    protected function sendPayRequest()
    {
        $this->newTransaction();
        $fields = [
            'api'      => $this->config->get('gateway.payir.api'),
            'amount'   => $this->amount,
            'redirect' => $this->getCallback(),
        ];

        if (isset($this->factorNumber))
            $fields['factorNumber'] = $this->factorNumber;

        $response = $this->http()->post($this->serverUrl, http_build_query($fields), [], [
            'verify_host' => null,
        ])->json(true);
        if (is_numeric($response['status']) && $response['status'] > 0) {
            $this->refId = $response['transId'];
            $this->transactionSetRefId();
            return true;
        }
        $this->transactionFailed();
        $this->newLog($response['errorCode'], PayirSendException::$errors[ $response['errorCode'] ]);
        throw new PayirSendException($response['errorCode']);
    }

    /**
     * Check user payment with GET data
     *
     * @return bool
     *
     * @throws PayirReceiveException
     */
    protected function userPayment()
    {
        $status = Request::input('status');
        $transId = Request::input('transId');
        $this->cardNumber = Request::input('cardNumber');
        $message = Request::input('message');
        if (is_numeric($status) && $status > 0) {
            $this->trackingCode = $transId;
            return true;
        }
        $this->transactionFailed();
        $this->newLog(-5, $message);
        throw new PayirReceiveException(-5);
    }

    /**
     * Verify user payment from zarinpal server
     *
     * @return bool
     *
     * @throws PayirReceiveException
     */
    protected function verifyPayment()
    {
        $fields = [
            'api'     => $this->config->get('gateway.payir.api'),
            'transId' => $this->refId(),
        ];
        $response = $this->http()->post($this->serverVerifyUrl, http_build_query($fields), [], [
            'verify_host' => null,
        ])->json(true);
        if ($response['status'] == 1) {
            $this->transactionSucceed();
            $this->newLog(1, Enum::TRANSACTION_SUCCEED_TEXT);
            return true;
        }

        $this->transactionFailed();
        $this->newLog($response['errorCode'], PayirReceiveException::$errors[ $response['errorCode'] ]);
        throw new PayirReceiveException($response['errorCode']);
    }
}
