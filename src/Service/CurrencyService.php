<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class CurrencyService
{
    private const API_PRIMARY = 'https://open.er-api.com/v6/latest/TND';
    private const API_FALLBACK = 'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/tnd.min.json';
    private const CACHE_TTL = 1800; // 30 minutes

    private HttpClientInterface $httpClient;
    private FilesystemAdapter $cache;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->cache = new FilesystemAdapter();
    }

    /**
     * Retourne les taux USD et EUR (par rapport au TND).
     * Utilise un cache de 30 minutes.
     */
    public function getRates(): array
    {
        $cacheItem = $this->cache->getItem('currency_rates_usd_eur');
        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        try {
            $rates = $this->fetchFromPrimary();
        } catch (\Exception $e) {
            try {
                $rates = $this->fetchFromFallback();
            } catch (\Exception $e) {
                return ['USD' => null, 'EUR' => null, 'error' => 'Indisponible'];
            }
        }

        $cacheItem->set($rates);
        $cacheItem->expiresAfter(self::CACHE_TTL);
        $this->cache->save($cacheItem);

        return $rates;
    }

    private function fetchFromPrimary(): array
    {
        $response = $this->httpClient->request('GET', self::API_PRIMARY);
        $data = $response->toArray();

        if (($data['result'] ?? '') !== 'success' || !isset($data['rates'])) {
            throw new \Exception('API primary failed');
        }

        return [
            'USD' => $data['rates']['USD'] ?? null,
            'EUR' => $data['rates']['EUR'] ?? null,
        ];
    }

    private function fetchFromFallback(): array
    {
        $response = $this->httpClient->request('GET', self::API_FALLBACK);
        $data = $response->toArray();

        if (!isset($data['tnd'])) {
            throw new \Exception('Fallback API failed');
        }

        return [
            'USD' => $data['tnd']['usd'] ?? null,
            'EUR' => $data['tnd']['eur'] ?? null,
        ];
    }

    /**
     * Formatte les taux pour un affichage direct.
     */
    public function getDisplayRates(): string
    {
        $rates = $this->getRates();
        if (isset($rates['error']) || $rates['USD'] === null || $rates['EUR'] === null) {
            return '⚠️ Taux indisponible';
        }
        return sprintf('%.4f USD  •  %.4f EUR', $rates['USD'], $rates['EUR']);
    }
}