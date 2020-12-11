<?php
namespace App\MessageHandler;

use App\SpamChecker;
use App\ImageOptimizer;
use Psr\Log\LoggerInterface;
use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use App\Notification\CommentReviewNotification;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use App\Notification\CommentPublishNotification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CommentMessageHandler implements MessageHandlerInterface
{
    private $spamChecker;
    private $entityManager;
    private $commentRepository;
    private $mailer;
    private $imageOptimizer;
    private $photoDir;
    private $adminEmail;
    private $logger;
    private $notifier;

    public function __construct(
        EntityManagerInterface $entityManager,
        SpamChecker $spamChecker,
        CommentRepository $commentRepository,
        MessageBusInterface $bus,
        WorkflowInterface $commentStateMachine,
        MailerInterface $mailer,
        string $adminEmail,
        ImageOptimizer $imageOptimizer,
        NotifierInterface $notifier,
        string $photoDir,
        LoggerInterface $logger = null
    ) {
        $this->entityManager = $entityManager;
        $this->spamChecker = $spamChecker;
        $this->commentRepository = $commentRepository;
        $this->bus = $bus;
        $this->workflow = $commentStateMachine;
        $this->mailer = $mailer;
        $this->adminEmail = $adminEmail;
        $this->logger = $logger;
        $this->imageOptimizer = $imageOptimizer;
        $this->notifier = $notifier;
        $this->photoDir = $photoDir;
    }

    public function __invoke(CommentMessage $message)
    {
        $comment = $this->commentRepository->find($message->getId());
        if (!$comment) {
            return;
        }
        if ($this->workflow->can($comment, 'accept')) {
            $score = $this->spamChecker->getSpamScore($comment, $message->getContext());
            $transition = 'accept';
            if (2 === $score) {
                $transition = 'reject_spam';
            } elseif (1 === $score) {
                $transition = 'might_be_spam';
            }
            $this->workflow->apply($comment, $transition);
            $this->entityManager->flush();
            
            $this->bus->dispatch($message);
        } elseif ($this->workflow->can($comment, 'publish') || $this->workflow->can($comment, 'publish_ham')) {
            /*
            $this->mailer->send(
                (new NotificationEmail())
                ->subject('New comment posted')
                ->htmlTemplate('emails/comment_notification.html.twig')
                ->from($this->adminEmail)
                ->to($this->adminEmail)
                ->context(['comment' => $comment])
            );
            */
            $this->notifier->send(new CommentReviewNotification($comment), ...$this->notifier->getAdminRecipients());
        } elseif ($this->workflow->can($comment, 'optimize')) {
            if ($comment->getPhotoFilename()) {
                $this->imageOptimizer->resize($this->photoDir.'/'.$comment->getPhotoFilename());
            }
            $this->workflow->apply($comment, 'optimize');
            $this->entityManager->flush();

            //$conference = $comment->getConference();
            //$this->logger->debug('CommentPublishNotification', ['email' => $comment->getEmail(), 'slug' => $conference->getSlug()]);
            $this->notifier->send(
                new CommentPublishNotification($comment),
                new Recipient($comment->getEmail())
            );
        } elseif ($this->logger) {
            $this->logger->debug('Dropping comment message', ['comment' => $comment->getId(), 'state' => $comment->getState()]);
        }
    }
}
