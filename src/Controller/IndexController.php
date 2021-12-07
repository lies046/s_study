<?php

namespace App\Controller;

use DateTimeImmutable;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Configuration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $username = $this->getUser()->getUserIdentifier();
        $configuration = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->getParameter('mercure_secret_key'))
        );

        $now   = new DateTimeImmutable();
        $token = $configuration->builder()
            ->withClaim('mercure', ['subscribe' => [sprintf("/%s", $username)]])
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
