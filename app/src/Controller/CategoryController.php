<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\CardRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CategoryController extends AbstractController
{
    #[Route('/categorie/{slug}', name: 'app_category')]
    public function index(string $slug, CategoryRepository $categoryRepository, CardRepository $cardRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $category = $categoryRepository->findOneBy(['slug' => $slug]);

        if (!$category) {
            throw $this->createNotFoundException('Catégorie introuvable.');
        }

        $categories = [$category];
        foreach ($category->getSubCategories() as $sub) {
            $categories[] = $sub;
        }

        $pagination = $paginator->paginate(
            $cardRepository->findByCategories($categories),
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('category/index.html.twig', [
            'category' => $category,
            'cards'    => $pagination,
            'categories' => $categoryRepository->findParentsWithChildren(),
        ]);
    }
}
