<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Entity\Scategorie;
use App\Form\CategorieForm;
use App\Form\ModifCategoriesForm;
use App\Form\GestionCategoriesForm;
use App\Repository\CategorieRepository;
use App\Repository\ScategorieRepository;
use Doctrine\Common\Collections\ArrayCollection;
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

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $categorie->setDateEnvoi(new \DateTime());
            $em->persist($categorie);
            $em->flush();

            $this->addFlash('success', 'Catégorie ajoutée avec succès !');
            return $this->redirectToRoute('app_liste_categories');
        }

        return $this->render('base/categorie.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/modifier-categorie/{id}', name: 'app_modifier_categorie')]
    public function modifierCategorie(Request $request, Categorie $categorie, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CategorieForm::class, $categorie);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Catégorie modifiée avec succès !');
            return $this->redirectToRoute('app_liste_categories');
        }

        return $this->render('categorie/modifier-categorie.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/modifier-categories/{type}/{ids}', name: 'app_modifier_categories')]
    public function modifierCategories(Request $request, EntityManagerInterface $em, string $type, string $ids): Response
    {
        $idsArray = explode(',', $ids);

        if ($type === 'categories') {
            $items = $em->getRepository(Categorie::class)->findBy(['id' => $idsArray]);
        } else {
            $items = $em->getRepository(Scategorie::class)->findBy(['id' => $idsArray]);
        }

        $formData = ['items' => array_map(fn ($item) => $type === 'categories' ? $item->getNom() : $item->getLibelle(), $items)];

        $form = $this->createForm(ModifCategoriesForm::class, $formData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newNames = $form->get('items')->getData();

            foreach ($items as $index => $item) {
                if ($type === 'categories') {
                    $item->setNom($newNames[$index]);
                } else {
                    $item->setLibelle($newNames[$index]);
                }
            }

            $em->flush();
            $this->addFlash('success', 'Modifications enregistrées.');

            return $this->redirectToRoute('app_liste_categories');
        }

        return $this->render('categorie/modifier-categories.html.twig', [
            'form' => $form->createView(),
            'items' => $items,
            'type' => $type,
        ]);
    }

    #[Route('/private-liste-categories', name: 'app_liste_categories', methods: ['GET', 'POST'])]
    public function listeCategories(
        Request $request,
        CategorieRepository $categorieRepository,
        ScategorieRepository $sCategorieRepository,
        EntityManagerInterface $em
    ): Response {
        $categories = $categorieRepository->findAll();
        $sCategories = $sCategorieRepository->findAll();

        $form = $this->createForm(GestionCategoriesForm::class, null, [
            'categories' => $categories,
            'scategories' => $sCategories,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedCategories = $form->get('categories')->getData()->toArray();
            $selectedSCategories = $form->get('scategories')->getData()->toArray();

            if ($form->get('modifier_selection')->isClicked()) {
                if (!empty($selectedCategories)) {
                    $ids = array_map(fn ($c) => $c->getId(), $selectedCategories);
                    return $this->redirectToRoute('app_modifier_categories', [
                        'type' => 'categories',
                        'ids' => implode(',', $ids),
                    ]);
                }

                if (!empty($selectedSCategories)) {
                    $ids = array_map(fn ($sc) => $sc->getId(), $selectedSCategories);
                    return $this->redirectToRoute('app_modifier_categories', [
                        'type' => 'scategories',
                        'ids' => implode(',', $ids),
                    ]);
                }
            }

            $em->flush();
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
        if ($categorie) {
            $em->remove($categorie);
            $em->flush();
            $this->addFlash('success', 'Catégorie supprimée avec succès !');
        }

        return $this->redirectToRoute('app_liste_categories');
    }
}
