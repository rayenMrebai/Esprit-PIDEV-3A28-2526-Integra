<?php

namespace App\Controller;

use App\Repository\CandidatRepository;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class QrCodeController extends AbstractController
{
    #[Route('/candidat/{id}/qrcode', name: 'app_candidat_qrcode')]
    public function generateQrCode(int $id, CandidatRepository $candidatRepository): Response
    {
        $candidat = $candidatRepository->find($id);

        if (!$candidat) {
            throw $this->createNotFoundException('Candidat non trouvé');
        }

        // Construction du contenu détaillé du QR code
        $qrContent = sprintf(
            "=== CANDIDAT ===\n".
            "ID: %d\n".
            "Nom complet: %s %s\n".
            "Email: %s\n".
            "Téléphone: %s\n".
            "Niveau d'études: %s\n".
            "Compétences: %s\n".
            "Statut: %s\n".
            "Offre associée: %s",
            $candidat->getId(),
            $candidat->getFirstName(),
            $candidat->getLastName(),
            $candidat->getEmail(),
            $candidat->getPhone(),
            $candidat->getEducationLevel(),
            $candidat->getSkills(),
            $candidat->getStatus(),
            $candidat->getJobposition() ? $candidat->getJobposition()->getTitle() : 'Aucune'
        );

        $result = (new Builder(
            writer: new PngWriter(),
            data: $qrContent,
            size: 250,
            margin: 10
        ))->build();

        return new Response($result->getString(), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="candidat_'.$candidat->getId().'_qrcode.png"',
        ]);
    }

    #[Route('/candidat/{id}/qrcode-json', name: 'app_candidat_qrcode_json')]
    public function generateQrCodeJson(int $id, CandidatRepository $candidatRepository): JsonResponse
    {
        $candidat = $candidatRepository->find($id);

        if (!$candidat) {
            return $this->json(['error' => 'Candidat non trouvé'], 404);
        }

        $qrContent = sprintf(
            "=== CANDIDAT ===\n".
            "ID: %d\n".
            "Nom complet: %s %s\n".
            "Email: %s\n".
            "Téléphone: %s\n".
            "Niveau d'études: %s\n".
            "Compétences: %s\n".
            "Statut: %s\n".
            "Offre associée: %s",
            $candidat->getId(),
            $candidat->getFirstName(),
            $candidat->getLastName(),
            $candidat->getEmail(),
            $candidat->getPhone(),
            $candidat->getEducationLevel(),
            $candidat->getSkills(),
            $candidat->getStatus(),
            $candidat->getJobposition() ? $candidat->getJobposition()->getTitle() : 'Aucune'
        );

        $result = (new Builder(
            writer: new PngWriter(),
            data: $qrContent,
            size: 200,
            margin: 5
        ))->build();

        $base64 = base64_encode($result->getString());

        return $this->json([
            'qr_base64' => 'data:image/png;base64,' . $base64
        ]);
    }
}