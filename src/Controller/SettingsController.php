<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\UserAccount;
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
        // Décodage sécurisé
        $rawData = json_decode((string) $request->getContent(), true);
        $data = is_array($rawData) ? $rawData : [];
        $language = $data['language'] ?? null;

        // Validation
        if (!is_string($language) || $language === '') {
            return $this->json(['success' => false, 'error' => 'Invalid language'], 400);
        }

        // Récupération de l'utilisateur connecté
        /** @var UserAccount|null $user */
        $user = $this->getUser();
        if (!$user instanceof UserAccount) {
            return $this->json(['success' => false, 'error' => 'User not authenticated'], 401);
        }

        // Récupération ou création de l'UserSetting
        $setting = $user->getUserSetting();
        if (!$setting instanceof UserSetting) {
            $setting = new UserSetting();
            $setting->setUserAccount($user);
            $em->persist($setting);
        }

        $setting->setLanguage($language);
        $em->flush();

        return $this->json(['success' => true]);
    }
}