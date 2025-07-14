<?php

namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\TemplateEmail;

class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Undocumented variable.
     *
     * @var array
     */
    public $data;

    /**
     * Create a new message instance.
     */
    public function __construct($data)
    {
        $data['content'] = str_replace(
        [
                    '##nama##', 
                    '##email##',
                    '##beasiswa##', 
                    '##no_pendaftaran##', 
                ],
                [
                    $data['name'], 
                    $data['email'],
                    $data['scholarship'], 
                    $data['registration_number'], 
                ],
        $data['content']
        );

        $this->data = $data;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->data['subject'] ?? 'Pemberitahuan',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.notification',
            with: $this->data
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
