<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Entity\Contact;
use App\Entity\Fichier;
use App\Form\AjoutAmiType;
use App\Form\CategorieType;
use App\Form\ContactType;
use App\Form\FichierUserType;
use App\Repository\ContactRepository;
use App\Repository\ScategorieRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;


class BaseController extends AbstractController
{
    #[Route('/', name: 'app_accueil')]
    public function index(): Response
    {
        return $this->render('base/index.html.twig', []);
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(Request $request, EntityManagerInterface $em): Response
    {
        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $contact->setDateEnvoi(new \Datetime());
                $em->persist($contact);
                $em->flush();
                $this->addFlash('notice', 'Message envoyé');
                return $this->redirectToRoute('app_contact');
            }
        }
        return $this->render('base/contact.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/apropos', name: 'app_apropos')]
    public function apropos(): Response
    {
        return $this->render('base/apropos.html.twig', []);
    }

    #[Route('/mentionslegales', name: 'app_mentionslegales')]
    public function mentionslegales(): Response
    {
        return $this->render('base/mentionslegales.html.twig', []);
    }

    #[Route('/categorie', name: 'app_categorie')]
    public function categorie(Request $request, EntityManagerInterface $em): Response
    {
        $categorie = new Categorie();
        $form = $this->createForm(CategorieType::class, $categorie);
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $categorie->setDateEnvoi(new \Datetime());
                $em->persist($categorie);
                $em->flush();
                $this->addFlash('notice', 'Formulaire pris en compte');
                return $this->redirectToRoute('app_categorie');
            }
        }
        return $this->render('base/categorie.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/liste-contacts', name: 'app_liste_contacts')]
    public function listeContacts(ContactRepository $contactRepository): Response
    {
        $contacts = $contactRepository->findAll();
        return $this->render('contact/liste-contacts.html.twig', [
            'contacts' => $contacts,
        ]);
    }

    #[Route('/modifier-categorie/{id}', name: 'app_modifier_categorie')]
    public function modifierCategorie(Request $request, Categorie $categorie, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CategorieType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $categorie->setDateEnvoi(new \Datetime());
            $em->persist($categorie);
            $em->flush();
            $this->addFlash('notice', 'Modification prise en compte');
            return $this->redirectToRoute('app_modifier_categorie', ['id' => $categorie->getId()]);
        }

        return $this->render('categorie/modifier-categorie.html.twig', [
            'form' => $form->createView(),
        ]);
    }

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
        $form = $this->createForm(FichierUserType::class, $fichier, [
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
        $form = $this->createForm(AjoutAmiType::class);
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

    // Route pour supprimer un utilisateur
    #[Route('/admin/user/delete/{id}', name: 'user_delete')]
    public function delete(UserRepository $userRepository, $id, EntityManagerInterface $entityManager): RedirectResponse
    {
        // Récupérer l'utilisateur par son ID
        $user = $userRepository->find($id);

        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_liste_utilisateurs');
        }

        // Supprimer l'utilisateur
        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur supprimé avec succès.');

        return $this->redirectToRoute('app_liste_utilisateurs'); // Rediriger vers la liste des utilisateurs
    }
}
