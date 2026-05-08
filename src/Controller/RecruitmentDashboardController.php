<?php

declare(strict_types=1);

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
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class RecruitmentDashboardController extends AbstractController
{
    #[Route('/recruitment', name: 'app_recruitment_dashboard', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        JobpositionRepository $jobpositionRepository,
        CandidatRepository $candidatRepository
    ): Response {
        $modal = $request->query->get('modal');
        $id = $request->query->get('id');
        $jobFilterId = $request->query->get('job');

        $searchJob = $request->query->get('search_job');
        $searchCandidat = $request->query->get('search_candidat');
        $sortJobs = $request->query->get('sort_jobs', 'date_desc');
        $sortCandidats = $request->query->get('sort_candidats', 'name_asc');

        // === GESTION DES OFFRES ===
        $qbJobs = $jobpositionRepository->createQueryBuilder('j');
        if ($searchJob) {
            $qbJobs->andWhere('j.title LIKE :search')->setParameter('search', '%'.$searchJob.'%');
        }
        if ($sortJobs === 'date_asc') {
            $qbJobs->orderBy('j.postedAt', 'ASC');
        } else {
            $qbJobs->orderBy('j.postedAt', 'DESC');
        }
        $jobs = $qbJobs->getQuery()->getResult();

        // === GESTION DES CANDIDATS ===
        $selectedJob = null;
        if ($jobFilterId) {
            $selectedJob = $jobpositionRepository->find($jobFilterId);
        }

        $qbCandidats = $candidatRepository->createQueryBuilder('c');
        if ($selectedJob) {
            $qbCandidats->andWhere('c.jobposition = :job')->setParameter('job', $selectedJob);
        }
        if ($searchCandidat) {
            $qbCandidats->andWhere('c.firstName LIKE :search OR c.lastName LIKE :search OR c.email LIKE :search')
                ->setParameter('search', '%'.$searchCandidat.'%');
        }
        if ($sortCandidats === 'name_desc') {
            $qbCandidats->orderBy('c.lastName', 'DESC')->addOrderBy('c.firstName', 'DESC');
        } else {
            $qbCandidats->orderBy('c.lastName', 'ASC')->addOrderBy('c.firstName', 'ASC');
        }
        $candidats = $qbCandidats->getQuery()->getResult();

        // Préparer les formulaires
        $jobEntity = new Jobposition();
        $candidatEntity = new Candidat();

        // PRÉ‑REMPLISSAGE DEPUIS L'ANALYSE IA (paramètres GET)
        $prefillFirstName = $request->query->get('firstName');
        $prefillLastName  = $request->query->get('lastName');
        $prefillEmail     = $request->query->get('email');
        $prefillPhone     = $request->query->get('phone');
        $prefillEducation = $request->query->get('educationLevel');
        $prefillSkills    = $request->query->get('skills');
        $prefillStatus    = $request->query->get('status');
        $prefillJobId     = $request->query->get('job');

        if ($prefillFirstName) $candidatEntity->setFirstName((string) $prefillFirstName);
        if ($prefillLastName)  $candidatEntity->setLastName((string) $prefillLastName);
        if ($prefillEmail)     $candidatEntity->setEmail((string) $prefillEmail);
        if ($prefillPhone) {
            $phoneStr = (string) $prefillPhone;
            $phoneValue = preg_replace('/\D/', '', $phoneStr);
            $candidatEntity->setPhone($phoneValue ? (int) $phoneValue : 0);
        }
        if ($prefillEducation) $candidatEntity->setEducationLevel((string) $prefillEducation);
        if ($prefillSkills)    $candidatEntity->setSkills((string) $prefillSkills);
        if ($prefillStatus)    $candidatEntity->setStatus((string) $prefillStatus);
        if ($prefillJobId) {
            $job = $jobpositionRepository->find($prefillJobId);
            if ($job) $candidatEntity->setJobposition($job);
        }

        if ($modal === 'job_edit' && $id) {
            $jobEntity = $jobpositionRepository->find($id);
            if (!$jobEntity) throw $this->createNotFoundException('Offre non trouvée');
        }
        if ($modal === 'candidat_edit' && $id) {
            $candidatEntity = $candidatRepository->find($id);
            if (!$candidatEntity) throw $this->createNotFoundException('Candidat non trouvé');
        }

        $jobForm = $this->createForm(JobpositionType::class, $jobEntity);
        $candidatForm = $this->createForm(CandidatType::class, $candidatEntity);

        // Traitement POST
        if ($request->isMethod('POST')) {
            if ($modal === 'job_new' || $modal === 'job_edit') {
                $jobForm->handleRequest($request);
                if ($jobForm->isSubmitted() && $jobForm->isValid()) {
                    $entityManager->persist($jobEntity);
                    $entityManager->flush();
                    $this->addFlash('success', $modal === 'job_edit' ? 'Offre modifiée' : 'Offre ajoutée');
                    return $this->redirectToRoute('app_recruitment_dashboard');
                } else {
                    foreach ($jobForm->getErrors(true) as $error) {
                        // On utilise le message en castant en string via getMessage() ou __toString
                        $msg = method_exists($error, 'getMessage') ? $error->getMessage() : (string) $error;
                        $this->addFlash('error', $msg);
                    }
                }
            }
            if ($modal === 'candidat_new' || $modal === 'candidat_edit') {
                $candidatForm->handleRequest($request);
                if ($candidatForm->isSubmitted() && $candidatForm->isValid()) {
                    $entityManager->persist($candidatEntity);
                    $entityManager->flush();
                    $this->addFlash('success', $modal === 'candidat_edit' ? 'Candidat modifié' : 'Candidat ajouté');
                    return $this->redirectToRoute('app_recruitment_dashboard');
                } else {
                    foreach ($candidatForm->getErrors(true) as $error) {
                        $msg = method_exists($error, 'getMessage') ? $error->getMessage() : (string) $error;
                        $this->addFlash('error', $msg);
                    }
                }
            }
        }

        // Routes AJAX
        if ($request->isXmlHttpRequest() && $request->query->has('ajax')) {
            $type = $request->query->get('type');
            $idAjax = $request->query->get('id');
            if ($type === 'job') {
                $job = $jobpositionRepository->find($idAjax);
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
                $candidat = $candidatRepository->find($idAjax);
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
            'selectedJob' => $selectedJob,
            'searchJob' => $searchJob,
            'searchCandidat' => $searchCandidat,
            'sortJobs' => $sortJobs,
            'sortCandidats' => $sortCandidats,
            'modal' => $modal,
            'jobForm' => $jobForm->createView(),
            'candidatForm' => $candidatForm->createView(),
        ]);
    }

    #[Route('/candidat/details/{id}', name: 'app_candidat_details', methods: ['GET'])]
    public function candidatDetails(Candidat $candidat): JsonResponse
    {
        return $this->json([
            'id' => $candidat->getId(),
            'firstName' => $candidat->getFirstName(),
            'lastName' => $candidat->getLastName(),
            'email' => $candidat->getEmail(),
            'phone' => $candidat->getPhone(),
            'educationLevel' => $candidat->getEducationLevel(),
            'skills' => $candidat->getSkills(),
            'status' => $candidat->getStatus(),
            'jobTitle' => $candidat->getJobposition() ? $candidat->getJobposition()->getTitle() : 'Aucune offre',
        ]);
    }
}