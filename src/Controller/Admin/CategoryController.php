<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/categories')]
#[IsGranted('ROLE_ADMIN')]
class CategoryController extends AbstractController
{
    use SafePaginationTrait;

    #[Route('', name: 'admin_category_index', methods: ['GET'])]
    public function index(Request $request, CategoryRepository $repository, PaginatorInterface $paginator): Response
    {
        $search = $request->query->getString('q');

        // Only these columns may be used for sorting (prevents injecting arbitrary fields).
        $categories = $this->paginateSafely($paginator, $repository->createAdminListQueryBuilder($search), $request, ['c.name', 'c.createdAt']);

        return $this->render('admin/category/index.html.twig', [
            'categories' => $categories,
            'search' => $search,
        ]);
    }

    #[Route('/nouvelle', name: 'admin_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();
            $this->addFlash('success', sprintf('Catégorie « %s » créée.', $category->getName()));

            return $this->redirectToRoute('admin_category_index');
        }

        return $this->render('admin/category/form.html.twig', [
            'form' => $form,
            'category' => $category,
        ]);
    }

    #[Route('/{id<\d+>}/modifier', name: 'admin_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', sprintf('Catégorie « %s » modifiée.', $category->getName()));

            return $this->redirectToRoute('admin_category_index');
        }

        return $this->render('admin/category/form.html.twig', [
            'form' => $form,
            'category' => $category,
        ]);
    }

    #[Route('/{id<\d+>}/supprimer', name: 'admin_category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category, EntityManagerInterface $entityManager, EventRepository $eventRepository): Response
    {
        if (!$this->isCsrfTokenValid('delete-category-'.$category->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('danger', 'Jeton de sécurité invalide, suppression annulée.');

            return $this->redirectToRoute('admin_category_index');
        }

        // Events cannot lose their category: refuse instead of a database error (foreign key).
        $eventCount = $eventRepository->count(['category' => $category]);
        if ($eventCount > 0) {
            $this->addFlash('danger', sprintf('Impossible de supprimer « %s » : %d événement(s) utilisent cette catégorie.', $category->getName(), $eventCount));

            return $this->redirectToRoute('admin_category_index');
        }

        $entityManager->remove($category);
        $entityManager->flush();
        $this->addFlash('success', sprintf('Catégorie « %s » supprimée.', $category->getName()));

        return $this->redirectToRoute('admin_category_index');
    }
}
