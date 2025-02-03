<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\ConnexionRepository;


class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/mod-statistiques', name: 'app_statistiques')]
    public function statistiques(): Response
    {
        return $this->render('admin/statistiques.html.twig', []);
    }

    #[Route('/api/user-statistics', name: 'api_user_statistics', methods: ['GET'])]
public function getStatistics(UserRepository $userRepository): Response
{
    $stats = $userRepository->getUserStatistics();

    $users = $userRepository->createQueryBuilder('u')
        ->select('u.email, u.dateEnvoi')
        ->getQuery()
        ->getArrayResult();

    // Formatage des dates
    $formattedUsers = array_map(function ($user) {
        $user['dateEnvoi'] = $user['dateEnvoi'] instanceof \DateTimeInterface
            ? $user['dateEnvoi']->format('Y-m-d\TH:i:s') // Format ISO 8601
            : null;
        return $user;
    }, $users);

    return $this->json([
        'stats' => $stats,
        'users' => $formattedUsers,
    ]);
}

#[Route('/api/connections-data', name: 'api_connections_data', methods: ['GET'])]
public function getConnectionsData(ConnexionRepository $connexionRepository): JsonResponse
{
    $connections = $connexionRepository->getConnectionsForLast31Days();

    return $this->json($connections);
}
    
}
