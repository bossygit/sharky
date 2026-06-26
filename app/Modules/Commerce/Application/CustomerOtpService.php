<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Application;

use App\Models\User;
use App\Modules\Commerce\Domain\Models\CustomerOtp;
use App\Modules\Commerce\Mail\CustomerOtpMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CustomerOtpService
{
    public function send(string $email): void
    {
        $email = strtolower(trim($email));
        $length = (int) config('commerce.otp.length', 6);
        $code = str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);

        CustomerOtp::query()
            ->where('email', $email)
            ->whereNull('consumed_at')
            ->delete();

        CustomerOtp::create([
            'email' => $email,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes((int) config('commerce.otp.ttl_minutes', 10)),
        ]);

        Mail::to($email)->send(new CustomerOtpMail($code));
    }

    public function verify(string $email, string $code): User
    {
        $email = strtolower(trim($email));
        $otp = CustomerOtp::query()
            ->where('email', $email)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $otp || ! Hash::check($code, $otp->code_hash)) {
            throw new InvalidArgumentException('Code OTP invalide ou expiré.');
        }

        $otp->consumed_at = now();
        $otp->save();

        return User::firstOrCreate(
            ['email' => $email],
            [
                'name' => Str::before($email, '@'),
                'password' => Hash::make(Str::random(32)),
                'role' => 'customer',
                'email_verified_at' => now(),
            ],
        );
    }
}
