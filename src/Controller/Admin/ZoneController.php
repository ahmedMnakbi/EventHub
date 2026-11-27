<?php

namespace App\Controller\Admin;

use App\Entity\Venue;
use App\Entity\Zone;
use App\Form\ZoneType;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Zones are always managed from their venue's page (composition). */
#[IsGranted('ROLE_ADMIN')]
class ZoneController extends AbstractController
{
    #[Route('/admin/lieux/{id<\d+>}/zones/nouvelle', name: 'admin_zone_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Venue $venue, EntityManagerInterface $entityManager): Response
    {
        $zone = new Zone();
        // Attached before validation so the capacity check sees the other zones of the venue.
        $venue->addZone($zone);

        $form = $this->createForm(ZoneType::class, $zone);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($zone);
            $entityManager->flush();
            $this->addFlash('success', sprintf('Zone « %s » ajoutée.', $zone->getName()));

            return $this->redirectToRoute('admin_venue_show', ['id' => $venue->getId()]);
        }

        return $this->render('admin/zone/form.html.twig', [
            'form' => $form,
            'zone' => $zone,
            'venue' => $venue,
        ]);
    }

    #[Route('/admin/zones/{id<\d+>}/modifier', name: 'admin_zone_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Zone $zone, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ZoneType::class, $zone);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', sprintf('Zone « %s » modifiée.', $zone->getName()));

            return $this->redirectToRoute('admin_venue_show', ['id' => $zone->getVenue()->getId()]);
        }

        if ($form->isSubmitted()) {
            $entityManager->refresh($zone);
        }

        return $this->render('admin/zone/form.html.twig', [
            'form' => $form,
            'zone' => $zone,
            'venue' => $zone->getVenue(),
        ]);
    }

    #[Route('/admin/zones/{id<\d+>}/supprimer', name: 'admin_zone_delete', methods: ['POST'])]
    public function delete(Request $request, Zone $zone, EntityManagerInterface $entityManager, EventRepository $eventRepository): Response
    {
        $venue = $zone->getVenue();
        $redirect = $this->redirectToRoute('admin_venue_show', ['id' => $venue->getId()]);

        if (!$this->isCsrfTokenValid('delete-zone-'.$zone->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('danger', 'Jeton de sécurité invalide, suppression annulée.');

            return $redirect;
        }

        if ($zone->isUsed()) {
            $this->addFlash('danger', sprintf('Impossible de supprimer « %s » : des types de billets utilisent cette zone.', $zone->getName()));

            return $redirect;
        }

        // A venue hosting events must keep at least one zone (multiplicity 1..*).
        if ($venue->getZones()->count() <= 1 && $eventRepository->count(['venue' => $venue]) > 0) {
            $this->addFlash('danger', 'C\'est la dernière zone d\'un lieu qui accueille des événements : ajoutez d\'abord une autre zone.');

            return $redirect;
        }

        $venue->removeZone($zone);
        $entityManager->flush();
        $this->addFlash('success', sprintf('Zone « %s » supprimée.', $zone->getName()));

        return $redirect;
    }
}
