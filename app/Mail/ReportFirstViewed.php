<?php

namespace App\Mail;

use App\Models\Share;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReportFirstViewed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Share $share)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your report \"{$this->share->project->name}\" was just viewed",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.report-first-viewed',
            with: [
                'projectName' => $this->share->project->name,
                'reportUrl' => url('/r/'.$this->share->slug),
            ],
        );
    }
}
