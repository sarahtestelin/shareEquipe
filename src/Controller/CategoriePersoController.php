<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\CategoriePerso;
use App\Form\CategoriePersoForm;
use App\Form\ModifCategoriePersoForm;
use App\Repository\CategoriePersoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class CategoriePersoController extends AbstractController
{
    #[Route('/ajout-categorieperso', name: 'app_ajout_categorie_perso')]
    public function ajoutCategoriePerso(Request $request, EntityManagerInterface $em): Response
    {
        $categoriePerso = new CategoriePerso();
        $form = $this->createForm(CategoriePersoForm::class, $categoriePerso);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $utilisateur = $this->getUser();

            if ($utilisateur instanceof User) {
                $categoriePerso->setUtilisateur($utilisateur);
            } else {
                $this->addFlash('error', 'Vous devez être connecté pour ajouter une catégorie.');
                return $this->redirectToRoute('app_liste_categories_perso');
            }

            $categoriePerso->setDateEnvoi(new \DateTime());
            $em->persist($categoriePerso);
            $em->flush();

            $this->addFlash('success', 'Catégorie ajoutée avec succès !');
            return $this->redirectToRoute('app_ajout_categorie_perso');
        }

        return $this->render('cperso/categorie-perso.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/liste-categories-perso', name: 'app_liste_categories_perso',)]
    public function listeCategoriesPerso(
        CategoriePersoRepository $categoriePersoRepo,
    ): Response {
        $categoriePerso = $categoriePersoRepo->findAll();

        return $this->render('cperso/liste-categories-perso.html.twig', [
            'categories_perso' => $categoriePerso,
        ]);
    }


    #[Route('/profile-modifier-categorieperso/{id}', name: 'app_modifier_categorieperso')]
    public function modifierCategorie(Request $request, CategoriePerso $categorieP, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ModifCategoriePersoForm::class, $categorieP);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Catégorie perso modifiée avec succès !');
            return $this->redirectToRoute('app_liste_categories_perso');
        }

        return $this->render('categorie/modifier-categorie.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/supprimer-categorieperso/{id}', name: 'app_supprimer_categorieperso')]
    public function supprimerCategorie(CategoriePerso $categorieP, EntityManagerInterface $em): Response
    {
        if ($categorieP) {
            $em->remove($categorieP);
            $em->flush();
            $this->addFlash('success', 'Catégorie personnalisée supprimée avec succès !');
        }

        return $this->redirectToRoute('app_liste_categories_perso');
    }
}
