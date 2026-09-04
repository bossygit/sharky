<?php

declare(strict_types=1);

namespace App\Modules\Commerce\Mail;

use App\Modules\Commerce\Domain\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewOrderAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nouvelle commande '.$this->order->order_number.' — nasande.cg',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'commerce::mail.new-order-admin-text',
        );
    }
}
