<?php

namespace App\Mail;

use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class YouAreNextMail extends Mailable
{
    use Queueable, SerializesModels;

    public ServiceRequest $serviceRequest;
    public string $mode;

    public function __construct(ServiceRequest $serviceRequest, string $mode)
    {
        $this->serviceRequest = $serviceRequest;
        $this->mode = $mode;
    }

    public function build()
    {
        $subject = $this->mode === 'serving'
            ? 'Queue Update: Your Request Is Now Being Served'
            : 'Queue Update: One Person Ahead of You';

        return $this->subject($subject)
            ->view('emails.you_are_next');
    }
}
