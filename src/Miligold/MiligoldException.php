<?php

namespace Mozakar\Gateway\Miligold;

use Mozakar\Gateway\Exceptions\BankException;

class MiligoldException extends BankException
{
    public static $errors = [
        100 => 'عملیات با موفقیت انجام شد',
        1164 => 'ترمینال یافت نشد.',
        1000 => 'درخواست غیر مجاز',
        1104 => 'شماره موبایل معتبر نمی باشد',
        1177 => 'کد رهگیری تکراری می باشد',
        1007 => 'نام کاربر و یا رمز عبور اشتباه است',
        1159 => 'api key موجود نیست',
        1160 => 'api key نامعتبر است',
        1161 => 'api key غیرفعال است',
        1183 => 'وضعیت تراکنش معتبر نمی باشد.',
        
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
