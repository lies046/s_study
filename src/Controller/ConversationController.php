<?php

namespace App\Controller;

use App\Repository\ConversationRepository;
use App\Repository\UserRepository;
use Doctrine\DBAL\Driver\PDO\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/conversations', name: 'conversations')]
class ConversationController extends AbstractController
{


    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private ConversationRepository $conversationRepository;

    public function __construct(UserRepository $userRepository
    , EntityManagerInterface $entityManager
    , ConversationRepository $conversationRepository)
    {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->conversationRepository = $conversationRepository;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    #[Route('/{id}', name: 'new_conversation')]
    public function index(Request $request, int $id): Response
    {

        $otherUser = $request->get('otherUser', 0);
        $otherUser = $this->userRepository->find($id);

        if (is_null($otherUser)){
            throw new \Exception("The user was not found");
        }

        // cannot create a conversation with myself
        if ($otherUser->getId() === $this->getUser()->getId()){
            throw new Exception("That's deep you cannot create a conversation with yourself");
        }

        // check if conversation already exists
        $conversation = $this->conversationRepository->findConversationByParticipants(
            $otherUser->getId(),
            $this->getUser()->getId()
        );
        return $this->json();
    }
}
