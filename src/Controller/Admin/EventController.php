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
        $form = $this->createForm(EventType::class, $event, ['images_required' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->storeImages($form, $event) !== null) {
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
        $form = $this->createForm(EventType::class, $event, ['existing_images' => $event->getImages()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && null !== $removed = $this->storeImages($form, $event)) {
            $entityManager->flush();

            // Removed files are deleted from the disk only once the database is up to date.
            foreach ($removed as $fileName) {
                $this->fileUploader->remove($fileName);
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

        // Step 6 (reservations) adds: refuse when the event has reservations (cancel it instead).

        $images = $event->getImages();
        $entityManager->remove($event);
        $entityManager->flush();
        foreach ($images as $fileName) {
            $this->fileUploader->remove($fileName);
        }
        $this->addFlash('success', sprintf('Événement « %s » supprimé.', $event->getTitle()));

        return $this->redirectToRoute('admin_event_index');
    }

    /**
     * Applies the image changes of the form: new uploads and removals.
     * Returns the removed file names (to delete from disk after flush), or null with a form error.
     *
     * @return list<string>|null
     */
    private function storeImages(FormInterface $form, Event $event): ?array
    {
        /** @var list<UploadedFile> $files */
        $files = $form->get('imageFiles')->getData() ?? [];
        /** @var list<string> $removed */
        $removed = $form->has('removeImages') ? ($form->get('removeImages')->getData() ?? []) : [];

        $total = count($event->getImages()) - count($removed) + count($files);
        if ($total < 1) {
            $form->get('imageFiles')->addError(new FormError('Un événement doit garder au moins une image.'));

            return null;
        }
        if ($total > Event::MAX_IMAGES) {
            $form->get('imageFiles')->addError(new FormError(sprintf('Au plus %d images par événement (il y en aurait %d).', Event::MAX_IMAGES, $total)));

            return null;
        }

        $uploaded = [];
        try {
            foreach ($files as $file) {
                $uploaded[] = $this->fileUploader->upload($file);
            }
        } catch (FileException) {
            // Roll back the files already moved, so no orphan stays on disk.
            array_map($this->fileUploader->remove(...), $uploaded);
            $form->get('imageFiles')->addError(new FormError('Les images n\'ont pas pu être enregistrées, réessayez.'));

            return null;
        }

        foreach ($removed as $fileName) {
            $event->removeImage($fileName);
        }
        foreach ($uploaded as $fileName) {
            $event->addImage($fileName);
        }

        return $removed;
    }
}
