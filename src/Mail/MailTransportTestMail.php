<?php

namespace FleetTrackingTechnology\LaravelMailTransport\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class MailTransportTestMail extends Mailable
{
    public function __construct(
        public string $sentAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '['.config('app.name').'] Mail transport test — '.$this->sentAt,
        );
    }

    public function content(): Content
    {
        $app = e(config('app.name'));
        $mailer = e((string) config('mail.default'));

        return new Content(
            htmlString: <<<HTML
<p>This is a <strong>test message</strong> from {$app}.</p>
<p>Mailer in use: <code>{$mailer}</code></p>
<p>If you received this email, outgoing mail is working.</p>
HTML
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
