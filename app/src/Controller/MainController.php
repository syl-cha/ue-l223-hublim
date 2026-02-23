<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\CardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MainController extends AbstractController
{
    #[Route('/', name: 'app_main')]
    public function index(UserRepository $userRepo, CardRepository $cardRepo): Response
    {
        // On récupère tout ce qu'il y a en base
        return $this->render('main/index.html.twig', [
            'users' => $userRepo->findAll(),
            'cards' => $cardRepo->findAll(),
        ]);
    }
}