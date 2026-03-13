<?php

namespace App\Controller;

use App\Entity\Card;
use App\Form\CardType;
use App\Repository\CardRepository;
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
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ImageUploadService $imageUploadService
    ): Response {
        $card = new Card();
        $card->setUser($this->getUser());

        $form = $this->createForm(CardType::class, $card);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($card);

            // Traitement des images
            $files = $form->get('imageFiles')->getData();
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
        ]);
    }

    #[Route('/{id}', name: 'app_card_show', methods: ['GET'])]
    public function show(Card $card): Response
    {
        return $this->render('card/show.html.twig', [
            'card' => $card,
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

    #[Route('/{id}/images', name: 'app_card_images', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function editImages(
        Card $card,
        Request $request,
        ImageUploadService $imageUploadService,
        EntityManagerInterface $entityManager
    ): Response {
        // Seul l'auteur peut gérer ses images
        if ($this->getUser() !== $card->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(CardImageType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $files = $form->get('imageFiles')->getData();

            foreach ($files as $position => $file) {
                try {
                    $result = $imageUploadService->upload($file);

                    $image = new Image();
                    $image->setFileName($result['filename']);
                    $image->setSize($result['size']);
                    $image->setPosition($card->getImages()->count() + $position);
                    $card->addImage($image);
                    $entityManager->persist($image);
                } catch (\InvalidArgumentException $e) {
                    $this->addFlash('error', $e->getMessage());
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Photos ajoutées avec succès !');
            return $this->redirectToRoute('app_card_images', ['id' => $card->getId()]);
        }

        return $this->render('card/images.html.twig', [
            'card' => $card,
            'form' => $form,
        ]);
    }

    #[Route('/image/{id}/delete', name: 'app_card_image_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteImage(
        Image $image,
        Request $request,
        ImageUploadService $imageUploadService,
        EntityManagerInterface $entityManager
    ): Response {
        $card = $image->getCard();

        if ($this->getUser() !== $card->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete-image-' . $image->getId(), $request->request->get('_token'))) {
            $imageUploadService->delete($image->getFileName());
            $entityManager->remove($image);
            $entityManager->flush();
            $this->addFlash('success', 'Photo supprimée.');
        }

        return $this->redirectToRoute('app_card_images', ['id' => $card->getId()]);
    }

    #[Route('/{id}', name: 'app_card_delete', methods: ['POST'])]
    public function delete(Request $request, Card $card, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$card->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($card);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_card_index', [], Response::HTTP_SEE_OTHER);
    }
}