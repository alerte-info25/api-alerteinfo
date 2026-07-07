<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendAccountMailer extends Mailable
{
    use Queueable, SerializesModels;

    public $password;
    public $customer_name;

    public $email;
    public $subject_email;

    public $default_text;
    /**
     * Create a new message instance.
     */
    public function __construct($passwords, $customer_name, $email, $subject_email, $default_text)
    {
        $this->password = $passwords;
        $this->customer_name = $customer_name;
        $this->email = $email;
        $this->subject_email = $subject_email;
        $this->default_text = $default_text;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject_email,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.send_account_mailer',
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
