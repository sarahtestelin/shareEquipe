<?php

namespace App\Controller;

use App\Entity\Scategorie;
use App\Form\ModifScategorieForm;
use App\Form\ScategorieForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ScategorieController extends AbstractController
{
    #[Route('/ajout-scategorie', name: 'app_ajout_scategorie')]
    public function ajoutScategorie(Request $request, EntityManagerInterface $em): Response
    {
        $scategorie = new Scategorie();
        $form = $this->createForm(ScategorieForm::class, $scategorie);
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $em->persist($scategorie);
                    $em->flush();
                } catch (\RuntimeException $e) {
                    $this->addFlash('notice', $e->getMessage());
                    return $this->redirectToRoute('app_ajout_scategorie');
                }
                $this->addFlash('notice', 'Sous catégorie insérée');
                return $this->redirectToRoute('app_ajout_scategorie');
            }
        }
        return $this->render('scategorie/ajout-scategorie.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/modifier-scategorie/{id}', name: 'app_modifier_scategorie')]
    public function modifierScategorie(Request $request, SCategorie $scategorie, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ModifScategorieForm::class, $scategorie);
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $em->persist($scategorie);
                $em->flush();
                $this->addFlash('notice', 'Sous-catégorie modifiée');
                return $this->redirectToRoute('app_liste_categories');
            }
        }
        return $this->render('scategorie/modifier-scategorie.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/private-supprimer-scategorie/{id}', name: 'app_supprimer_scategorie')]
    public function supprimerCategorie(Scategorie $scategorie, EntityManagerInterface $em): Response
    {
        if ($scategorie != null) {
            $em->remove($scategorie);
            $em->flush();
            $this->addFlash('notice', 'Sous-catégorie supprimée');
        }
        return $this->redirectToRoute('app_liste_categories');
    }
}
