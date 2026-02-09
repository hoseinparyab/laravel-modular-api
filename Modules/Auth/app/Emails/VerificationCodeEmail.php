<?php

namespace Modules\Auth\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class VerificationCodeEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public int $code) {}

    /**
     * Build the message.
     */

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verification Code From Najino'.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'auth::emails.verification-code',
            with: [
                'code' => $this->code,
            ],
        );
    }
}
