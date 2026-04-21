<?php

namespace App\Controller;

use App\Repository\JobpositionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FrontController extends AbstractController
{
    #[Route('/', name: 'front_home')]
    public function home(JobpositionRepository $jobpositionRepository): Response
    {
        // Récupère toutes les offres, triées par date de publication (récentes d'abord)
        $jobs = $jobpositionRepository->findBy([], ['postedAt' => 'DESC']);

        return $this->render('front/home.html.twig', [
            'jobs' => $jobs,
        ]);
    }

    #[Route('/offre/{id}', name: 'front_job_show')]
    public function show(JobpositionRepository $jobpositionRepository, int $id): Response
    {
        $job = $jobpositionRepository->find($id);
        if (!$job) {
            throw $this->createNotFoundException('Offre non trouvée');
        }
        return $this->render('front/job_show.html.twig', [
            'job' => $job,
        ]);
    }
}