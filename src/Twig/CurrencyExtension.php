<?php

namespace App\Twig;

use App\Service\CurrencyService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CurrencyExtension extends AbstractExtension
{
    private CurrencyService $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('currency_rates', [$this, 'getRates']),
            new TwigFunction('currency_display', [$this, 'getDisplayRates']),
        ];
    }

    public function getRates(): array
    {
        return $this->currencyService->getRates();
    }

    public function getDisplayRates(): string
    {
        return $this->currencyService->getDisplayRates();
    }
}