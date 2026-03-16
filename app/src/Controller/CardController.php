<?php

namespace App\Controller;

use App\Entity\Card;
use App\Form\CardType;
use App\Repository\CardRepository;
use App\Repository\StudyFieldRepository;
use App\Entity\Image;
use App\Form\CardImageType;
use App\Service\ImageUploadService;
use Symfony\Component\Security\Http\Attribute\IsGranted;
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
            $query,
            $request->query->getInt('page', 1),
            9
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
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ImageUploadService $imageUploadService,
        StudyFieldRepository $studyFieldRepository
    ): Response {
        $card = new Card();
        $card->setUser($this->getUser());

        $form = $this->createForm(CardType::class, $card);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($card);

            $files = $form->get('imageFiles')->getData();
            $files = array_slice($files, 0, 10);
            foreach ($files as $position => $file) {
                try {
                    $result = $imageUploadService->upload($file);
                    $image = new Image();
                    $image->setFileName($result['filename']);
                    $image->setSize($result['size']);
                    $image->setPosition($card->getImages()->count());
                    $card->addImage($image);
                    $entityManager->persist($image);
                } catch (\InvalidArgumentException $e) {
                    $this->addFlash('error', $e->getMessage());
                }
            }

            $entityManager->flush();
            return $this->redirectToRoute('app_card_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('card/new.html.twig', [
            'card' => $card,
            'form' => $form,
            'studyFieldsGrouped' => $studyFieldRepository->findAllGrouped(),
        ]);
    }

    #[Route('/{id}', name: 'app_card_show', methods: ['GET', 'POST'])]
    public function show(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        CardRepository $cardRepository,
    ): Response {
        $card = $cardRepository->find($id);

        if (!$card) {
            $this->addFlash('error', 'Cette annonce n\'existe plus ou a été supprimée.');
            return $this->redirectToRoute('app_card_index');
        }

        $user = $this->getUser();

        // Gestion des messages lus
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

        // Formulaire message
        $message = new \App\Entity\Message();
        $messageForm = $this->createForm(\App\Form\MessageType::class, $message, [
            'action' => $this->generateUrl('app_message_new', ['id' => $card->getId()]),
            'method' => 'POST',
        ]);

        // Compteur de vues
        $isAuthor = $user && $user === $card->getUser();
        if (!$isAuthor) {
            $session = $request->getSession();
            $viewedCards = $session->get('viewed_cards', []);
            if (!in_array($card->getId(), $viewedCards)) {
                $card->setViews(($card->getViews() ?? 0) + 1);
                $entityManager->flush();
                $viewedCards[] = $card->getId();
                $session->set('viewed_cards', $viewedCards);
            }
        }

        return $this->render('card/show.html.twig', [
            'card' => $card,
            'message_form' => $messageForm->createView(),
        ]);
    }


    #[Route('/{id}/edit', name: 'app_card_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(
        Request $request, 
        Card $card, 
        EntityManagerInterface $entityManager,
        ImageUploadService $imageUploadService,
        StudyFieldRepository $studyFieldRepository
    ): Response {
        if ($this->getUser() !== $card->getUser()) {
            $this->addFlash('error', 'Vous n\'êtes pas le propriétaire de cette annonce.');
            return $this->redirectToRoute('app_card_show', ['id' => $card->getId()]);
        }

        $form = $this->createForm(CardType::class, $card);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // 1. SUPPRESSION (Logique identique à ton show)
            $formData = $request->request->all('image_carte') ?? [];
            $deleteIds = $formData['delete_images'] ?? [];
            foreach ($deleteIds as $imageId) {
                $image = $entityManager->getRepository(Image::class)->find($imageId);
                if ($image && $image->getCard() === $card) {
                    // Utilise ta méthode delete() du service comme dans show
                    $imageUploadService->delete($image->getFileName());
                    $entityManager->remove($image);
                }
            }

            // 2. AJOUTS (Logique copiée de ton show/new)
            $imageFiles = $form->get('imageFiles')->getData();
            if ($imageFiles) {
                foreach ($imageFiles as $file) {
                    try {
                        // On récupère le tableau ['filename' => ..., 'size' => ...]
                        $result = $imageUploadService->upload($file);
                        
                        $image = new Image();
                        // C'est ici qu'on pioche dans le tableau result
                        $image->setFileName($result['filename']); 
                        $image->setSize($result['size']);
                        $image->setPosition($card->getImages()->count());
                        $image->setCard($card);
                        
                        $entityManager->persist($image);
                    } catch (\InvalidArgumentException $e) {
                        $this->addFlash('error', $e->getMessage());
                    }
                }
            }

            $entityManager->flush();
            
            $this->addFlash('success', 'Annonce mise à jour avec succès !');
            return $this->redirectToRoute('app_card_show', ['id' => $card->getId()], Response::HTTP_SEE_OTHER);
        }

        $response = new Response(null, $form->isSubmitted() && !$form->isValid() 
            ? Response::HTTP_UNPROCESSABLE_ENTITY // On renvoie 422 si le formulaire est invalide
            : Response::HTTP_OK
        );

        return $this->render('card/edit.html.twig', [
            'card' => $card,
            'form' => $form,
            'studyFieldsGrouped' => $studyFieldRepository->findAllGrouped(),
        ], $response);
    }

    #[Route('/{id}/delete', name: 'app_card_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(
        Request $request,
        Card $card,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->getUser() !== $card->getUser()) {
            $this->addFlash('error', 'Vous n\'êtes pas le propriétaire de cette annonce.');
            return $this->redirectToRoute('app_card_show', ['id' => $card->getId()]);
        }

        if ($this->isCsrfTokenValid('delete' . $card->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($card);
            $entityManager->flush();
            $this->addFlash('success', 'Annonce supprimée.');
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