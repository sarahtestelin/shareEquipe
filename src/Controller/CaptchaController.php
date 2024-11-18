<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class CaptchaController extends AbstractController
{
    #[Route('/captcha', name: 'captcha_generate', methods: ['GET'])]
    public function generate(SessionInterface $session): JsonResponse
    {
        $captchaCode = random_int(1000, 9999);
        $session->set('captcha_code', $captchaCode);

        return $this->json(['captcha' => $captchaCode]);
    }
}
