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
        EntityManagerInterface $em, SluggerInterface $slugger): Response {
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
        // Vérifie si l'utilisateur est propriétaire ou si le fichier lui a été partagé
        if ($fichier->getUser() !== $this->getUser() && !$fichier->getPartageAvec()->contains($this->getUser())) {
            $this->addFlash('notice', 'Vous n\'êtes pas autorisé à télécharger ce fichier.');
            return $this->redirectToRoute('app_profil');
        }

        // Retourne le fichier pour téléchargement
        return $this->file(
            $this->getParameter('file_directory') . '/' . $fichier->getNomServeur(),
            $fichier->getNomOriginal()
        );
    }

    #[Route('/private-partager-fichiers', name: 'app_partager_fichiers')]
    public function partagerFichiers(
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        // Récupérer l'utilisateur connecté et ses fichiers
        $user = $this->getUser();
        $fichiers = $user->getFichiers(); // Récupère tous les fichiers de l'utilisateur connecté

        // Récupérer les amis de l'utilisateur
        $amis = $user->getAccepter(); //  Récupère la liste des amis acceptés.

        // Gestion de la soumission du formulaire
        if ($request->isMethod('POST')) {
            $data = $request->request->all(); // On récupère les fichiers et amis sélectionnés

            if (isset($data['amis']) && isset($data['fichiers'])) {
                $amisSelectionnes = $data['amis'];
                $fichiersSelectionnes = $data['fichiers'];

                foreach ($fichiersSelectionnes as $fichierId) {
                    $fichier = $em->getRepository(Fichier::class)->find($fichierId);
                    if ($fichier && $fichier->getUser() === $user) {
                        foreach ($amisSelectionnes as $amiId) {
                            $ami = $userRepository->find($amiId);
                            if ($ami) {
                                $fichier->addPartageAvec($ami);
                            }
                        }
                        $em->persist($fichier);
                    }
                }
                $em->flush(); // Permet de sauvegarder les modifications en bdd

                $this->addFlash('success', 'Fichiers partagés avec succès.');
                return $this->redirectToRoute('app_partager_fichiers');
            }
        }

        return $this->render('fichier/partager-fichiers.html.twig', [
            'fichiers' => $fichiers,
            'amis' => $amis,
        ]);
    }

#[Route('/private-annuler-partage/{fichierId}/{amiId}', name: 'app_annuler_partage')]
public function annulerPartage(int $fichierId, int $amiId, EntityManagerInterface $em, UserRepository $userRepository): Response
{
    // Récupérer le fichier par son ID
    $fichier = $em->getRepository(Fichier::class)->find($fichierId);

    // Vérifier si le fichier existe et que l'utilisateur connecté en est le propriétaire
    if (!$fichier || $fichier->getUser() !== $this->getUser()) {
        $this->addFlash('error', 'Vous n\'êtes pas autorisé à annuler ce partage.');
        return $this->redirectToRoute('app_profil');
    }

    // Récupérer l'ami par son ID
    $ami = $userRepository->find($amiId);

    if ($ami && $fichier->getPartageAvec()->contains($ami)) {
        // Retirer l'ami de la liste des partages
        $fichier->removePartageAvec($ami);

        // Persister les changements
        $em->persist($fichier);
        $em->flush();

        $this->addFlash('success', 'Partage annulé avec succès.');
    } else {
        $this->addFlash('error', 'L\'ami sélectionné ne figure pas dans les partages de ce fichier.');
    }

    // Redirection vers le profil ou une page spécifique
    return $this->redirectToRoute('app_profil');
}

}
