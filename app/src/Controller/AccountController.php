<?php

namespace App\Controller;

use Knp\Component\Pager\PaginatorInterface;
use App\Repository\CardRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class AccountController extends AbstractController
{
    #[Route('/account/annonces', name: 'app_mes_annonces')]
    public function mesAnnonces(CardRepository $cardRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $cardRepository->findBy(
            ['user' => $this->getUser()],
            ['createdAt' => 'DESC']
        );

        $cards = $paginator->paginate($query, $request->query->getInt('page', 1), 10);

        return $this->render('account/annonces.html.twig', [
            'cards' => $cards,
        ]);
    }

    #[Route('/account/favoris', name: 'app_mes_favoris')]
    public function mesFavoris(PaginatorInterface $paginator, Request $request): Response
    {
        $user = $this->getUser();

        $query = $user->getUserFavoriteCards();

        $cards = $paginator->paginate($query, $request->query->getInt('page', 1), 10);

        return $this->render('account/favoris.html.twig', [
            'cards' => $cards,
        ]);
    }

    #[Route('/account/profil', name: 'app_mon_profil')]
    public function profil(): Response
    {
        return $this->render('account/profil.html.twig');
    }

}
