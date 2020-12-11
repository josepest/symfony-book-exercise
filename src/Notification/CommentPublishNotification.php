<?php

namespace App\Notification;

use App\Entity\Comment;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;

class CommentPublishNotification extends Notification implements EmailNotificationInterface
{
    private $comment;

    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
        parent::__construct('Your comment has been published');
    }

    public function asEmailMessage(EmailRecipientInterface  $recipient, string $transport = null): ?EmailMessage
    {
        $this->importance(Notification::IMPORTANCE_LOW);
        $message = EmailMessage::fromNotification($this, $recipient, $transport);
        $message->getMessage()
            ->htmlTemplate('emails/comment_publish.html.twig')
            ->context(
                ['comment' => $this->comment,
                'conference' => $this->comment->getConference()]
            );

        return $message;
    }
}
