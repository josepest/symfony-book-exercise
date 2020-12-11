<?php

namespace App\Controller;

use App\SpamChecker;
use Twig\Environment;
use App\Entity\Comment;
use App\Entity\Conference;
use App\Form\CommentFormType;
use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use Symfony\Component\Form\FormError;
use App\Repository\ConferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class ConferenceController extends AbstractController
{
    private $entityManager;
    private $bus;

    public function __construct(EntityManagerInterface $entityManager, MessageBusInterface $bus)
    {
        $this->entityManager = $entityManager;
        $this->bus = $bus;
    }


    /**
     * @Route("/conference_header", name="conference_header")
     */
    public function conferenceHeader(ConferenceRepository $conferenceRepository): Response
    {
        $response = $this->render('conference/header.html.twig', [
            'conferences' => $conferenceRepository->findAll(),
        ]);
        $response->setSharedMaxAge(3600);
        return $response;
    }

    /**
     * @Route("/", name="conference-list")
     */
    public function index(ConferenceRepository $conferenceRepository): Response
    {
        $response = $this->render('conference/index.html.twig', [
            'conferences' => $conferenceRepository->findAll()
        ]);

        /*
        $response = new Response($twig->render('conference/index.html.twig', [
            'conferences' => $conferenceRepository->findAll(),
        ]));
        */

        //$response->setPublic();
        //$response->setMaxAge(600);
        $response->setSharedMaxAge(3600);
        return $response;
    }

    /**
     * @Route("/conference/{slug}", name="conference-view")
     */
    public function show(
        Request $request,
        Conference $conference,
        CommentRepository $commentRepository,
        ConferenceRepository $conferenceRepository,
        NotifierInterface $notifier,
        string $photoDir
    ): Response {
        $comment = new Comment();
        $form = $this->createForm(CommentFormType::class, $comment);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                $notifier->send(new Notification('Can you check your submission? There are some problems with it.', ['browser']));
            } else {
                $comment->setConference($conference);
                if ($photo = $form['photo']->getData()) {
                    $filename = bin2hex(random_bytes(6)).'.'.$photo->guessExtension();
                    try {
                        $photo->move($photoDir, $filename);
                    } catch (FileException $e) {
                        // unable to upload the photo, give up
                    }
                    $comment->setPhotoFilename($filename);
                }
                $this->entityManager->persist($comment);
                $this->entityManager->flush();

                $context = [
                    'user_ip' => $request->getClientIp(),
                    'user_agent' => $request->headers->get('user-agent'),
                    'referrer' => $request->headers->get('referer'),
                    'permalink' => $request->getUri(),
                    ];
                $this->bus->dispatch(new CommentMessage($comment->getId(), $context));

                $notifier->send(new Notification('Thank you for the feedback; your comment will be posted after moderation.', ['browser']));

                return $this->redirectToRoute('conference-view', ['slug' => $conference->getSlug()]);
            }
        }

        $offset = max(0, $request->query->getInt('offset', 0));
        $commentsPaginated = $commentRepository->getCommentPaginator($conference, $offset);

        return $this->render('conference/show.html.twig', [
            'conference' => $conference,
            'comments' => $commentsPaginated,
            'previous' => $offset - CommentRepository::PAGINATOR_PER_PAGE,
            'next' => min(count($commentsPaginated), $offset + CommentRepository::PAGINATOR_PER_PAGE),
            'comment_form' => $form->createView()
        ]);
    }
}
