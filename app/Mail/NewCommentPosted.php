<?php

namespace App\Mail;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewCommentPosted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Comment $comment) {}

    public function envelope(): Envelope
    {
        $project = $this->comment->share->project;

        return new Envelope(
            subject: "New comment on \"{$project->name}\" from {$this->comment->authorName()}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.new-comment-posted',
            with: [
                'projectName' => $this->comment->share->project->name,
                'authorName' => $this->comment->authorName(),
                'body' => $this->comment->body,
                'reportUrl' => url('/r/'.$this->comment->share->slug),
            ],
        );
    }
}
