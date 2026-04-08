<?php
// src/Controller/BackofficeController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/backoffice', name: 'app_backoffice_')]
class BackofficeController extends AbstractController
{
    #[Route('', name: 'dashboard')]
    public function dashboard(): Response
    {
        return $this->render('backoffice/index.html.twig');
    }

    #[Route('/utilisateurs', name: 'utilisateurs')]
    public function utilisateurs(): Response
    {
        return $this->render('backoffice/utilisateurs.html.twig');
    }

    #[Route('/formations', name: 'formations')]
    public function formations(): Response
    {
        return $this->render('backoffice/formations.html.twig');
    }


    #[Route('/projets', name: 'projets')]
    public function projets(): Response
    {
        return $this->render('backoffice/projets.html.twig');
    }

    #[Route('/recrutement', name: 'recrutement')]
    public function recrutement(): Response
    {
        return $this->render('backoffice/recrutement.html.twig');
    }
}
