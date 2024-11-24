<div dir="rtl">

```
بدلیل عدم پشتیبانی تیم Larabook این پکیج بصورت شخصی توسعه میابد
```

پکیج اتصال به تمامی IPG ها و  بانک های ایرانی.

این پکیج با ورژن های
(  ۴ و ۵ و ۶ و۷ و ۸ و ۹ و ۱۰)
 لاراول سازگار می باشد


پشتیبانی تنها از درگاهای زیر می باشد:
 1. MELLAT
 2. SADAD (MELLI)
 3. SAMAN Token Base  (جدید)
 3. SAMAN
 4. PARSIAN
 5. PASARGAD(جدید)
 6. ZARINPAL
 7. ASAN PARDAKHT 
 8. PAY.IR ( برای فراخوانی از 'payir' استفاده نمایید)
 9. Irankish (**جدید** -  برای فراخوانی از 'irankish' استفاده نمایید)
 10. Nikan (PASARGAD)  (جدید)
 11. PayPing (جدید)
 12. Vandar (جدید)
 13. Apsan (جدید)
 14. Digipay
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

**توجه برای نسخه های لاراول ۶ به بعد  این مرحله نیاز به انجام نمی باشد** 

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
برای لاراول ۶ به بعد :
</div>

```php

php artisan vendor:publish 

// then choose : GatewayServiceProviderLaravel6
// then choose : GatewayServiceProviderLaravel7
// then choose : GatewayServiceProviderLaravel8
// then choose : GatewayServiceProviderLaravel9
// then choose : GatewayServiceProviderLaravel10

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

حال میتوایند برای اتصال به api  بانک  از یکی از روش های زیر به انتخاب خودتان استفاده نمایید . (Facade , Service container):
</div>
 
 1. Gateway::make(new Mellat())
 2. Gateway::make('mellat')
 3. Gateway::mellat()
 4. app('gateway')->make(new Mellat());
 5. app('gateway')->mellat();
 
<div dir="rtl">

 مثال :‌اتصال به بانک ملت (درخواست توکن و انتقال کاربر به درگاه بانک)
توجه :‌ مقدار متد price   به ریال وارد شده است و معادل یکصد تومان می باشد

یک روت از نوع GET با آدرس /bank/request ایجاد نمایید و کد های زیر را در آن قرار دهید .

</div>


```php

try {

   $gateway = \Gateway::make('mellat');

   $gateway->setCallback(url('/bank/response')); // You can also change the callback
   $gateway->price(1000)
           // setFactorNumber("13131313") // optional - just for vandar
           ->ready();

   $refId =  $gateway->refId(); // شماره ارجاع بانک
   $transID = $gateway->transactionId(); // شماره تراکنش

  
   // در اینجا
   //  شماره تراکنش  بانک را با توجه به نوع ساختار دیتابیس تان 
   //  در جداول مورد نیاز و بسته به نیاز سیستم تان
   // ذخیره کنید .

    //برای دیجی پی
    //نوع تیکت شماره همراه کاربر و شماره سفارش الزامی است.
    // $gateway->setTicketType(11);
    // $gateway->setMobile("09350000000");
    // $gateway->setOrderId(1);
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
