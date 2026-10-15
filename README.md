<div dir="rtl">

```
بدلیل عدم پشتیبانی تیم Larabook این پکیج بصورت شخصی توسعه میابد
```

پکیج اتصال به تمامی IPG ها و  بانک های ایرانی.

این پکیج با ورژن های
(۴، ۵، ۶، ۷، ۸، ۹، ۱۰، ۱۱، ۱۲ و ۱۳)
لاراول سازگار می باشد.

حداقل نسخه PHP برای اجرای پکیج، ۸ است. لاراول ۱۱ و ۱۲ به PHP ۸.۲ یا جدیدتر، و لاراول ۱۳ به PHP ۸.۳ یا جدیدتر نیاز دارند.

نسخه فعلی پکیج ۳ است.


پشتیبانی تنها از درگاهای زیر می باشد:
 1. MELLAT
 2. SADAD (MELLI)
 3. SAMAN Token Base  (جدید)
 3. SAMAN
 4. PARSIAN
 5. PASARGAD(جدید)
 6. ZARINPAL (ورژن 4)
 7. ASAN PARDAKHT 
 8. PAY.IR ( برای فراخوانی از 'payir' استفاده نمایید)
 9. Irankish (**جدید** -  برای فراخوانی از 'irankish' استفاده نمایید)
 10. Nikan (PASARGAD)  (جدید)
 11. PayPing (جدید)
 12. Vandar (جدید)
 13. Apsan (جدید)
 14. Digipay
 15. SADAD BNPL (MELLI BNPL)
 16. Zibal (جدید)
 17. MiliGold

Payline، جهان‌پی و ستاره‌اول در این نسخه فعال نیستند.
----------


**نصب**:

دستورات زیر را جهت نصب دنبال کنید :

**مرحله ۱)**

### نصب به وسیله "composer require"
```shell
composer require mozakar/gateway
```
### یا 
### در فایل composer.json اضافه کنید
</div>


```php
"require": {
  ...
  "mozakar/gateway" : "dev-master"
},
"repositories": [
    {
        "type": "git",
        "url": "https://github.com/mozakar/gateway.git"
    }
],

```   

<div dir="rtl">
 
**مرحله ۲)**

    تغییرات زیر را در فایل  config/app.php اعمال نمایید:

**توجه: از لاراول ۶ به بعد این مرحله لازم نیست. پکیج با package discovery ثبت می‌شود و در لاراول ۱۱ به بعد هم نیازی به ویرایش `config/app.php` یا `bootstrap/providers.php` نیست.** 

</div>

```php

'providers' => [
  ...
  Mozakar\Gateway\GatewayServiceProvider::class, // <-- add this line at the end of provider array
],


'aliases' => [
  ...
  'Gateway' => Mozakar\Gateway\Gateway::class, // <-- add this line at the end of aliases array
]

```



<div dir="rtl">

**مرحله ۳) - انتقال فایل های مورد نیاز**

برای لاراول ۵ :
</div>

```php

php artisan vendor:publish --provider=Mozakar\Gateway\GatewayServiceProviderLaravel5

```

<div dir="rtl">
برای لاراول ۶ به بعد، پرووایدر همان نسخه را منتشر کنید:
</div>

```php

php artisan vendor:publish --provider="Mozakar\Gateway\GatewayServiceProviderLaravel6"
php artisan vendor:publish --provider="Mozakar\Gateway\GatewayServiceProviderLaravel7"
php artisan vendor:publish --provider="Mozakar\Gateway\GatewayServiceProviderLaravel8"
php artisan vendor:publish --provider="Mozakar\Gateway\GatewayServiceProviderLaravel9"
php artisan vendor:publish --provider="Mozakar\Gateway\GatewayServiceProviderLaravel10"
php artisan vendor:publish --provider="Mozakar\Gateway\GatewayServiceProviderLaravel11"
php artisan vendor:publish --provider="Mozakar\Gateway\GatewayServiceProviderLaravel12"
php artisan vendor:publish --provider="Mozakar\Gateway\GatewayServiceProviderLaravel13"

```

<div dir="rtl">

تنظیمات پیش‌فرض از لاراول ۵ به بعد خودکار بارگذاری می‌شوند. برای سفارشی‌سازی، فایل تنظیمات، مایگریشن یا ویو را جداگانه منتشر کنید:

</div>

```php

php artisan vendor:publish --tag=config
php artisan vendor:publish --tag=migrations
php artisan vendor:publish --tag=views

```

<div dir="rtl"> 

**مرحله ۴) - ایجاد جداول**

</div>

```php

php artisan migrate

```


<div dir="rtl"> 
 
**مرحله ۵)**

عملیات نصب پایان یافته است حال فایل gateway.php را در مسیر app/  باز نموده و  تنظیمات مربوط به درگاه بانکی مورد نظر خود را در آن وارد نمایید .

از نسخه ۳ نام درگاه را مستقیم به `make` بدهید. نیازی به `switch` روی نام بانک نیست.

