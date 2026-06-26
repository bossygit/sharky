<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $code) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre code de vérification — nasande.cg',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'commerce::mail.otp-text',
        );
    }
}
