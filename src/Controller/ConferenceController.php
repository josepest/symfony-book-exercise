<?php

namespace App\Controller;

use App\SpamChecker;
use App\Entity\Comment;
use App\Entity\Conference;
use App\Form\CommentFormType;
use App\Repository\CommentRepository;
use Symfony\Component\Form\FormError;
use App\Repository\ConferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class ConferenceController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/", name="conference-list")
     */
    public function index(ConferenceRepository $conferenceRepository): Response
    {
        return $this->render('conference/index.html.twig');
    }

    /**
     * @Route("/conference/{slug}", name="conference-view")
     */
    public function show(Request $request, Conference $conference, CommentRepository $commentRepository, ConferenceRepository $conferenceRepository, SpamChecker $spamChecker, string $photoDir): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentFormType::class, $comment);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $context = [
                        'user_ip' => $request->getClientIp(),
                        'user_agent' => $request->headers->get('user-agent'),
                        'referrer' => $request->headers->get('referer'),
                        'permalink' => $request->getUri(),
                        ];
            if (2 === $spamChecker->getSpamScore($comment, $context)) {
                //throw new \RuntimeException('Blatant spam, go away!');
                $form->addError(new FormError("Blatant spam, go away!"));
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