مبلغ پیش‌فرض همه درگاه‌ها ریال است. اگر مبلغ را به تومان دارید، یک بار `setCurrency` را صدا بزنید. پکیج خودش مبلغ را به واحدی که API همان بانک می‌خواهد تبدیل می‌کند.

</div>

- بیشتر بانک‌ها (ملت، سامان، سداد، پارسیان، پاسارگاد، زرین‌پال، زیبال، دیجی‌پی، آسان‌پرداخت، ایران‌کیش، نیکان، آپسان، سداد BNPL و pay.ir) ریال می‌خواهند.
- پی‌پینگ و وندار تومان می‌خواهند.
- میلی‌گلد مبلغ ریال یا تومان را با قیمت لحظه‌ای به میلی تبدیل می‌کند. برای میلی‌گلد مقدار `MILI` هم مجاز است.
 
 1. Gateway::make($bank)
 2. Gateway::make('mellat')
 3. Gateway::mellat()
 4. app('gateway')->make($bank);
 
<div dir="rtl">

 مثال :‌اتصال به درگاه (درخواست توکن و انتقال کاربر به بانک)

یک روت از نوع GET با آدرس /bank/request ایجاد نمایید و کد های زیر را در آن قرار دهید .

</div>


```php

try {

   $bank = 'mellat'; // zarinpal, saman, payping, digipay, ...
   $gateway = \Gateway::make($bank);

   // پیش‌فرض ریال است. اگر مبلغ فروشگاه تومان است:
   $gateway->setCurrency(\Mozakar\Gateway\Currency::TOMAN);

   $gateway->setCallback(url('/bank/response'));
   $gateway->setMobile('09xxxxxxxxx');
   $gateway->setOrderId(1);
   $gateway->price(1000)->ready(); // ۱۰۰۰ تومان؛ برای بانک ریالی ۱۰۰۰۰ ریال ارسال می‌شود

   $refId =  $gateway->refId(); // شماره ارجاع بانک
   $transID = $gateway->transactionId(); // شماره تراکنش

   //برای دیجی پی نوع تیکت، شماره همراه و شماره سفارش الزامی است.
   // $gateway->useCache();
   // $gateway->setTicketType(11);
   // $gateway->setBasketItems($basket);
   // مبلغ سطرهای سبد را خودتان به ریال بدهید؛ فقط مبلغ کل تبدیل می‌شود.

   // setFactorNumber("13131313") اختیاری و فقط برای وندار است.

   return $gateway->redirect();

} catch (\Exception $e) {

   echo $e->getMessage();
}

```

<div dir="rtl">

 و سپس روت با مسیر /bank/response  و از نوع post  ایجاد نمایید و کد های زیر را در آن قرار دهید :

</div>


```php

try { 

   $gateway = \Gateway::verify();
   $trackingCode = $gateway->trackingCode();
   $refId = $gateway->refId();
   $cardNumber = $gateway->cardNumber();

   // تراکنش با موفقیت سمت بانک تایید گردید
   // در این مرحله عملیات خرید کاربر را تکمیل میکنیم

} catch (\Mozakar\Gateway\Exceptions\RetryException $e) {

    // تراکنش قبلا سمت بانک تاییده شده است و
    // کاربر احتمالا صفحه را مجددا رفرش کرده است
    // لذا تنها فاکتور خرید قبل را مجدد به کاربر نمایش میدهیم

    echo $e->getMessage() . "<br>";

} catch (\Exception $e) {

    // نمایش خطای بانک
    echo $e->getMessage();
}

```




<div dir="rtl">

درخواست تسویه حساب از وندار (Vandar)

</div>


```php

try { 

  $track_id   = Str::random(32);
  $payment_number = rand(1000000000, 9999999999);
  $gateway = \Gateway::vandar();
  //ibanRequest($amount, $iban, $track_id, $payment_number, $is_instant = true)
  $response = $gateway->ibanRequest($amount, $sheba_number, $track_id, $payment_number, true);
  if($response){
      $response = ['success' => true, 'tx' => $track_id, 'data' => $response];
      return $response;
  }

}catch (\Exception $e) {

    // نمایش خطای بانک
    echo $e->getMessage();
}

```


<div dir="rtl">

نمایش موجودی و لیست تراکنش ها در وندار (Vandar)

</div>


```php

try { 

  $gateway = \Gateway::vandar();

  //نمایش موجودی
  $balance = $gateway->balance();

  //نمایش تراکنش ها
  $transactions = $gateway->transactions();

}catch (\Exception $e) {

    // نمایش خطای بانک
    echo $e->getMessage();
}

```

<div dir="rtl">
 
در صورت تمایل جهت همکاری در توسعه   :

 1. توسعه مستندات پکیج.
 2. گزارش باگ و خطا.
 3. همکاری در نوشتن ماژول دیگر بانک ها برای این پکیج .


درصورت بروز هر گونه 
 [باگ](https://github.com/mozakar/gateway/issues) یا [خطا](https://github.com/mozakar/gateway/issues)  .
  ما را آگاه سازید .
  
این پکیج از پکیج دیگری بنام  poolport  مشتق شده است اما برخی از عملیات آن متناسب با فریموورک لارول تغییر کرده است
</div>
