<?php

namespace App\Controller\Admin;

use App\Entity\Event;
use App\Enum\EventStatus;
use App\Form\EventType;
use App\Repository\CategoryRepository;
use App\Repository\EventRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/evenements')]
#[IsGranted('ROLE_ADMIN')]
class EventController extends AbstractController
{
    use SafePaginationTrait;

    public function __construct(private readonly FileUploader $fileUploader)
    {
    }

    #[Route('', name: 'admin_event_index', methods: ['GET'])]
    public function index(Request $request, EventRepository $repository, CategoryRepository $categoryRepository, PaginatorInterface $paginator): Response
    {
        $search = $request->query->getString('q');
        $categoryId = $request->query->getInt('category') ?: null;
        // tryFrom() returns null for any unknown value: no arbitrary input reaches the query.
        $status = EventStatus::tryFrom($request->query->getString('status'));

        $events = $this->paginateSafely(
            $paginator,
            $repository->createAdminListQueryBuilder($search, $categoryId, $status),
            $request,
            ['e.title', 'e.startAt', 'e.status', 'v.city'],
        );

        return $this->render('admin/event/index.html.twig', [
            'events' => $events,
            'search' => $search,
            'categories' => $categoryRepository->findBy([], ['name' => 'ASC']),
            'category_id' => $categoryId,
            'status' => $status,
            'statuses' => EventStatus::cases(),
        ]);
    }

    #[Route('/nouveau', name: 'admin_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event, ['poster_required' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->storePoster($form, $event)) {
            $entityManager->persist($event);
            $entityManager->flush();
            $this->addFlash('success', sprintf('Événement « %s » créé. Ajoutez maintenant ses types de billets.', $event->getTitle()));

            return $this->redirectToRoute('admin_event_show', ['id' => $event->getId()]);
        }

        return $this->render('admin/event/form.html.twig', [
            'form' => $form,
            'event' => $event,
        ]);
    }

    #[Route('/{id<\d+>}', name: 'admin_event_show', methods: ['GET'])]
    public function show(Event $event): Response
    {
        return $this->render('admin/event/show.html.twig', ['event' => $event]);
    }

    #[Route('/{id<\d+>}/modifier', name: 'admin_event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        $oldPoster = $event->getImage();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->storePoster($form, $event)) {
            $entityManager->flush();

            // The old poster is deleted only once the new one is safely saved.
            if ($oldPoster !== $event->getImage()) {
                $this->fileUploader->remove($oldPoster);
            }
            $this->addFlash('success', sprintf('Événement « %s » modifié.', $event->getTitle()));

            return $this->redirectToRoute('admin_event_show', ['id' => $event->getId()]);
        }

        if ($form->isSubmitted()) {
            // Invalid form: undo the in-memory changes so the page shows the saved data.
            $entityManager->refresh($event);
        }

        return $this->render('admin/event/form.html.twig', [
            'form' => $form,
            'event' => $event,
        ]);
    }

    #[Route('/{id<\d+>}/supprimer', name: 'admin_event_delete', methods: ['POST'])]
    public function delete(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete-event-'.$event->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('danger', 'Jeton de sécurité invalide, suppression annulée.');

            return $this->redirectToRoute('admin_event_index');
        }

        // Business rule: an event with sold tickets is kept (history of reservations). Cancel it instead.
        if ($event->hasSales()) {
            $this->addFlash('danger', sprintf('Impossible de supprimer « %s » : des billets ont déjà été vendus. Passez-le plutôt au statut « Annulé ».', $event->getTitle()));

            return $this->redirectToRoute('admin_event_show', ['id' => $event->getId()]);
        }

        $poster = $event->getImage();
        $entityManager->remove($event);
        $entityManager->flush();
        $this->fileUploader->remove($poster);
        $this->addFlash('success', sprintf('Événement « %s » supprimé.', $event->getTitle()));

        return $this->redirectToRoute('admin_event_index');
    }

    /** Saves the uploaded poster, if any. Returns false (with a form error) when the move fails. */
    private function storePoster(FormInterface $form, Event $event): bool
    {
        $file = $form->get('posterFile')->getData();
        if (!$file instanceof UploadedFile) {
            return true;
        }

        try {
            $event->setImage($this->fileUploader->upload($file));
        } catch (FileException) {
            $form->get('posterFile')->addError(new FormError('L\'affiche n\'a pas pu être enregistrée, réessayez.'));

            return false;
        }

        return true;
    }
}
