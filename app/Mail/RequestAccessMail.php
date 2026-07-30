<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequestAccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $senderEmail,
        public string $messageBody,
    ) {}

    public function build()
    {
        return $this->subject("New access request from {$this->senderEmail}")
            ->replyTo($this->senderEmail)
            ->view('emails.request-access');
    }
}