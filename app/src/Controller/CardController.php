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
    public function show(Card $card, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
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
}
