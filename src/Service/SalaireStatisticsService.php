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

        $totalCount = count($salaires);

        // ── Tous statuts pour les compteurs ──
        $salairesPayes   = array_values(array_filter($salaires, fn($s) => $s->getStatus() === 'PAYÉ'));
        $salairesEnCours = array_values(array_filter($salaires, fn($s) => $s->getStatus() === 'EN_COURS'));
        $salairesCreed   = array_values(array_filter($salaires, fn($s) => $s->getStatus() === 'CREÉ'));

        $paidCount    = count($salairesPayes);
        $enCoursCount = count($salairesEnCours);
        $creeCount    = count($salairesCreed);

        // ── Montants sur TOUS les salaires pour les KPI ──
        $amounts = array_map(fn($s) => $s->getTotalAmount(), $salaires);
        $bonuses = array_map(fn($s) => $s->getBonusAmount(), $salaires);

        // ── Montants PAYÉS pour total versé réel ──
        $amountsPayes = count($salairesPayes) > 0
            ? array_map(fn($s) => $s->getTotalAmount(), $salairesPayes)
            : [];

        return [
            'totalCount'     => $totalCount,

            // Moyenne sur TOUS (sinon 0 si aucun payé)
            'averageSalary'  => array_sum($amounts) / $totalCount,

            // Total versé = PAYÉS uniquement (0 si aucun payé)
            'totalAmount'    => array_sum($amountsPayes),

            // Max/Min sur TOUS
            'maxSalary'      => max($amounts),
            'minSalary'      => min($amounts),

            'totalBonus'     => array_sum($bonuses),
            'averageBonus'   => array_sum($bonuses) / $totalCount,

            'paidCount'      => $paidCount,
            'enCoursCount'   => $enCoursCount,
            'creeCount'      => $creeCount,
            'paidPercentage' => ($paidCount / $totalCount) * 100,

            // ✅ Graphique et prédiction sur TOUS les salaires
            'monthlyData'    => $this->getMonthlyData($salaires),
            'prediction'     => $this->getLinearPrediction($salaires),

            // Top 5 sur TOUS
            'topEmployees'   => $this->getTopEmployees($salaires),
        ];
    }

    // ─────────────────────────────────────────────
    // Moyenne des salaires par mois — TOUS statuts
    // ─────────────────────────────────────────────
    private function getMonthlyData(array $salaires): array
    {
        $grouped = [];

        foreach ($salaires as $s) {
            $date = $s->getDatePaiement();
            if (!$date) continue;
            $key = $date->format('Y-m');
            $grouped[$key][] = $s->getTotalAmount();
        }

        if (empty($grouped)) {
            return ['labels' => [], 'data' => []];
        }

        ksort($grouped);

        $labels = [];
        $data   = [];

        foreach ($grouped as $yearMonth => $vals) {
            $dt       = \DateTime::createFromFormat('Y-m', $yearMonth);
            $labels[] = $dt->format('M Y');
            $data[]   = round(array_sum($vals) / count($vals), 2);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    // ─────────────────────────────────────────────
    // Régression linéaire — prédiction 3 mois
    // ─────────────────────────────────────────────
    private function getLinearPrediction(array $salaires): array
    {
        $monthly = $this->getMonthlyData($salaires);

        if (count($monthly['data']) < 2) {
            return [
                'predictions' => [],
                'r2'          => 0,
                'message'     => 'Minimum 2 mois de données requis',
            ];
        }

        $n  = count($monthly['data']);
        $xs = range(0, $n - 1);
        $ys = $monthly['data'];

        [$a, $b] = $this->linearRegression($xs, $ys);
        $r2      = $this->calculateR2($xs, $ys, $a, $b);

        // Prédire les 3 prochains mois
        $predictions = [];

        // Retrouver la dernière date à partir du dernier label
        $lastLabel = end($monthly['labels']);
        $lastDate  = \DateTime::createFromFormat('M Y', $lastLabel);

        if (!$lastDate) {
            // Fallback si le format échoue
            $lastDate = new \DateTime();
        }

        for ($i = 1; $i <= 3; $i++) {
            $futureDate   = clone $lastDate;
            $futureDate->modify("+{$i} month");
            $predictedVal = $a * ($n - 1 + $i) + $b;

            // ✅ Ne jamais retourner une valeur négative
            $predictedVal = max(0, round($predictedVal, 2));

            $predictions[] = [
                'month' => $futureDate->format('M Y'),
                'value' => $predictedVal,
            ];
        }

        return [
            'predictions'      => $predictions,
            'r2'               => round($r2 * 100, 1),
            'historicalLabels' => $monthly['labels'],
            'historicalData'   => $monthly['data'],
        ];
    }

    // ─────────────────────────────────────────────
    // Calcul régression linéaire y = ax + b
    // ─────────────────────────────────────────────
    private function linearRegression(array $xs, array $ys): array
    {
        $n     = count($xs);
        $sumX  = array_sum($xs);
        $sumY  = array_sum($ys);
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $xs[$i] * $ys[$i];
            $sumX2 += $xs[$i] * $xs[$i];
        }

        $denom = ($n * $sumX2 - $sumX * $sumX);

        if ($denom == 0) {
            return [0, $n > 0 ? $sumY / $n : 0];
        }

        $a = ($n * $sumXY - $sumX * $sumY) / $denom;
        $b = ($sumY - $a * $sumX) / $n;

        return [$a, $b];
    }

    // ─────────────────────────────────────────────
    // Coefficient de détermination R²
    // ─────────────────────────────────────────────
    private function calculateR2(array $xs, array $ys, float $a, float $b): float
    {
        $n     = count($ys);
        $meanY = array_sum($ys) / $n;

        $totalSS    = 0;
        $residualSS = 0;

        for ($i = 0; $i < $n; $i++) {
            $predicted   = $a * $xs[$i] + $b;
            $totalSS    += pow($ys[$i] - $meanY, 2);
            $residualSS += pow($ys[$i] - $predicted, 2);
        }

        if ($totalSS == 0) return 1; // Toutes les valeurs identiques = fit parfait

        return max(0, min(1, 1 - ($residualSS / $totalSS)));
    }

    // ─────────────────────────────────────────────
    // Top 5 employés par salaire moyen
    // ─────────────────────────────────────────────
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

        arsort($averages);

        return array_slice($averages, 0, 5, true);
    }

    // ─────────────────────────────────────────────
    // Stats vides si aucun salaire
    // ─────────────────────────────────────────────
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
            'prediction'     => [
                'predictions' => [],
                'r2'          => 0,
                'message'     => 'Aucune donnée disponible',
            ],
            'topEmployees'   => [],
        ];
    }
}