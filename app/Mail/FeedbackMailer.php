<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FeedbackMailer extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $reject_data;
    public $objects;
    public $comments;
    public $information;
    /**
     * Create a new message instance.
     */
    public function __construct($data,$reject_data,$objects,$comments,$information)
    {
        $this->data = $data;
        $this->reject_data = $reject_data;
        $this->objects = $objects;
        $this->comments = $comments;
        $this->information = $information;
    }



    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'ASDM: NOTIFICATION SUR PROJET',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.send_feedback_mailer',
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
