<?php

namespace App\Controller;

use App\Entity\Fichier;
use App\Form\AjoutfichierType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

class FichierController extends AbstractController
{
    #[Route('/fichier', name: 'app_fichier')]
    public function index(): Response
    {
        return $this->render('fichier/index.html.twig', [
        ]);
    }
    #[Route('/mod-ajout-fichier', name: 'app_ajout_fichier')]
    public function ajoutfichier(
        Request $request, 
        EntityManagerInterface $em
        ): Response
    {
        $ficherToSave = new Fichier();
        $form = $this->createForm(AjoutfichierType::class, $ficherToSave);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $ficherToSave->setDateEnvoi(new \Datetime());

                $em->persist($ficherToSave);
                $em->flush();

                $this->addFlash('notice', 'Message envoyé');

                return $this->redirectToRoute('app_ajout_fichier');
            }
        }

        return $this->render('fichier/ajout-fichier.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/mod-liste-fichiers', name: 'app_liste_fichiers')]
    public function listefichier(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        return $this->render('fichier/liste-fichiers.html.twig', [
            'users' => $users,
        ]);
    }
}
