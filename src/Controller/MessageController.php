<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
#[Route('/messages', name: 'messages')]
class MessageController extends AbstractController
{


    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;
    /**
     * @var MessageRepository
     */
    private MessageRepository $messageRepository;

    public function __construct(EntityManagerInterface $entityManager, MessageRepository $messageRepository)
    {
        $this->entityManager = $entityManager;
        $this->messageRepository = $messageRepository;
    }


    /**
     * @param Request $request
     * @param Conversation $conversation
     * @return Response
     */
    #[Route('/{id}', name: 'getMessage')]
    public function index(Request $request, Conversation $conversation): Response
    {
        $this->denyAccessUnlessGranted('view', $conversation);
        $messages = $this->messageRepository->findMessageByConversationId(
            $conversation->getId()
        );

        return $this->render('message/index.html.twig', [
            'controller_name' => 'MessageController',
        ]);
    }
}
