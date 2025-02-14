<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Form\CategorieForm;
use App\Form\ModifCategorieForm;
use App\Form\SupprCategorieForm;
use App\Form\SupprScategorieForm;
use App\Repository\CategorieRepository;
use App\Repository\ScategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class CategorieController extends AbstractController
{
    #[Route('/ajout-categorie', name: 'app_ajout_categorie')]
    public function ajoutCategorie(Request $request, EntityManagerInterface $em): Response
    {
        $categorie = new Categorie();
        $form = $this->createForm(CategorieForm::class, $categorie);
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $categorie->setDateEnvoi(new \Datetime());
                $em->persist($categorie);
                $em->flush();
                $this->addFlash('notice', 'Formulaire pris en compte');
                return $this->redirectToRoute('app_liste_categories');
            }
        }
        return $this->render('base/categorie.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/modifier-categorie/{id}', name: 'app_modifier_categorie')]
    public function modifierCategorie(Request $request, Categorie $categorie, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ModifCategorieForm::class, $categorie);
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $em->persist($categorie);
                $em->flush();
                $this->addFlash('notice', 'Catégorie modifiée');
                return $this->redirectToRoute('app_liste_categories');
            }
        }
        return $this->render('categorie/modifier-categorie.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/private-liste-categories', name: 'app_liste_categories', methods: ['GET', 'POST'])]
    public function listeCategories(
        Request $request,
        CategorieRepository $categorieRepository,
        SCategorieRepository $sCategorieRepository,
        EntityManagerInterface $emi
    ): Response {
        $categories = $categorieRepository->findAll();
        $sCategories = $sCategorieRepository->findAll();

        $form = $this->createForm(SupprCategorieForm::class, null, [
            'categories' => $categories,
            'scategories' => $sCategories,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('supprimer_c')->isClicked()) {
                $selectedCategories = $form->get('categories')->getData();
                foreach ($selectedCategories as $categorie) {
                    $emi->remove($categorie);
                }
                $this->addFlash('notice', 'Catégories supprimées avec succès');
            }
        
            if ($form->get('supprimer_sc')->isClicked()) {
                $selectedSCategories = $form->get('scategories')->getData();
                foreach ($selectedSCategories as $sCategorie) {
                    $emi->remove($sCategorie);
                }
                $this->addFlash('notice', 'Sous-catégories supprimées avec succès');
            }
        
            $emi->flush();
            return $this->redirectToRoute('app_liste_categories');
        }

        return $this->render('categorie/liste-categories.html.twig', [
            'categories' => $categories,
            'scategories' => $sCategories,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/private-supprimer-categorie/{id}', name: 'app_supprimer_categorie')]
    public function supprimerCategorie(Categorie $categorie, EntityManagerInterface $em): Response
    {
        if ($categorie != null) {
            $em->remove($categorie);
            $em->flush();
            $this->addFlash('notice', 'Catégorie supprimée');
        }
        return $this->redirectToRoute('app_liste_categories');
    }
}
