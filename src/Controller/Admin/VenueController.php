<?php

namespace App\Controller\Admin;

use App\Entity\Venue;
use App\Form\VenueType;
use App\Repository\VenueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/lieux')]
#[IsGranted('ROLE_ADMIN')]
class VenueController extends AbstractController
{
    use SafePaginationTrait;

    #[Route('', name: 'admin_venue_index', methods: ['GET'])]
    public function index(Request $request, VenueRepository $repository, PaginatorInterface $paginator): Response
    {
        $search = $request->query->getString('q');
        $city = $request->query->getString('city');

        $venues = $this->paginateSafely($paginator, $repository->createAdminListQueryBuilder($search, $city), $request, ['v.name', 'v.city', 'v.capacity']);

        return $this->render('admin/venue/index.html.twig', [
            'venues' => $venues,
            'search' => $search,
            'city' => $city,
            'cities' => $repository->findDistinctCities(),
        ]);
    }

    #[Route('/nouveau', name: 'admin_venue_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $venue = new Venue();
        $form = $this->createForm(VenueType::class, $venue);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($venue);
            $entityManager->flush();
            $this->addFlash('success', sprintf('Lieu « %s » créé.', $venue->getName()));

            return $this->redirectToRoute('admin_venue_index');
        }

        return $this->render('admin/venue/form.html.twig', [
            'form' => $form,
            'venue' => $venue,
        ]);
    }

    #[Route('/{id<\d+>}/modifier', name: 'admin_venue_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Venue $venue, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(VenueType::class, $venue);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', sprintf('Lieu « %s » modifié.', $venue->getName()));

            return $this->redirectToRoute('admin_venue_index');
        }

        return $this->render('admin/venue/form.html.twig', [
            'form' => $form,
            'venue' => $venue,
        ]);
    }

    #[Route('/{id<\d+>}/supprimer', name: 'admin_venue_delete', methods: ['POST'])]
    public function delete(Request $request, Venue $venue, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete-venue-'.$venue->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('danger', 'Jeton de sécurité invalide, suppression annulée.');

            return $this->redirectToRoute('admin_venue_index');
        }

        $entityManager->remove($venue);
        $entityManager->flush();
        $this->addFlash('success', sprintf('Lieu « %s » supprimé.', $venue->getName()));

        return $this->redirectToRoute('admin_venue_index');
    }
}
