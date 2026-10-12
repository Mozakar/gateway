<?php

namespace Mozakar\Gateway\Exceptions;
/**
 * This exception when throws, user try to submit a payment request who submitted before
 */
#[\AllowDynamicProperties]
class BankException extends \Exception
{
	protected $code=-100;
	protected $message = 'خطای بانک.';

	/**
	 * Bank codes are sometimes numeric strings. PHP 8 requires Exception::$code to be an int.
	 *
	 * @param string|null $message
	 * @param mixed $code
	 * @param \Throwable|null $previous
	 */
	public function __construct($message = '', $code = 0, ?\Throwable $previous = null)
	{
		parent::__construct((string) ($message ?? ''), (int) ($code ?? 0), $previous);
	}
}
