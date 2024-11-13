<?php

namespace App\Controller;

use App\Entity\Fichier;
use App\Form\FichierType;
use App\Repository\ScategorieRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class FichierController extends AbstractController
{
    #[Route('/fichier', name: 'app_fichier')]
    public function index(): Response
    {
        return $this->render('fichier/index.html.twig', []);
    }

    #[Route('/ajout-fichier', name: 'app_ajout_fichier')]
    public function ajoutFichier(Request $request, ScategorieRepository $scategorieRepository,
        EntityManagerInterface $em, SluggerInterface $slugger): Response 
    {
        $fichier = new Fichier();
        $scategories = $scategorieRepository->findBy([], ['categorie' => 'asc', 'numero' => 'asc']);

        $form = $this->createForm(FichierType::class, $fichier, ['scategories' => $scategories]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedScategories = $form->get('scategories')->getData();
            foreach ($selectedScategories as $scategorie) {
                $fichier->addScategory($scategorie);
            }

            $file = $form->get('fichier')->getData();
            if ($file) {
                $nomFichierServeur = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $nomFichierServeur = $slugger->slug($nomFichierServeur) . '-' . uniqid() . '.' . $file->guessExtension();

                try {
                    $fichier->setNomServeur($nomFichierServeur);
                    $fichier->setNomOriginal($file->getClientOriginalName());
                    $fichier->setDateEnvoi(new \Datetime());
                    $fichier->setExtension($file->guessExtension());
                    $fichier->setTaille($file->getSize());

                    $em->persist($fichier);
                    $em->flush();

                    $file->move($this->getParameter('file_directory'), $nomFichierServeur);
                    $this->addFlash('notice', 'Fichier envoyé');
                    return $this->redirectToRoute('app_ajout_fichier');
                } catch (FileException $e) {
                    $this->addFlash('notice', 'Erreur d\'envoi');
                }
            }
        }

        return $this->render('fichier/ajout-fichier.html.twig', [
            'form' => $form->createView(),
            'scategories' => $scategories,
        ]);
    }

    #[Route('/mod-liste-fichiers', name: 'app_liste_fichiers')]
    public function listeFichier(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        return $this->render('fichier/liste-fichiers.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/liste-fichiers-par-utilisateur', name: 'app_liste_fichiers_par_utilisateur')]
    public function listeFichiersParUtilisateur(UserRepository $userRepository): Response
    {
        $users = $userRepository->findBy([], ['nom' => 'asc', 'prenom' => 'asc']);
        return $this->render('fichier/liste-fichiers-par-utilisateur.html.twig', ['users' => $users]);
    }

    #[Route('/private-telechargement-fichier/{id}', name: 'app_telechargement_fichier', requirements: ["id" => "\d+"])]
    public function telechargementFichier(Fichier $fichier): Response
    {
        if ($fichier == null) {
            return $this->redirectToRoute('app_liste_fichiers_par_utilisateur');
        }

        if ($fichier->getUser() !== $this->getUser()) {
            $this->addFlash('notice', 'Vous n\'êtes pas le propriétaire de ce fichier');
            return $this->redirectToRoute('app_profil');
        }

        return $this->file(
            $this->getParameter('file_directory') . '/' . $fichier->getNomServeur(),
            $fichier->getNomOriginal()
        );
    }
}
