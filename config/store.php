<?php

return [
    'name' => env('STORE_NAME', 'Rama Aurantha'),
    'admin_email' => env('STORE_ADMIN_EMAIL'),
    'otp_expiry_minutes' => (int) env('OTP_EXPIRY_MINUTES', 10),
];
