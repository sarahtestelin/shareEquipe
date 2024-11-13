<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Entity\Contact;
use App\Entity\Fichier;
use App\Form\CategorieType;
use App\Form\ContactType;
use App\Form\FichierUserType;
use App\Repository\ContactRepository;
use App\Repository\UserRepository;
use App\Repository\ScategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

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

            // Ajoutez ce code ici pour déplacer le fichier et enregistrer les informations
            if ($uploadedFile && $uploadedFile->isValid()) {
                $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

                try {
                    

                    // Enregistrement des informations du fichier dans l'entité après le déplacement
                    $fichier->setNomOriginal($originalFilename);
                    $fichier->setNomServeur($newFilename);
                    $fichier->setExtension($uploadedFile->guessExtension());
                    $fichier->setTaille($uploadedFile->getSize());
                    $fichier->setUser($this->getUser());
                    $fichier->setDateEnvoi(new \DateTime());

                    // Associer les sous-catégories sélectionnées
                    $selectedScategories = $form->get('scategories')->getData();
                    foreach ($selectedScategories as $scategorie) {
                        $fichier->addScategory($scategorie);
                    }

                    // Enregistrement dans la base de données
                    $em->persist($fichier);
                    $em->flush();
                    // Déplacer le fichier immédiatement vers le répertoire défini
                    $uploadedFile->move(
                        $this->getParameter('file_directory'), // Assurez-vous que 'file_directory' est correctement configuré
                        $newFilename
                    );

                    // Message de succès
                    $this->addFlash('success', 'Fichier ajouté avec succès.');
                    return $this->redirectToRoute('app_profil');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du fichier : ' . $e->getMessage());
                }
            } else {
                $this->addFlash('error', 'Le fichier uploadé n\'est pas valide ou lisible. Veuillez réessayer.');
            }
        }

        // Récupérer les fichiers de l'utilisateur connecté
        $userFiles = $em->getRepository(Fichier::class)->findBy(['user' => $this->getUser()]);

        return $this->render('base/profil.html.twig', [
            'form' => $form->createView(),
            'myuser' => $this->getUser(),
            'userFiles' => $userFiles, // Passer les fichiers à la vue
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

}