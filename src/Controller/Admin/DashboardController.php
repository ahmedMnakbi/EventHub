<?php

namespace App\Controller\Admin;

use App\Repository\EventRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'admin_dashboard', methods: ['GET'])]
    public function index(UserRepository $userRepository, EventRepository $eventRepository): Response
    {
        return $this->render('admin/dashboard/index.html.twig', [
            'userCount' => $userRepository->count([]),
            'eventCount' => $eventRepository->count([]),
        ]);
    }
}
