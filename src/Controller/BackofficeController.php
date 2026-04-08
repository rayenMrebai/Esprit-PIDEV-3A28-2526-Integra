<?php

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
}