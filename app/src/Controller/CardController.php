<?php

namespace App\Controller;

use App\Entity\Card;
use App\Enum\CardState;
use App\Form\CardType;
use App\Repository\CardRepository;
use App\Repository\CategoryRepository;
use App\Repository\StudyFieldRepository;
use App\Entity\Report;
use App\Form\ReportType;
use App\Entity\Image;
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
        // Onglet actif : 'all' ou 'drafts'
        $tab = $request->query->get('tab', 'all');

        // Brouillons de l'utilisateur connecté
        $draftCount = 0;
        if ($this->getUser()) {
            $draftCount = $cardRepository->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.state = :state')
                ->andWhere('c.user = :user')
                ->setParameter('state', CardState::DRAFT)
                ->setParameter('user', $this->getUser())
                ->getQuery()
                ->getSingleScalarResult();
        }

        if ($tab === 'drafts' && $this->getUser()) {
            $query = $cardRepository->createQueryBuilder('c')
                ->where('c.state = :state')
                ->andWhere('c.user = :user')
                ->setParameter('state', CardState::DRAFT)
                ->setParameter('user', $this->getUser())
                ->orderBy('c.createdAt', 'DESC')
                ->getQuery();
        } else {
            $qb = $cardRepository->createQueryBuilder('c');

            if ($this->isGranted('ROLE_ADMIN')) {
                // L'admin voit tout sauf les brouillons
                $qb->where('c.state != :draft')
                    ->setParameter('draft', CardState::DRAFT);
            } elseif ($this->getUser()) {
                // L'utilisateur connecté voit les cartes publiées (sans message signalé, sauf s'il est l'auteur), et SES cartes signalées
                $qb->where('c.state = :published AND (c.user = :user OR NOT EXISTS (SELECT 1 FROM App\Entity\Message m WHERE m.card = c AND m.state = :msg_flagged))')
                    ->orWhere('c.state = :flagged AND c.user = :user')
                    ->setParameter('published', CardState::PUBLISHED)
                    ->setParameter('flagged', CardState::FLAGGED)
                    ->setParameter('msg_flagged', \App\Enum\MessageState::FLAGGED)
                    ->setParameter('user', $this->getUser());
            } else {
                // Visiteur non connecté : uniquement le publié sans message signalé
                $qb->where('c.state = :published AND NOT EXISTS (SELECT 1 FROM App\Entity\Message m WHERE m.card = c AND m.state = :msg_flagged)')
                    ->setParameter('published', CardState::PUBLISHED)
                    ->setParameter('msg_flagged', \App\Enum\MessageState::FLAGGED);
            }

            $query = $qb->orderBy('c.createdAt', 'DESC')->getQuery();
        }

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('card/index.html.twig', [
            'cards'      => $pagination,
            'activeTab'  => $tab,
            'draftCount' => $draftCount,
        ]);
    }

    #[Route('/search', name: 'card_search', methods: ['GET'])]
    public function search(Request $request, CardRepository $cardRepository): Response
    {
        $recherche = $request->query->get('query', '');

        $user = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if (!empty(trim($recherche))) {
            $cards = $cardRepository->searchFunction($recherche, $user, $isAdmin);
        } else {
            // Utiliser la même logique que l'index pour findAll
            $qb = $cardRepository->createQueryBuilder('c');
            if ($isAdmin) {
                $qb->where('c.state != :draft')
                    ->setParameter('draft', CardState::DRAFT);
            } elseif ($user) {
                $qb->where('c.state = :published AND (c.user = :user OR NOT EXISTS (SELECT 1 FROM App\Entity\Message m WHERE m.card = c AND m.state = :msg_flagged))')
                    ->orWhere('c.state = :flagged AND c.user = :user')
                    ->setParameter('published', CardState::PUBLISHED)
                    ->setParameter('flagged', CardState::FLAGGED)
                    ->setParameter('msg_flagged', \App\Enum\MessageState::FLAGGED)
                    ->setParameter('user', $user);
            } else {
                $qb->where('c.state = :published AND NOT EXISTS (SELECT 1 FROM App\Entity\Message m WHERE m.card = c AND m.state = :msg_flagged)')
                    ->setParameter('published', CardState::PUBLISHED)
                    ->setParameter('msg_flagged', \App\Enum\MessageState::FLAGGED);
            }
            $cards = $qb->orderBy('c.createdAt', 'DESC')->getQuery()->getResult();
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
        StudyFieldRepository $studyFieldRepository,
        CategoryRepository $categoryRepository
    ): Response {
        $card = new Card();
        $card->setUser($this->getUser());

        $form = $this->createForm(CardType::class, $card);
        $form->handleRequest($request);

        $isDraft = $request->request->has('save_as_draft');

        // ── Brouillon : bypass la validation, seul le titre est obligatoire ──
        if ($form->isSubmitted() && $isDraft) {
            $title = trim($request->request->all('card')['title'] ?? '');

            if (empty($title)) {
                $this->addFlash('error', 'Le titre est obligatoire même pour un brouillon.');
                return $this->render('card/new.html.twig', [
                    'card'               => $card,
                    'form'               => $form,
                    'studyFieldsGrouped' => $studyFieldRepository->findAllGrouped(),
                    'categories'         => $categoryRepository->findParentsWithChildren(),
                ]);
            }

            $card->setTitle($title);
            $card->setDescription($request->request->all('card')['description'] ?? '');
            $card->setState(CardState::DRAFT);

            $entityManager->persist($card);

            $files = $form->get('imageFiles')->getData() ?? [];
            $files = array_slice($files, 0, 10);
            foreach ($files as $file) {
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

            $this->addFlash('success', 'Brouillon enregistré. Tu pourras le publier depuis "Mes brouillons".');
            return $this->redirectToRoute('app_card_index', ['tab' => 'drafts'], Response::HTTP_SEE_OTHER);
        }

        // ── Publication normale : validation complète ──
        if ($form->isSubmitted() && $form->isValid()) {
            $card->setState(CardState::PUBLISHED);
            $entityManager->persist($card);

            $files = $form->get('imageFiles')->getData();
            $files = array_slice($files, 0, 10);
            foreach ($files as $file) {
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
            'card'               => $card,
            'form'               => $form,
            'studyFieldsGrouped' => $studyFieldRepository->findAllGrouped(),
            'categories'         => $categoryRepository->findParentsWithChildren(),
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

        // Bloquer l'accès aux brouillons pour les non-propriétaires
        if ($card->getState() === CardState::DRAFT && $card->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'Cette annonce n\'est pas encore publiée.');
            return $this->redirectToRoute('app_card_index');
        }

        // Bloquer l'accès aux annonces signalées pour les non-propriétaires et non-admins
        if ($card->getState() === CardState::FLAGGED && $card->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Cette annonce a été signalée et est en attente de modération.');
            return $this->redirectToRoute('app_card_index');
        }

        $hasFlaggedMessage = false;
        foreach ($card->getMessages() as $msg) {
            if ($msg->getState() === \App\Enum\MessageState::FLAGGED) {
                $hasFlaggedMessage = true;
                break;
            }
        }

        // Bloquer l'accès aux annonces contenant un message signalé pour les non-propriétaires et non-admins
        if ($hasFlaggedMessage && $card->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Cette annonce est temporairement masquée suite au signalement d\'un message.');
            return $this->redirectToRoute('app_card_index');
        }

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
        $messageForm = $this->createForm(\App\Form\MessageType::class, $message, [
            'action' => $this->generateUrl('app_message_new', ['id' => $card->getId()]),
            'method' => 'POST',
        ]);

        $reportForm = $this->createForm(ReportType::class, new Report());

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
            'card'         => $card,
            'message_form' => $messageForm->createView(),
            'report_form'  => $reportForm->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_card_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(
        Request $request,
        Card $card,
        EntityManagerInterface $entityManager,
        ImageUploadService $imageUploadService,
        StudyFieldRepository $studyFieldRepository,
        CategoryRepository $categoryRepository
    ): Response {
        if ($this->getUser() !== $card->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous n\'êtes pas le propriétaire de cette annonce.');
            return $this->redirectToRoute('app_card_show', ['id' => $card->getId()]);
        }

        $form = $this->createForm(CardType::class, $card);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $formData = $request->request->all('image_carte') ?? [];
            $deleteIds = $formData['delete_images'] ?? [];
            foreach ($deleteIds as $imageId) {
                $image = $entityManager->getRepository(Image::class)->find($imageId);
                if ($image && $image->getCard() === $card) {
                    $imageUploadService->delete($image->getFileName());
                    $entityManager->remove($image);
                }
            }

            $imageFiles = $form->get('imageFiles')->getData();
            if ($imageFiles) {
                foreach ($imageFiles as $file) {
                    try {
                        $result = $imageUploadService->upload($file);
                        $image = new Image();
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

            // Si c'était un brouillon, on peut aussi le publier depuis l'édition
            $publishOnSave = $request->request->has('publish_on_save');
            if ($publishOnSave) {
                $card->setState(CardState::PUBLISHED);
                $card->setCreatedAt(new \DateTimeImmutable());
            } elseif ($card->getState() === CardState::DRAFT) {
                // On garde le brouillon en brouillon
                $card->setState(CardState::DRAFT);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Annonce mise à jour avec succès !');
            return $this->redirectToRoute('app_card_show', ['id' => $card->getId()], Response::HTTP_SEE_OTHER);
        }

        $response = new Response(
            null,
            $form->isSubmitted() && !$form->isValid()
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_OK
        );

        return $this->render('card/edit.html.twig', [
            'card'               => $card,
            'form'               => $form,
            'studyFieldsGrouped' => $studyFieldRepository->findAllGrouped(),
            'categories'         => $categoryRepository->findParentsWithChildren(),
        ], $response);
    }

    /**
     * Publier un brouillon en un clic (sans passer par le formulaire d'édition)
     */
    #[Route('/{id}/publish', name: 'app_card_publish', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function publish(
        Card $card,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->getUser() !== $card->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous n\'êtes pas le propriétaire de cette annonce.');
            return $this->redirectToRoute('app_card_index', ['tab' => 'drafts']);
        }

        if (!$this->isCsrfTokenValid('publish' . $card->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_card_index', ['tab' => 'drafts']);
        }

        $card->setState(CardState::PUBLISHED);
        $card->setCreatedAt(new \DateTimeImmutable());
        $entityManager->flush();

        $this->addFlash('success', 'Annonce publiée avec succès !');
        return $this->redirectToRoute('app_card_show', ['id' => $card->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/delete', name: 'app_card_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(
        Request $request,
        Card $card,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->getUser() !== $card->getUser() && !$this->isGranted('ROLE_ADMIN')) {
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
