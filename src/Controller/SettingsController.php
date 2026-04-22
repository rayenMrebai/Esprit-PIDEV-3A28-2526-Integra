<?php

namespace App\Controller;

use App\Entity\UserSetting;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SettingsController extends AbstractController
{
    #[Route('/save-language', name: 'app_save_language', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function saveLanguage(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $language = $data['language'] ?? null;
        if (!$language) {
            return $this->json(['success' => false], 400);
        }

        $user = $this->getUser();
        $setting = $user->getUserSetting();
        if (!$setting) {
            $setting = new UserSetting();
            $setting->setUserAccount($user);
            $em->persist($setting);
        }
        $setting->setLanguage($language);
        $em->flush();

        return $this->json(['success' => true]);
    }
}