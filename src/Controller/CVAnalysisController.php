<?php

namespace App\Controller;

use App\Entity\Candidat;
use App\Repository\JobpositionRepository;
use App\Service\HuggingFaceAnalyzer;
use App\Service\PdfTextExtractor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;

class CVAnalysisController extends AbstractController
{
    public function __construct(
        private MailerInterface $mailer,  // = mailer.recrutement (Gmail)
    ) {}

    #[Route('/recruitment/cv-analyze', name: 'app_cv_analyze')]
    public function index(JobpositionRepository $jobRepo): Response
    {
        return $this->render('recruitment/upload_cv.html.twig', [
            'jobs' => $jobRepo->findAll(),
        ]);
    }

    #[Route('/recruitment/cv-upload', name: 'app_cv_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('cv_file');

        if (!$file || $file->getClientOriginalExtension() !== 'pdf') {
            return $this->json(['error' => 'Fichier PDF requis'], 400);
        }

        $dir = $this->getParameter('kernel.project_dir') . '/var/tmp/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $filename = uniqid() . '.pdf';
        $file->move($dir, $filename);

        return $this->json(['filePath' => $dir . $filename]);
    }

    #[Route('/recruitment/cv-extract', name: 'app_cv_extract', methods: ['POST'])]
    public function extract(Request $request, PdfTextExtractor $extractor): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $filePath = $data['filePath'] ?? null;

        if (!$filePath || !file_exists($filePath)) {
            return $this->json(['error' => 'Fichier introuvable'], 400);
        }

        return $this->json(['text' => $extractor->extract($filePath)]);
    }

    #[Route('/recruitment/cv-analyze-ai', name: 'app_cv_analyze_ai', methods: ['POST'])]
    public function analyze(Request $request, HuggingFaceAnalyzer $analyzer, JobpositionRepository $jobRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $cvText = $data['text'] ?? '';
        $jobId = $data['jobId'] ?? null;

        if (!$cvText || !$jobId) {
            return $this->json(['error' => 'Données manquantes'], 400);
        }

        $job = $jobRepo->find($jobId);
        if (!$job) {
            return $this->json(['error' => 'Offre non trouvée'], 404);
        }

        $result = $analyzer->analyzeCV($cvText, $job->getTitle() . ' - ' . $job->getDescription());

        if (empty($result['email'])) {
            preg_match('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $cvText, $matches);
            $result['email'] = $matches[0] ?? '';
        }

        return $this->json($result);
    }

    #[Route('/recruitment/cv-add-rejected-candidate', name: 'app_cv_add_rejected_candidate', methods: ['POST'])]
    public function addRejectedCandidate(Request $request, EntityManagerInterface $em, JobpositionRepository $jobRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $candidat = new Candidat();
        $candidat->setFirstName($data['firstName'] ?? '');
        $candidat->setLastName($data['lastName'] ?? '');
        $candidat->setEmail($data['email'] ?? '');
        $candidat->setPhone((int) preg_replace('/\D/', '', $data['phone'] ?? ''));
        $candidat->setEducationLevel($data['educationLevel'] ?? '');
        $candidat->setSkills($data['skills'] ?? '');
        $candidat->setStatus('Rejeté');

        if ($job = $jobRepo->find($data['jobId'] ?? null)) {
            $candidat->setJobposition($job);
        }

        $em->persist($candidat);
        $em->flush();

        return $this->json(['candidatId' => $candidat->getId()]);
    }

    #[Route('/recruitment/generate-rejection-letter', name: 'app_generate_rejection_letter', methods: ['POST'])]
    public function generateRejectionLetter(Request $request, HuggingFaceAnalyzer $analyzer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $letter = $analyzer->generateRejectionLetter(
            $data['firstName'] ?? '',
            $data['lastName'] ?? '',
            $data['jobTitle'] ?? '',
            $data['reason'] ?? ''
        );

        return $this->json(['letter' => $letter]);
    }

    #[Route('/recruitment/send-rejection-email', name: 'app_send_rejection_email', methods: ['POST'])]
    public function sendRejectionEmail(Request $request, JobpositionRepository $jobRepo): JsonResponse
    {
        $job = $jobRepo->find(json_decode($request->getContent(), true)['jobId'] ?? null);

        if (!$job) {
            return $this->json(['error' => 'Offre introuvable'], 404);
        }

        try {
            $email = (new Email())
                ->from(new Address('walabentahar0@gmail.com', 'INTEGRA Recruitment'))
                ->to('walabentahar0@gmail.com')
                ->subject('TEST INTEGRA FINAL')
                ->html('<p>Test final Symfony OK</p>')
                ->text('Test final Symfony OK');

            $this->mailer->send($email);

            return $this->json([
                'success' => true,
                'sent_to' => 'walabentahar0@gmail.com'
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}