<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FeedbackMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $feedbackType,
        public string $messageBody,
        public string $senderName,
        public string $senderEmail,
        public ?string $organizationName,
    ) {}

    public function build()
    {
        return $this->subject("New {$this->feedbackType} from {$this->senderName}")
            ->replyTo($this->senderEmail, $this->senderName)
            ->view('emails.feedback');
    }
}
