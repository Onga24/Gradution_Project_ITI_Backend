<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;



class SendOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;
    public $type;


    /**
     * Create a new message instance.
     */
    public function __construct($otp, $type)
    {
        $this->otp = $otp;
        $this->type = $type;

    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Send Otp Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    // public function content(): Content
    // {
    //     return new Content(
    //         view: 'emails.verifyotp',
    //         with: [
    //             'otp' => $this->otp,
    //             'type' => $this->type,
    //         ],
    //     );
    // }

    public function content(): Content
{
    $view = $this->type === 'forgot_password'
        ? 'emails.forgot'
        : 'emails.verifyotp';

    return new Content(
        view: $view,
        with: [
            'otp' => $this->otp,
            'type' => $this->type,
        ],
    );
}


    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
