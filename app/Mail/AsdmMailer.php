<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AsdmMailer extends Mailable
{
    use Queueable, SerializesModels;


    public $password;
    public $data;

    public $email;
    public $profil;
    /**
     * Create a new message instance.
     */
    public function __construct($passwords, $data, $email, $profil)
    {
        $this->password = $passwords;
        $this->data = $data;
        $this->email = $email;
        $this->profil = $profil;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'ASDM: NOTIFICATION APRES CREATION DE COMPTE',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.asdm_mailer',
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
