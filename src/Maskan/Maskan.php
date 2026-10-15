<?php

namespace Mozakar\Gateway\Maskan;

use Mozakar\Gateway\Enum;
use Mozakar\Gateway\PortAbstract;
use Mozakar\Gateway\PortInterface;

class Maskan extends PortAbstract implements PortInterface
{
    /**
     * Address of main CURL server
     *
     * @var string
     */
    protected $serverUrl = 'https://fcp.shaparak.ir/NvcService/Api/v2/PayRequest';
    //	protected $serverUrl = 'http://79.174.161.132:8181/NvcService/Api/v2/PayRequest';

    /**
     * Address of CURL server for verify payment
     *
     * @var string
     */
    protected $serverVerifyUrl = 'https://fcp.shaparak.ir/NvcService/Api/v2/Confirm';
    //	protected $serverVerifyUrl = 'http://79.174.161.132:8181/NvcService/Api/v2/PayRequest';
    protected $bankGateUrl;

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
        $url = $this->bankGateUrl;

        return view('gateway::maskan-redirector', compact('url'));
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
     * Send pay request to server
     *
     * @return void
     *
     * @throws MaskanException
     */
    protected function sendPayRequest()
    {
        $this->newTransaction();
        $terminalId   = $this->config->get("gateway.maskan.terminal_id");                    // Terminal ID
        $userName     = $this->config->get("gateway.maskan.USERNAME");                    // Username
        $userPassword = $this->config->get("gateway.maskan.USERPASSWORD");                    // Password
        $orderId      = $this->transactionId();                        // Order ID
        $amount       = $this->getPrice();                    // Price / Rial
        $callBackUrl  = $this->getCallback();    // Callback URL

        $parameters = [
            "PAYMENTID"        => $orderId,
            "CALLBACKURL"      => $callBackUrl,
            "AMOUNT"           => $amount,
            "USERNAME"         => $userName,
            "USERPASSWORD"     => $userPassword,
            "CARDACCEPTORCODE" => $terminalId,
        ];

        $result = $this->http()->postJson($this->serverUrl, $parameters, [
            'Content-Type: application/json',
        ], [
            'verify_host' => null,
            'verify_peer' => true,
        ])->body();

        if ($result == null || $result == "" || !isset($result)) {
            $is_error   = 'yes';
            $error_code = 505;
            $this->transactionFailed();
            $this->newLog($error_code, MaskanException::$errors[$error_code]);

        } else {
            $confirm     = json_decode($result, false);
            $ActionCode  = strval($confirm->ActionCode);
            $RedirectUrl = strval($confirm->RedirectUrl);
            $RefCode     = strval($confirm->RedirectUrl);
            if ($ActionCode == "0") {
                $this->refId = $RefCode;
                $this->transactionSetRefId();
                $this->bankGateUrl = $RedirectUrl;

                return true;
            }
            $this->transactionFailed();
            $this->newLog($ActionCode, MaskanException::$errors[$ActionCode]);
            throw new MaskanException($ActionCode);
        }
    }

    /**
     * Check user payment with GET data
     *
     * @return bool
     *
     * @throws MaskanException
     */

    /**
     * Verify user payment from zarinpal server
     *
     * @return bool
     *
     * @throws MaskanException
     */
    protected function verifyPayment()
    {
        $json           = stripslashes($_POST['Data']);
        $Res            = json_decode($json);
        $transaction_id = strval($Res->RRN);
        $orderId        = strval($Res->PaymentID);
        $fault          = strval($Res->ActionCode);

        $terminalId   = $this->config->get("gateway.maskan.terminal_id");                    // Terminal ID
        $userName     = $this->config->get("gateway.maskan.USERNAME");                    // Username
        $userPassword = $this->config->get("gateway.maskan.USERPASSWORD");                // Password

        if ($fault == 511 || $fault == 519) {
            $this->transactionFailed();
            $this->newLog($fault, MaskanException::$errors[$fault]);
            throw new MaskanException($fault);
        } else {
            if ($fault != 0) {
                $this->transactionFailed();
                $this->newLog($fault, MaskanException::$errors[$fault]);
                throw new MaskanException($fault);
            } else {
                $parameters = [
                    "PAYMENTID"        => $orderId,
                    "CARDACCEPTORCODE" => $terminalId,
                    "RRN"              => $transaction_id,
                    "USERNAME"         => $userName,
                    "USERPASSWORD"     => $userPassword,
                ];

                $result = $this->http()->postJson('https://fcp.shaparak.ir/NvcService/Api/v2/Confirm', $parameters, [
                    'Content-Type: application/json',
                ], [
                    'verify_host' => null,
                    'verify_peer' => true,
                ])->body();
                $result     = iconv('UTF-8', 'UTF-8//IGNORE', mb_convert_encoding($result, 'UTF-8', 'ISO-8859-1'));
                $confirm    = json_decode($result, false);
                $ActionCode = strval($confirm->ActionCode);
                if (json_last_error() != 0 || $confirm == null || $ActionCode == null) {
                    $error_code = 505;
                    $this->transactionFailed();
                    $this->newLog($error_code, MaskanException::$errors[$error_code]);
                    throw new MaskanException($ActionCode);
                } else {
                    if ($ActionCode == "511" || $ActionCode == "519") {
                        $this->transactionFailed();
                        $this->newLog($ActionCode, MaskanException::$errors[$ActionCode]);
                        throw new MaskanException($ActionCode);
                    } else {
                        if ($ActionCode != "0") {
                            $this->transactionFailed();
                            $this->newLog($ActionCode, MaskanException::$errors[$ActionCode]);
                            throw new MaskanException($ActionCode);
                        } else {
                            $this->trackingCode = $confirm->RRN;
                            $this->transactionSucceed();
                            $this->newLog(1, Enum::TRANSACTION_SUCCEED_TEXT);

                            return true;
                        }
                    }
                }
            }
        }
    }
}
