<?php

namespace App\Controller;

use App\Entity\Conversation;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
#[Route('/messages', name: 'messages')]
class MessageController extends AbstractController
{
    /**
     * @param Request $request
     * @param Conversation $conversation
     * @return Response
     */
    #[Route('/{id}', name: 'getMessage')]
    public function index(Request $request, Conversation $conversation): Response
    {
        $this->denyAccessUnlessGranted('view', $conversation);
        return $this->render('message/index.html.twig', [
            'controller_name' => 'MessageController',
        ]);
    }
}
