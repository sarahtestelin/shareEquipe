<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\AppCustomAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        Security $security,
        EntityManagerInterface $entityManager
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        // Récupération des valeurs CAPTCHA
        $submittedCaptcha = $request->request->get('captchaValue'); // Généré côté client
        $userCaptchaInput = $request->request->get('captchaInput'); // Saisi par l'utilisateur

        if ($form->isSubmitted()) {
            // Validation du CAPTCHA
            if ($submittedCaptcha !== $userCaptchaInput) {
                $this->addFlash('error', 'Le CAPTCHA est incorrect. Veuillez réessayer.');
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                ]);
            }

            // Vérifie si le formulaire est valide
            if ($form->isValid()) {
                // Encode le mot de passe
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );

                $user->setDateEnvoi(new \DateTime());
                $entityManager->persist($user);
                $entityManager->flush();

                // Connecte l'utilisateur après l'inscription
                return $security->login($user, AppCustomAuthenticator::class, 'main');
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
