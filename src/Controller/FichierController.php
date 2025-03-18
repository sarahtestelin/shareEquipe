<?php

namespace App\Controller;

use App\Entity\Fichier;
use App\Form\FichierForm;
use App\Repository\ScategorieRepository;
use App\Repository\CategoriePersoRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

class FichierController extends AbstractController
{
    #[Route('/fichier', name: 'app_fichier')]
    public function index(): Response
    {
        return $this->render('fichier/index.html.twig', []);
    }


    
    #[Route('/ajout-fichier', name: 'app_ajout_fichier')]
    public function ajoutFichier(
        Request $request,
        ScategorieRepository $scategorieRepository,
        CategoriePersoRepository $categoriePersoRepo,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $fichier = new Fichier();
        $scategories = $scategorieRepository->findBy([], ['categorie' => 'asc', 'numero' => 'asc']);
        $categoriesp = $categoriePersoRepo->findBy([], ['categoriep' => 'asc']);

        $form = $this->createForm(FichierForm::class, $fichier, ['scategories' => $scategories]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedCategoriesP = $form->get('categoriep')->getData();
            foreach ($selectedCategoriesP as $categoriesp) {
                $fichier->addScategory($categoriesp);
            }

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

        $user = $this->getUser(); // Récupérer l'utilisateur connecté et ses fichiers
        $fichiers = $user->getFichiers(); // Récupère tous les fichiers de l'utilisateur connecté

        // Récupérer les amis de l'utilisateur
        $amis = $user->getAccepter(); //  Récupère la liste des amis acceptés.

        // Gestion de la soumission du formulaire
        if ($request->isMethod('POST')) { // Récupère les données soumises par le formulaire via la requête POST.  
            $data = $request->request->all(); // On récupère les fichiers et amis sélectionnés

            if (isset($data['amis']) && isset($data['fichiers'])) { // Vérifie si les champs "amis" et "fichiers" sont présents dans les données soumises.
                $amisSelectionnes = $data['amis']; // Récupère les identifiants des amis sélectionnés pour le partage.
                $fichiersSelectionnes = $data['fichiers']; // Récupère les identifiants des fichiers sélectionnés pour le partage.

                foreach ($fichiersSelectionnes as $fichierId) { // Boucle sur chaque fichier sélectionné pour traiter son partage.
                    $fichier = $em->getRepository(Fichier::class)->find($fichierId); // Recherche en base de données l'entité Fichier correspondant à l'ID $ficherId
                    if ($fichier && $fichier->getUser() === $user) { // Vérifie qur le fichier existe et qu'il appartient bien à l'user connecté
                        foreach ($amisSelectionnes as $amiId) {  // Parcourt la liste des amis sélectionnés dans le formulaire soumis avec une boucle
                            $ami = $userRepository->find($amiId); // Recherche en base de données l'utilisateur correspondant à l'ID $amiId
                            if ($ami) { // Vérifie que l'utilisateur (ami) a été trouvé en base de données que'$ami n'est pas null
                                $fichier->addPartageAvec($ami);  // Si il est pas null ça ajoute cet ami à la liste des utilisateurs avec lesquels ce fichier est partagé.
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



    #[Route('/delete-fichier/{id}', name: 'app_delete_fichier')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function deleteFichier(int $id, EntityManagerInterface $em): Response
    {
        $fichier = $em->getRepository(Fichier::class)->find($id);

        if (!$fichier || $fichier->getUser() !== $this->getUser()) {
            $this->addFlash('error', 'Fichier non trouvé ou accès non autorisé.');
            return $this->redirectToRoute('app_profil');
        }

        $em->remove($fichier);
        $em->flush();

        $this->addFlash('success', 'Fichier supprimé avec succès.');
        return $this->redirectToRoute('app_profil');
    }
}
