<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class CurrencyService
{
    private const API_PRIMARY = 'https://open.er-api.com/v6/latest/TND';
    private const API_FALLBACK = 'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/tnd.min.json';
    private const CACHE_TTL = 1800;

    private HttpClientInterface $httpClient;
    private FilesystemAdapter $cache;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->cache = new FilesystemAdapter();
    }

    /**
     * @return array{USD: string, EUR: string}|array{USD: null, EUR: null, error: string}
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
                $rates = ['USD' => null, 'EUR' => null, 'error' => 'Indisponible'];
            }
        }

        $displayRates = [
            'USD' => $rates['USD'] ?? '--',
            'EUR' => $rates['EUR'] ?? '--',
        ];

        $cacheItem->set($displayRates);
        $cacheItem->expiresAfter(self::CACHE_TTL);
        $this->cache->save($cacheItem);

        return $displayRates;
    }

    /**
     * @return array{USD: float|null, EUR: float|null}
     */
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

    /**
     * @return array{USD: float|null, EUR: float|null}
     */
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

    public function getDisplayRates(): string
    {
        $rates = $this->getRates();
        if (isset($rates['error']) || $rates['USD'] === '--' || $rates['EUR'] === '--') {
            return '⚠️ Taux indisponible';
        }
        return sprintf('%.4f USD  •  %.4f EUR', $rates['USD'], $rates['EUR']);
    }
}