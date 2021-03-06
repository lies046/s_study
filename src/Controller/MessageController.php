<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Repository\MessageRepository;
use App\Repository\ParticipantRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/messages', name: 'messages')]
class MessageController extends AbstractController
{

    const ATTRIBUTE_TO_SERIALIZE = ['id', 'content', 'createdAt', 'mine'];

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;
    /**
     * @var MessageRepository
     */
    private MessageRepository $messageRepository;
    /**
     * @var UserRepository
     */
    private UserRepository $userRepository;
    /**
     * @var ParticipantRepository
     */
    private ParticipantRepository $participantRepository;
    /**
     * @var HubInterface
     */
    private HubInterface $hub;

    public function __construct(EntityManagerInterface $entityManager, MessageRepository $messageRepository, UserRepository $userRepository, ParticipantRepository $participantRepository, HubInterface $hub)
    {
        $this->entityManager = $entityManager;
        $this->messageRepository = $messageRepository;
        $this->userRepository = $userRepository;
        $this->participantRepository = $participantRepository;
        $this->hub = $hub;
    }


    /**
     * @param Request $request
     * @param Conversation $conversation
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'getMessage', methods: 'GET')]
    public function index(Request $request, Conversation $conversation): Response
    {
        $this->denyAccessUnlessGranted('view', $conversation);
        $messages = $this->messageRepository->findMessageByConversationId(
            $conversation->getId()
        );

        /**
         * @var $message Message
         */
        array_map(function ($message) {
            $message->setMine(
                $message->getUser()->getId() === $this->getUser()->getId()
                    ? true : false
            );
        }, $messages);

        return $this->json($messages, Response::HTTP_OK, [], [
            'attributes' => self::ATTRIBUTE_TO_SERIALIZE
        ]);
    }


    /**
     * @param Request $request
     * @param Conversation $conversation
     * @param SerializerInterface $serializer
     * @return JsonResponse
     * @throws \Exception
     */
    #[Route('/{id}', name: 'newMessage', methods: 'POST')]
    public function newMessage(Request $request, Conversation $conversation, SerializerInterface $serializer)
    {
        $user = $this->userRepository->findOneBy(['id' => 2]);

        $recipient = $this->participantRepository->findParticipantByConversationIdAndUserId(
          $conversation->getId(),
          $user->getId()
        );
        $content = $request->get('content', null);

        $message = new Message();
        $message->setContent($content)
            ->setUser($user)
            ->setMine(true);
        $conversation->addMessage($message)
            ->setLastMessage($message);

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $this->entityManager->persist($message);
            $this->entityManager->persist($conversation);
            $this->entityManager->flush();
            $this->entityManager->commit();
        }catch (\Exception $e){
            $this->entityManager->rollback();
            throw $e;
        }
        $message->setMine(false);
        $messageSerialized = $serializer->serialize($message, 'json',[
            'attributes' => ['id', 'content', 'createdAt', 'mine', 'conversation' =>['id']]
        ]);
        $update = new Update(
            [
                sprintf('conversations/%s', $conversation->getId()),
                sprintf('conversations/%s', $recipient->getUser()->getUserName())
//            'http://localhost/conversations/'. $conversation->getId(),
//            'http://localhost/conversations/'. $recipient->getUser()->getUserName(),

            ],
           $messageSerialized,
            true
        );
        $this->hub->publish($update);
        $message->setMine(true);


        return $this->json($message, Response::HTTP_CREATED,[],[
            'attributes' => self::ATTRIBUTE_TO_SERIALIZE
        ]);
    }
}
