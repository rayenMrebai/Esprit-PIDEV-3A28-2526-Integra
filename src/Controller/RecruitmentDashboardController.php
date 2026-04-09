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
        $jobFilterId = $request->query->get('job');

        // Paramètres de recherche
        $searchJob = $request->query->get('search_job');
        $searchCandidat = $request->query->get('search_candidat');

        // Paramètres de tri
        $sortJobs = $request->query->get('sort_jobs', 'date_desc'); // date_desc, date_asc
        $sortCandidats = $request->query->get('sort_candidats', 'name_asc'); // name_asc, name_desc

        // === GESTION DES OFFRES ===
        $qbJobs = $jobpositionRepository->createQueryBuilder('j');
        if ($searchJob) {
            $qbJobs->andWhere('j.title LIKE :search')->setParameter('search', '%'.$searchJob.'%');
        }
        // Application du tri
        if ($sortJobs === 'date_asc') {
            $qbJobs->orderBy('j.postedAt', 'ASC');
        } else { // date_desc par défaut
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
        // Application du tri
        if ($sortCandidats === 'name_desc') {
            $qbCandidats->orderBy('c.lastName', 'DESC')->addOrderBy('c.firstName', 'DESC');
        } else { // name_asc par défaut
            $qbCandidats->orderBy('c.lastName', 'ASC')->addOrderBy('c.firstName', 'ASC');
        }
        $candidats = $qbCandidats->getQuery()->getResult();

        // Préparer les formulaires (ajout/édition)
        $jobEntity = new Jobposition();
        $candidatEntity = new Candidat();

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

        // Traitement POST (ajout/modification)
        if ($request->isMethod('POST')) {
            // ... (identique à avant) ...
        }

        // Routes AJAX (si utilisées)
        if ($request->isXmlHttpRequest() && $request->query->has('ajax')) {
            // ... (identique) ...
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
}