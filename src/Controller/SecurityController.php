<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request, SessionInterface $session): Response
    {
        // Si l'utilisateur est déjà connecté, redirigez-le vers une autre page
        if ($this->getUser()) {
            return $this->redirectToRoute('app_accueil');
        }

        // Générer un CAPTCHA simple si non encore présent
        if (!$session->has('captcha_code')) {
            $captchaCode = random_int(1000, 9999); // Génère un code à 4 chiffres
            $session->set('captcha_code', $captchaCode);
        } else {
            $captchaCode = $session->get('captcha_code');
        }

        // Si le formulaire a été soumis
        if ($request->isMethod('POST')) {
            // Récupérer le CAPTCHA saisi par l'utilisateur
            $userCaptcha = $request->request->get('captcha');

            // Vérifiez le CAPTCHA
            if ($userCaptcha !== (string) $captchaCode) {
                // Ajouter un message d'erreur si le CAPTCHA est incorrect
                $this->addFlash('error', 'Le CAPTCHA n\'est pas correct.');
                return $this->redirectToRoute('app_login');
            }

            // Supprimez le CAPTCHA après validation réussie
            $session->remove('captcha_code');
        }

        // Récupérer l'erreur d'authentification (si elle existe)
        $error = $authenticationUtils->getLastAuthenticationError();
        // Dernier nom d'utilisateur saisi par l'utilisateur
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'captcha_code' => $captchaCode, // Transmettre le CAPTCHA au template
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
