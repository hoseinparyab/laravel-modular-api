<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\Log;

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
        $to = $this->normalizePhoneNumber($to);
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
            Log::error("Melipayamak Curl Error: " . $curlErrMsg);
            return [
                'success' => false,
                'message' => 'خطای ارتباط با سامانه پیامک: ' . $curlErrMsg,
                'code' => $curlErr
            ];
        }

        $cleanResponse = trim(strip_tags((string) $response));

        // Check for specific error codes
        if (array_key_exists($cleanResponse, self::ERROR_MESSAGES)) {
            Log::warning("Melipayamak Error Response: " . $cleanResponse . " - " . self::ERROR_MESSAGES[$cleanResponse]);
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

        Log::error("Melipayamak Unexpected Response: " . $cleanResponse);
        // Fallback for unexpected responses
        return [
            'success' => false,
            'message' => 'پاسخ نامشخص از سامانه پیامک: ' . $cleanResponse,
            'response' => $cleanResponse
        ];
    }

    /**
     * Normalize phone number to Melipayamak expected format (09XXXXXXXXX)
     */
    private function normalizePhoneNumber(string $phone): string
    {
        // Remove any non-numeric characters except +
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // Convert +98... to 0...
        if (str_starts_with($phone, '+98')) {
            $phone = '0' . substr($phone, 3);
        }
        // Convert 0098... to 0...
        elseif (str_starts_with($phone, '0098')) {
            $phone = '0' . substr($phone, 4);
        }
        // Convert 98... to 0...
        elseif (str_starts_with($phone, '98') && strlen($phone) > 10) {
            $phone = '0' . substr($phone, 2);
        }
        // If it starts with 9... and is 10 digits, add a 0
        elseif (preg_match('/^9\d{9}$/', $phone)) {
            $phone = '0' . $phone;
        }

        return $phone;
    }
}
