<?php

namespace App\Service;

use App\Repository\SalaireRepository;

class SalaireStatisticsService
{
    public function __construct(
        private SalaireRepository $salaireRepo
    ) {}

    public function getStatistics(): array
    {
        $salaires = $this->salaireRepo->findAll();

        if (empty($salaires)) {
            return $this->emptyStats();
        }

        $totalCount  = count($salaires);
        $amounts     = array_map(fn($s) => $s->getTotalAmount(), $salaires);
        $bonuses     = array_map(fn($s) => $s->getBonusAmount(), $salaires);
        $paidCount   = count(array_filter($salaires, fn($s) => $s->getStatus() === 'PAYÉ'));
        $enCoursCount = count(array_filter($salaires, fn($s) => $s->getStatus() === 'EN_COURS'));
        $creeCount   = count(array_filter($salaires, fn($s) => $s->getStatus() === 'CREÉ'));

        return [
            'totalCount'      => $totalCount,
            'averageSalary'   => array_sum($amounts) / $totalCount,
            'totalAmount'     => array_sum($amounts),
            'maxSalary'       => max($amounts),
            'minSalary'       => min($amounts),
            'totalBonus'      => array_sum($bonuses),
            'averageBonus'    => array_sum($bonuses) / $totalCount,
            'paidCount'       => $paidCount,
            'enCoursCount'    => $enCoursCount,
            'creeCount'       => $creeCount,
            'paidPercentage'  => ($paidCount / $totalCount) * 100,
            // Données pour graphique barres par mois
            'monthlyData'     => $this->getMonthlyData($salaires),
            // Données pour prédiction linéaire
            'prediction'      => $this->getLinearPrediction($salaires),
            // Top 5 employés par salaire
            'topEmployees'    => $this->getTopEmployees($salaires),
        ];
    }

    // ─────────────────────────────────────────────
    // Moyenne des salaires par mois (pour graphique)
    // ─────────────────────────────────────────────
    private function getMonthlyData(array $salaires): array
    {
        $grouped = [];

        foreach ($salaires as $s) {
            $date = $s->getDatePaiement();
            if (!$date) continue;
            $key = $date->format('Y-m'); // ex: "2025-03"
            $grouped[$key][] = $s->getTotalAmount();
        }

        ksort($grouped); // trier par date

        $labels = [];
        $data   = [];

        foreach ($grouped as $yearMonth => $vals) {
            // Formater le label : "2025-03" → "Mar 2025"
            $dt = \DateTime::createFromFormat('Y-m', $yearMonth);
            $labels[] = $dt->format('M Y');
            $data[]   = round(array_sum($vals) / count($vals), 2);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    // ─────────────────────────────────────────────
    // Régression linéaire — prédiction 3 mois
    // Équivalent exact du PredictionService Java
    // ─────────────────────────────────────────────
    private function getLinearPrediction(array $salaires): array
    {
        $monthly = $this->getMonthlyData($salaires);

        if (count($monthly['data']) < 3) {
            return [
                'predictions' => [],
                'r2'          => 0,
                'message'     => 'Minimum 3 mois de données requis',
            ];
        }

        $n    = count($monthly['data']);
        $xs   = range(0, $n - 1);
        $ys   = $monthly['data'];

        // Calcul régression linéaire y = ax + b
        [$a, $b] = $this->linearRegression($xs, $ys);

        // R² (coefficient de détermination)
        $r2 = $this->calculateR2($xs, $ys, $a, $b);

        // Prédire les 3 prochains mois
        $predictions = [];
        $lastDate    = \DateTime::createFromFormat(
            'M Y',
            end($monthly['labels']),
            new \DateTimeZone('UTC')
        );

        for ($i = 1; $i <= 3; $i++) {
            $futureDate  = clone $lastDate;
            $futureDate->modify("+{$i} month");
            $predictedVal = max(0, $a * ($n - 1 + $i) + $b);

            $predictions[] = [
                'month' => $futureDate->format('M Y'),
                'value' => round($predictedVal, 2),
            ];
        }

        return [
            'predictions'     => $predictions,
            'r2'              => round($r2 * 100, 1), // en %
            'historicalLabels' => $monthly['labels'],
            'historicalData'  => $monthly['data'],
        ];
    }

    private function linearRegression(array $xs, array $ys): array
    {
        $n    = count($xs);
        $sumX = array_sum($xs);
        $sumY = array_sum($ys);
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $xs[$i] * $ys[$i];
            $sumX2 += $xs[$i] * $xs[$i];
        }

        $denom = ($n * $sumX2 - $sumX * $sumX);
        if ($denom == 0) return [0, $sumY / $n];

        $a = ($n * $sumXY - $sumX * $sumY) / $denom;
        $b = ($sumY - $a * $sumX) / $n;

        return [$a, $b];
    }

    private function calculateR2(array $xs, array $ys, float $a, float $b): float
    {
        $n     = count($ys);
        $meanY = array_sum($ys) / $n;

        $totalSS   = 0;
        $residualSS = 0;

        for ($i = 0; $i < $n; $i++) {
            $predicted   = $a * $xs[$i] + $b;
            $totalSS    += pow($ys[$i] - $meanY, 2);
            $residualSS += pow($ys[$i] - $predicted, 2);
        }

        if ($totalSS == 0) return 0;
        return max(0, min(1, 1 - ($residualSS / $totalSS)));
    }

    private function getTopEmployees(array $salaires): array
    {
        $byEmployee = [];

        foreach ($salaires as $s) {
            $name = $s->getUser()->getUsername();
            $byEmployee[$name][] = $s->getTotalAmount();
        }

        $averages = [];
        foreach ($byEmployee as $name => $amounts) {
            $averages[$name] = array_sum($amounts) / count($amounts);
        }

        arsort($averages); // tri décroissant

        return array_slice($averages, 0, 5, true); // top 5
    }

    private function emptyStats(): array
    {
        return [
            'totalCount'     => 0,
            'averageSalary'  => 0,
            'totalAmount'    => 0,
            'maxSalary'      => 0,
            'minSalary'      => 0,
            'totalBonus'     => 0,
            'averageBonus'   => 0,
            'paidCount'      => 0,
            'enCoursCount'   => 0,
            'creeCount'      => 0,
            'paidPercentage' => 0,
            'monthlyData'    => ['labels' => [], 'data' => []],
            'prediction'     => ['predictions' => [], 'r2' => 0, 'message' => 'Aucune donnée'],
            'topEmployees'   => [],
        ];
    }
}