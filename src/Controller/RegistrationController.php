<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\InscriptionForm;
use App\Security\AppCustomAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        Security $security,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger // Ajout du logger pour debug propre
    ): Response {
        $user = new User();
        $form = $this->createForm(InscriptionForm::class, $user);
        $form->handleRequest($request);

        // Récupération du CAPTCHA généré côté client
        $submittedCaptcha = $request->request->get('captchaValue');

        if ($form->isSubmitted()) {
            // Vérifie si le CAPTCHA est soumis
            if (empty($submittedCaptcha)) {
                $this->addFlash('error', 'Le CAPTCHA est requis.');
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                ]);
            }

            // Vérification de la validité du formulaire
            if ($form->isValid()) {
                $logger->info("Formulaire valide, enregistrement en base...");

                // Hachage du mot de passe
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );

                $user->setDateEnvoi(new \DateTime());

                try {
                    $entityManager->persist($user);
                    $entityManager->flush();
                    $logger->info("Utilisateur enregistré avec succès !");
                    $this->addFlash('success', 'Inscription réussie ! Vous êtes maintenant connecté.');

                    // Connexion automatique après inscription
                    return $security->login($user, AppCustomAuthenticator::class, 'main');
                } catch (\Exception $e) {
                    $logger->error("Erreur lors de l'enregistrement : " . $e->getMessage());
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'inscription.');
                }
            } else {
                $logger->error("Formulaire invalide : " . (string) $form->getErrors(true));
                $this->addFlash('error', 'Le formulaire contient des erreurs.');
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
