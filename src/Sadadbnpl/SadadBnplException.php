<?php

namespace Mozakar\Gateway\Sadadbnpl;

use Mozakar\Gateway\Exceptions\BankException;

class SadadBnplException extends BankException
{

    public static $errors = [
        -1 => 'خطای ناشناخته, درصورت کسر موجودی طی 72 ساعت موجودی برگشت داده خواهد شد',
        0 => 'عملیات با موفقیت انجام شد',
    ];

    public function __construct($errorRef, $message = "")
    {
        $this->errorRef = $errorRef;
        if (!empty($message)) {
            parent::__construct($message, intval($this->errorRef));
        }else {
            parent::__construct(@self::$errors[$this->errorRef].' ('.$this->errorRef.')', intval($this->errorRef));
        }
    }
}
