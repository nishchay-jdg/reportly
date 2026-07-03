<?php

namespace App\Mail;

use App\Models\AgreementSignature;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AgreementSigned extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AgreementSignature $signature, public bool $isSignerCopy = false) {}

    public function envelope(): Envelope
    {
        $project = $this->signature->share->project;

        return new Envelope(
            subject: $this->isSignerCopy
                ? "Your signed copy of \"{$project->name}\""
                : "Agreement signed: \"{$project->name}\" by {$this->signature->full_name}",
        );
    }

    public function content(): Content
    {
        $project = $this->signature->share->project;

        return new Content(
            markdown: 'mail.agreement-signed',
            with: [
                'projectName' => $project->name,
                'fullName' => $this->signature->full_name,
                'email' => $this->signature->email,
                'companyName' => $this->signature->company_name,
                'signedAt' => $this->signature->signed_at->format('M j, Y g:ia'),
                'reportUrl' => url('/r/'.$this->signature->share->slug),
                'isSignerCopy' => $this->isSignerCopy,
            ],
        );
    }
}
