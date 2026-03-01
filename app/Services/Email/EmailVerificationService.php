<?php

namespace App\Services\Email;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmailVerificationMail;
use App\Mail\PasswordResetMail;

class EmailVerificationService
{
    /**
     * Send verification code to user
     */
    public function sendVerificationCode(User $user, int $code): void
    {
Mail::to($user->email)->queue(new EmailVerificationMail($user, $code));
    }

    /**
     * Send password reset code
     */
    public function sendPasswordResetCode(User $user, int $code): void
    {
        Mail::to($user->email)->send(new PasswordResetMail($user, $code));
    }
}