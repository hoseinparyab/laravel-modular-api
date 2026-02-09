<?php

namespace Modules\Auth\Services;

class MelipayamakService
{
    private const ERROR_MESSAGES = [
        '0' => 'نام کاربری یا رمز عبور اشتباه است',
        '2' => 'اعتبار کافی نمی باشد',
        '3' => 'محدودیت در ارسال روزانه',
        '4' => 'محدودیت در حجم ارسال',
        '5' => 'شماره فرستنده معتبر نیست',
        '6' => 'سامانه در حال بروزرسانی است',
        '7' => 'متن پیامک شامل کلمات فیلتر شده است',
        '8' => 'عدم رسیدن به حداقل ارسال',
        '9' => 'پیامک تکراری',
        '10' => 'پایان اعتبار زمانی',
        '11' => 'محتوی نامعتبر',
        '12' => 'فایل موجود نیست',
        '35' => 'شماره گیرنده نامعتبر است',
        '-108' => 'IP مسدود شده است',
        '-109' => 'IP معتبر نیست',
        '-110' => 'استفاده از رمز عبور امکان پذیر نیست (ApiKey)',
    ];

    public function send(string $to, string $text, ?string $from = null, bool $isFlash = false): array
    {
        $username = config('auth.service.melipayamak.username', env('MELIPAYAMAK_USERNAME'));
        $password = config('auth.service.melipayamak.password', env('MELIPAYAMAK_PASSWORD'));
        $from = $from ?? config('auth.service.melipayamak.from', env('MELIPAYAMAK_FROM'));

        $data = [
            'username' => $username,
            'password' => $password,
            'from' => $from,
            'to' => $to,
            'text' => $text,
            'isflash' => $isFlash ? 'true' : 'false',
        ];

        $post_data = http_build_query($data);
        $handle = curl_init('http://api.payamak-panel.com/post/Send.asmx/SendSimpleSMS2');
        curl_setopt($handle, CURLOPT_HTTPHEADER, [
            'content-type: application/x-www-form-urlencoded'
        ]);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);

        $response = curl_exec($handle);
        $curlErr = curl_errno($handle);
        $curlErrMsg = curl_error($handle);
        curl_close($handle);

        if ($curlErr) {
            return [
                'success' => false,
                'message' => 'خطای ارتباط با سامانه پیامک: ' . $curlErrMsg,
                'code' => $curlErr
            ];
        }

        $cleanResponse = trim(strip_tags((string) $response));

        // Check for specific error codes
        if (array_key_exists($cleanResponse, self::ERROR_MESSAGES)) {
            return [
                'success' => false,
                'message' => self::ERROR_MESSAGES[$cleanResponse],
                'code' => $cleanResponse
            ];
        }

        // If it's a numeric value that isn't in our error list, assume it's a success RecID
        if (is_numeric($cleanResponse) && (float)$cleanResponse > 100) {
             return [
                'success' => true,
                'message' => 'پیامک با موفقیت ارسال شد',
                'recId' => $cleanResponse
            ];
        }

        // Fallback for unexpected responses
        return [
            'success' => false,
            'message' => 'پاسخ نامشخص از سامانه پیامک: ' . $cleanResponse,
            'response' => $cleanResponse
        ];
    }
}
