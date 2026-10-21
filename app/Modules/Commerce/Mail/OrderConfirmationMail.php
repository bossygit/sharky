<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Mail;

use App\Modules\Commerce\Domain\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmation commande '.$this->order->order_number.' — Nasande Vins',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'commerce::mail.order-confirmation-text',
        );
    }
}
