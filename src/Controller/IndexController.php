<?php

namespace App\Controller;

use App\Repository\UserRepository;
use DateTimeImmutable;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Configuration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\User;

class IndexController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(UserRepository $userRepository): Response
    {
//        $username = $this->getUser()->getUserIdentifier();
        $username = $userRepository->find(1);
        $configuration = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->getParameter('mercure_secret_key'))
        );

        $now   = new DateTimeImmutable();
        $token = $configuration->builder()
//            ->withClaim('mercure', ['subscribe' => [sprintf("/%s", $username)]])
            ->withClaim('mercure', ['subscribe' => [sprintf("/%s", $username->getUserIdentifier())]])
            ->getToken($configuration->signer(), $configuration->signingKey());
        $token = $token->toString();

        $response = $this->render('index/index.html.twig', [
            'controller_name' => 'IndexController',
        ]);


        $response->headers->setCookie(
            new Cookie(
                'mercureAuthorization',
                $token,
                (new \DateTime())
                ->add(new \DateInterval('PT2H')),
                '/.well-known/mercure',
                null,
                false,
                true,
                false,
                'strict'
            )
        );
        return $response;
    }
}
