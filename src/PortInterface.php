<?php

namespace Mozakar\Gateway;

interface PortInterface
{

    /**
     * Set the payable amount in the selected currency. Default currency is Rial.
     *
     * @param int|float|string $amount
     *
     * @return $this
     */
    public function set($amount);

    /**
     * Set the currency of the amount passed to set() / price().
     * Default is Rial. Call this before ready().
     *
     * @return $this
     */
    public function setCurrency(string $currency);

    /**
     * Currency of the amount passed by the caller.
     */
    public function getCurrency(): string;

    /**
     * This method use for done everything that necessary before redirect to port.
     *
     * @return $this
     */
    public function ready();

    /**
     * Get ref id, in some ports ref id has a different name such as authority
     *
     * @return int|string
     */
    public function refId();

    /**
     * Return tracking code
     *
     * @return int|string
     */
    public function trackingCode();

    /**
     * Get port id, $this->portId
     *
     * @return int
     */
    public function getPortName();

    /**
     * Get transaction id
     *
     * @return int|null
     */
    public function transactionId();

    /**
     * Return card number
     *
     * @return string
     */
    public function cardNumber();

    /**
     * Sets callback url
     *
     * @return string
     */
    public function setCallback($url);

    /**
     * Gets callback url
     *
     * @return string
     */
    public function getCallback();

    /**
     * This method use for redirect to port
     *
     * @return mixed
     */
    public function redirect();

    /**
     * Return result of payment
     * If result is done, return true, otherwise throws an related exception
     *
     * @param object $transaction row of transaction in database
     *
     * @return $this
     */
    public function verify($transaction);
}
