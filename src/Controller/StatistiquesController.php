<?php
// src/Controller/StatistiquesController.php

namespace App\Controller;

use App\Repository\CandidatRepository;
use App\Repository\JobpositionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StatistiquesController extends AbstractController
{
    #[Route('/recruitment/statistiques', name: 'app_recruitment_statistiques')]
    public function index(
        JobpositionRepository $jobRepo,
        CandidatRepository $candidatRepo
    ): Response {
        // === STATS OFFRES ===
        $totalJobs = $jobRepo->count([]);
        $jobsOpen = $jobRepo->count(['status' => 'Open']);
        $jobsClosed = $jobRepo->count(['status' => 'Closed']);

        // === STATS CANDIDATS ===
        $totalCandidats = $candidatRepo->count([]);

        // Récupération dynamique des statuts et de leurs effectifs
        $qb = $candidatRepo->createQueryBuilder('c');
        $qb->select('c.status as statut, COUNT(c.id) as nb')
            ->groupBy('c.status')
            ->orderBy('nb', 'DESC');
        $statusData = $qb->getQuery()->getResult();

        $statusLabels = array_column($statusData, 'statut');
        $statusCounts = array_column($statusData, 'nb');

        // Répartition des offres par statut
        $jobStatusLabels = ['Open', 'Closed'];
        $jobStatusCounts = [$jobsOpen, $jobsClosed];

        // Nombre de candidats par offre (pour le bar chart)
        $jobs = $jobRepo->findAll();
        $jobTitles = [];
        $candidatsPerJob = [];
        foreach ($jobs as $job) {
            $jobTitles[] = $job->getTitle();
            $candidatsPerJob[] = $job->getCandidats()->count();
        }

        return $this->render('statistiques/index.html.twig', [
            'totalJobs' => $totalJobs,
            'jobsOpen' => $jobsOpen,
            'jobsClosed' => $jobsClosed,
            'totalCandidats' => $totalCandidats,
            'statusLabels' => $statusLabels,
            'statusCounts' => $statusCounts,
            'jobStatusLabels' => $jobStatusLabels,
            'jobStatusCounts' => $jobStatusCounts,
            'jobTitles' => $jobTitles,
            'candidatsPerJob' => $candidatsPerJob,
        ]);
    }
}