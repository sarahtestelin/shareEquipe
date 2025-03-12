<?php

namespace App\Controller;

use App\Entity\Fichier;
use App\Form\AmiForm;
use App\Form\FichierUserForm;
use App\Repository\ScategorieRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

final class UtilisateurController extends AbstractController
{
    #[Route('/mod-liste-utilisateurs', name: 'app_liste_utilisateurs')]
    public function listeUtilisateurs(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        return $this->render('base/listeutilisateurs.html.twig', [
            'users' => $users,
        ]);
    }


    
    #[Route('/private-profil', name: 'app_profil')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function profil(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        ScategorieRepository $scategorieRepository
    ): Response {
        $fichier = new Fichier();
        $form = $this->createForm(FichierUserForm::class, $fichier, [
            'scategories' => $scategorieRepository->findAll(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('fichier')->getData();

            if ($uploadedFile && $uploadedFile->isValid()) {
                $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

                try {
                    $fichier->setNomOriginal($originalFilename);
                    $fichier->setNomServeur($newFilename);
                    $fichier->setExtension($uploadedFile->guessExtension());
                    $fichier->setTaille($uploadedFile->getSize());
                    $fichier->setUser($this->getUser());
                    $fichier->setDateEnvoi(new \DateTime());

                    $selectedScategories = $form->get('scategories')->getData();
                    foreach ($selectedScategories as $scategorie) {
                        $fichier->addScategory($scategorie);
                    }

                    $em->persist($fichier);
                    $em->flush();

                    $uploadedFile->move(
                        $this->getParameter('file_directory'),
                        $newFilename
                    );

                    $this->addFlash('success', 'Fichier ajouté avec succès.');
                    return $this->redirectToRoute('app_profil');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du fichier : ' . $e->getMessage());
                }
            }
        }

        // Récupérer les fichiers de l'utilisateur connecté
        $userFiles = $em->getRepository(Fichier::class)->findBy(['user' => $this->getUser()]);

        // Récupérer les fichiers partagés avec l'utilisateur connecté
        $fichiersPartages = $em->getRepository(Fichier::class)->createQueryBuilder('f')
            ->innerJoin('f.partageAvec', 'u')
            ->where('u.id = :userId')
            ->setParameter('userId', $this->getUser()->getId())
            ->getQuery()
            ->getResult();

        return $this->render('base/profil.html.twig', [
            'form' => $form->createView(),
            'myuser' => $this->getUser(),
            'userFiles' => $userFiles,
            'fichiersPartages' => $fichiersPartages, // Ajouter les fichiers partagés à la vue
            'scategories' => $scategorieRepository->findAll(),
        ]);
    }



    #[Route('/private-amis', name: 'app_amis')]
    public function amis(Request $request, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        if ($request->get('id') != null) {
            $id = $request->get('id');
            $userDemande = $userRepository->find($id);
            if ($userDemande) {
                $this->getUser()->removeDemander($userDemande);
                $em->persist($this->getUser());
                $em->flush();
            }
        }
        if ($request->get('idRefuser') != null) {
            $id = $request->get('idRefuser');
            $userRefuser = $userRepository->find($id);
            if ($userRefuser) {
                $this->getUser()->removeUsersDemande($userRefuser);
                $em->persist($this->getUser());
                $em->flush();
            }
        }
        if ($request->get('idAccepter') != null) {
            $id = $request->get('idAccepter');
            $userAccepter = $userRepository->find($id);
            if ($userAccepter) {
                $this->getUser()->addAccepter($userAccepter);
                $userAccepter->addAccepter($this->getUser());
                $this->getUser()->removeUsersDemande($userAccepter);
                $em->persist($this->getUser());
                $em->persist($userAccepter);
                $em->flush();
            }
        }
        $form = $this->createForm(AmiForm::class);
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $ami = $userRepository->findOneBy(array('email' => $form->get('email')->getData()));
                if (!$ami) {
                    $this->addFlash('notice', 'Ami introuvable');
                    return $this->redirectToRoute('app_amis');
                } else {
                    $this->getUser()->addDemander($ami);
                    $em->persist($this->getUser());
                    $em->flush();
                    $this->addFlash('notice', 'Invitation envoyée');
                    return $this->redirectToRoute('app_amis');
                }

            }
        }
        return $this->render('base/amis.html.twig', [
            'form' => $form,
        ]);
    }
    


    #[Route('/supprimer-ami/{id}', name: 'app_supprimer_ami')] // le id permet de passer l'id de l'user à supprimer
    #[IsGranted('IS_AUTHENTICATED_FULLY')] // Seul des utilisateurs connectés peuvent accéder à la route
    public function supprimerAmi(int $id, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        // Récupérer l'utilisateur actuel et l'ami à supprimer
        $user = $this->getUser(); // Récupérer l'utilisateur actuellement connecté
        $ami = $userRepository->find($id); // Recherche dans la base de données l'utilisateur correspondant à l'id passé dans l'url

        if (!$ami || !$user->getAccepter()->contains($ami)) { // vérifie si un utilisateur avec cet id existe ou si l'ami fait parti de la liste d'ami acceptés de l'utilisateur connecté
            $this->addFlash('error', 'Ami introuvable ou non autorisé.'); // si l'une des conditions est vraie un message flash se met et l'utilisateur est redirigé vers la page amis
            return $this->redirectToRoute('app_amis');
        }

        // Suppression de la relation d'amitié (réciproque)
        $user->removeAccepter($ami); // retire l'ami de la liste d'ami de l'utilisateur connecté
        $ami->removeAccepter($user); // y'a une double suppression parce que la relation entre amis est bidirectionnelle donc ca doit etre supprimé des deux cotés

        // Sauvegarde des modifications et le flush met à jour la bdd
        $em->persist($user);
        $em->persist($ami);
        $em->flush();

        $this->addFlash('success', 'Ami supprimé avec succès.'); // une fois que tout a été fait un messsage flash se met pour indiqué que l'ami a bien été supprimé
        return $this->redirectToRoute('app_amis');
    }
}
