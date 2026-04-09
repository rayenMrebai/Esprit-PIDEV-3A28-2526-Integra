<?php

namespace App\Controller;

use App\Service\CurrencyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class CurrencyController extends AbstractController
{
    #[Route('/api/currency-rates', name: 'api_currency_rates')]
    public function rates(CurrencyService $currencyService): JsonResponse
    {
        return $this->json($currencyService->getRates());
    }
}