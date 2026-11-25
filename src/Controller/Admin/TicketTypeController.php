<?php

namespace App\Controller\Admin;

use App\Entity\Event;
use App\Entity\TicketType;
use App\Form\TicketTypeFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Ticket types are always managed from their event's page (composition). */
#[IsGranted('ROLE_ADMIN')]
class TicketTypeController extends AbstractController
{
    #[Route('/admin/evenements/{id<\d+>}/billets/nouveau', name: 'admin_ticket_type_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        $ticketType = new TicketType();
        // Attached before validation so the capacity check sees the event and its venue.
        $event->addTicketType($ticketType);

        $form = $this->createForm(TicketTypeFormType::class, $ticketType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($ticketType);
            $entityManager->flush();
            $this->addFlash('success', sprintf('Billet « %s » ajouté.', $ticketType->getName()));

            return $this->redirectToRoute('admin_event_show', ['id' => $event->getId()]);
        }

        return $this->render('admin/ticket_type/form.html.twig', [
            'form' => $form,
            'ticket_type' => $ticketType,
            'event' => $event,
        ]);
    }

    #[Route('/admin/billets/{id<\d+>}/modifier', name: 'admin_ticket_type_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TicketType $ticketType, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TicketTypeFormType::class, $ticketType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', sprintf('Billet « %s » modifié.', $ticketType->getName()));

            return $this->redirectToRoute('admin_event_show', ['id' => $ticketType->getEvent()->getId()]);
        }

        if ($form->isSubmitted()) {
            $entityManager->refresh($ticketType);
        }

        return $this->render('admin/ticket_type/form.html.twig', [
            'form' => $form,
            'ticket_type' => $ticketType,
            'event' => $ticketType->getEvent(),
        ]);
    }

    #[Route('/admin/billets/{id<\d+>}/supprimer', name: 'admin_ticket_type_delete', methods: ['POST'])]
    public function delete(Request $request, TicketType $ticketType, EntityManagerInterface $entityManager): Response
    {
        $event = $ticketType->getEvent();
        $redirect = $this->redirectToRoute('admin_event_show', ['id' => $event->getId()]);

        if (!$this->isCsrfTokenValid('delete-ticket-type-'.$ticketType->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('danger', 'Jeton de sécurité invalide, suppression annulée.');

            return $redirect;
        }

        if ($ticketType->getSold() > 0) {
            $this->addFlash('danger', sprintf('Impossible de supprimer « %s » : %d billet(s) déjà vendu(s).', $ticketType->getName(), $ticketType->getSold()));

            return $redirect;
        }

        // A published event must keep at least one ticket type.
        if ($event->isPublished() && $event->getTicketTypes()->count() <= 1) {
            $this->addFlash('danger', 'C\'est le dernier type de billet d\'un événement publié. Repassez d\'abord l\'événement en brouillon.');

            return $redirect;
        }

        $event->removeTicketType($ticketType);
        $entityManager->flush();
        $this->addFlash('success', sprintf('Billet « %s » supprimé.', $ticketType->getName()));

        return $redirect;
    }
}
