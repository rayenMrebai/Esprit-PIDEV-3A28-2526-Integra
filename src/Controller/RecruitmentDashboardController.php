<?php

namespace App\Controller;

use App\Entity\Candidat;
use App\Entity\Jobposition;
use App\Form\CandidatType;
use App\Form\JobpositionType;
use App\Repository\CandidatRepository;
use App\Repository\JobpositionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RecruitmentDashboardController extends AbstractController
{
    #[Route('/recruitment', name: 'app_recruitment_dashboard', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        JobpositionRepository $jobpositionRepository,
        CandidatRepository $candidatRepository
    ): Response {
        $modal = $request->query->get('modal');
        $id = $request->query->get('id');

        // Récupération des données pour l'affichage
        $jobs = $jobpositionRepository->findAll();
        $candidats = $candidatRepository->findAll();

        // Préparer les formulaires (vides par défaut)
        $jobEntity = new Jobposition();
        $candidatEntity = new Candidat();

        // Si on est en mode édition, on charge l'entité correspondante
        if ($modal === 'job_edit' && $id) {
            $jobEntity = $jobpositionRepository->find($id);
            if (!$jobEntity) {
                throw $this->createNotFoundException('Offre non trouvée');
            }
        }

        if ($modal === 'candidat_edit' && $id) {
            $candidatEntity = $candidatRepository->find($id);
            if (!$candidatEntity) {
                throw $this->createNotFoundException('Candidat non trouvé');
            }
        }

        $jobForm = $this->createForm(JobpositionType::class, $jobEntity);
        $candidatForm = $this->createForm(CandidatType::class, $candidatEntity);

        // Traitement des soumissions POST (ajout ou modification)
        if ($request->isMethod('POST')) {
            if ($modal === 'job_new' || $modal === 'job_edit') {
                $jobForm->handleRequest($request);
                if ($jobForm->isSubmitted() && $jobForm->isValid()) {
                    $entityManager->persist($jobEntity);
                    $entityManager->flush();
                    $this->addFlash('success', $modal === 'job_edit' ? 'Offre modifiée' : 'Offre ajoutée');
                    return $this->redirectToRoute('app_recruitment_dashboard');
                }
            }

            if ($modal === 'candidat_new' || $modal === 'candidat_edit') {
                $candidatForm->handleRequest($request);
                if ($candidatForm->isSubmitted() && $candidatForm->isValid()) {
                    $entityManager->persist($candidatEntity);
                    $entityManager->flush();
                    $this->addFlash('success', $modal === 'candidat_edit' ? 'Candidat modifié' : 'Candidat ajouté');
                    return $this->redirectToRoute('app_recruitment_dashboard');
                }
            }
        }

        // Routes AJAX pour récupérer les données d'une entité (utilisées par le JS)
        if ($request->isXmlHttpRequest() && $request->query->has('ajax')) {
            $type = $request->query->get('type');
            $id = $request->query->get('id');
            if ($type === 'job') {
                $job = $jobpositionRepository->find($id);
                if (!$job) return new JsonResponse(['error' => 'Not found'], 404);
                return $this->json([
                    'idJob' => $job->getIdJob(),
                    'title' => $job->getTitle(),
                    'departement' => $job->getDepartement(),
                    'employeeType' => $job->getEmployeeType(),
                    'description' => $job->getDescription(),
                    'status' => $job->getStatus(),
                    'postedAt' => $job->getPostedAt() ? $job->getPostedAt()->format('Y-m-d') : null,
                ]);
            }
            if ($type === 'candidat') {
                $candidat = $candidatRepository->find($id);
                if (!$candidat) return new JsonResponse(['error' => 'Not found'], 404);
                return $this->json([
                    'id' => $candidat->getId(),
                    'firstName' => $candidat->getFirstName(),
                    'lastName' => $candidat->getLastName(),
                    'email' => $candidat->getEmail(),
                    'phone' => $candidat->getPhone(),
                    'educationLevel' => $candidat->getEducationLevel(),
                    'skills' => $candidat->getSkills(),
                    'status' => $candidat->getStatus(),
                    'jobposition' => $candidat->getJobposition() ? $candidat->getJobposition()->getIdJob() : null,
                ]);
            }
        }

        return $this->render('recruitment/dashboard.html.twig', [
            'jobs' => $jobs,
            'candidats' => $candidats,
            'modal' => $modal,
            'jobForm' => $jobForm->createView(),
            'candidatForm' => $candidatForm->createView(),
        ]);
    }
}