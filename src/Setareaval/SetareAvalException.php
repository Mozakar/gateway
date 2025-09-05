<?php

namespace Mozakar\Gateway\Setareaval;

use Mozakar\Gateway\Exceptions\BankException;

class SetareAvalException extends BankException
{
    public static $errors = [
        100 => 'عملیات با موفقیت انجام شد',
        101 => 'عدم وجود وام فعال برای کاربر',
        102 => 'Merchant  یافت نشد',
        103 => 'Merchant  غیرفعال است',
        104 => 'Merchant  نامعتبر است',
        105 => 'مرچنت و نام کاربری همخوانی ندارد.',
        106 => 'مبلغ تراکنش کمتر از حد مجاز است',
        107 => 'callbackUrl نامعتبر می‌باشد. (شروع با http و یا https)',
        108 => 'محصولات ارسال نشده است',
        113 => 'مبلغ تراکنش بیشتر از حد مجاز است',
        114 => 'شناسه پیگری برای فروشگاه نمیباشد',
        115 => 'شناسه پیگیری یافت نشد',
        116 => 'فاکتور پرداخت نشده است.',
        117 => 'پرداخت یافت نشد',
        118 => 'برگشت انجام نشد',
        120 => 'خطای ناشناخته',
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
