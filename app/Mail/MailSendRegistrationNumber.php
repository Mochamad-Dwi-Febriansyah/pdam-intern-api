<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MailSendRegistrationNumber extends Mailable
{
    use Queueable, SerializesModels;

    public $registration_number;
    public $program_type;
    /**
     * Create a new message instance.
     */
    public function __construct($registration_number, $program_type)
    {
        $this->registration_number = $registration_number;
        $this->program_type = $program_type;
    } 

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Mail Send Registration Number',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.registration-number',
            with: [
                'registration-number' => $this->registration_number, 
                'program_type' => $this->program_type, 
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
