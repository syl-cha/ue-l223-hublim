<?php

namespace App\Controller;

use App\Entity\Card;
use App\Form\CardType;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/card')]
final class CardController extends AbstractController
{
    #[Route(name: 'app_card_index', methods: ['GET'])]
    public function index(CardRepository $cardRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $cardRepository->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery();

        $pagination = $paginator->paginate(
            $query, /* requête SQL */
            $request->query->getInt('page', 1), /* numéro de page */
            9 /* nombre d'éléments par page */
        );

        return $this->render('card/index.html.twig', [
            'cards' => $pagination,
        ]);
    }

    #[Route('/search', name: 'card_search', methods: ['GET'])]
    public function search(Request $request, CardRepository $cardRepository): Response
    {
        $recherche = $request->query->get('query', '');

        if (!empty(trim($recherche))) {
            $cards = $cardRepository->searchFunction($recherche);
        } else {
            $cards = $cardRepository->findAll();
        }

        return $this->render('card/search.html.twig', [
            'cards' => $cards,
            'query' => $recherche,
        ]);
    }

    #[Route('/new', name: 'app_card_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $card = new Card();

        // On récupère l'utilisateur connecté et on l'associe à la carte
        $card->setUser($this->getUser());

        $form = $this->createForm(CardType::class, $card);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($card);
            $entityManager->flush();

            return $this->redirectToRoute('app_card_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('card/new.html.twig', [
            'card' => $card,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_card_show', methods: ['GET'])]
    public function show(Card $card, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        // Gestion des messages
        $isModified = false;

        foreach ($card->getMessages() as $message) {
            if ($message->getUser() !== $user && !$message->isRead()) {
                $message->setIsRead(true);
                $isModified = true;
            }
        }

        if ($isModified) {
            $entityManager->flush();
        }

        $message = new \App\Entity\Message();
        $form = $this->createForm(\App\Form\MessageType::class, $message, [
            'action' => $this->generateUrl('app_message_new', ['id' => $card->getId()]),
            'method' => 'POST',
        ]);

        // Gestion du compteur de vues
        $isAuthor = $user && $user === $card->getUser();

        if (!$isAuthor) {
            // on ne compte les vues que si ce n'est pas l'auteur de la carte qui regarde
            // on commence par récupérer les cartes vues dans la sessions
            $session = $request->getSession();
            $viewedCards = $session->get('viewed_cards', []);
            if (!in_array($card->getId(), $viewedCards)) {
                // on ne compte la vue qu'une fois par session
                // MAJ de la BDD
                $card->setViews(($card->getViews() ?? 0) + 1);
                $entityManager->flush();
                // MAJ de la Session
                $viewedCards[] = $card->getId();
                $session->set('viewed_cards', $viewedCards);
            }
        }

        return $this->render('card/show.html.twig', [
            'card' => $card,
            'message_form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_card_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Card $card, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CardType::class, $card);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_card_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('card/edit.html.twig', [
            'card' => $card,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_card_delete', methods: ['POST'])]
    public function delete(Request $request, Card $card, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $card->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($card);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_card_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/favorite', name: 'app_card_favorite', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function favorite(Card $card, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($card->getFanUsers()->contains($user)) {
            $card->removeFanUser($user);
            $this->addFlash('success', 'Carte retirée de vos favoris.');
        } else {
            $card->addFanUser($user);
            $this->addFlash('success', 'Carte ajoutée à vos favoris.');
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_card_show', ['id' => $card->getId()]);
    }
}
