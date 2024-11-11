<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Entity\Contact;
use App\Form\CategorieType;
use App\Form\ContactType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UserRepository;

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
    public function modifierCategorie(Categorie $categorie): Response
    {
        $form = $this->createForm(ModifierCategorieType::class, $categorie);
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $categorie->setDateEnvoi(new \Datetime());
                $em->persist($categorie);
                $em->flush();
                $this->addFlash('notice', 'Modification pris en compte');
                return $this->redirectToRoute('app_modifier_categorie');
            }
        }
        return $this->render('categorie/modifier-categorie.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/mod-liste-utilisateurs', name: 'app_liste_utilisateurs')]
    public function listeUtilisateurs(UserRepository $userRepository): Response
    {
        $user = $userRepository->findAll();
        return $this->render('base/listeutilisateurs.html.twig', [
            'user' => $user,
        ]);
    }
    #[Route('/private-profil', name: 'app_profil')]
    public function profil(UserRepository $userRepository): Response
    {
        $myuser = $userRepository->findAll();
        return $this->render('base/profil.html.twig', ['myuser' => $myuser,
    ]);
    }
}
