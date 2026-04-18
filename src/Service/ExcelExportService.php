<?php

namespace App\Service;

use App\Entity\Salaire;
use App\Repository\SalaireRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ExcelExportService
{
    public function __construct(
        private SalaireRepository $salaireRepo
    ) {}

    // ─────────────────────────────────────────────
    // MÉTHODE PRINCIPALE
    // ─────────────────────────────────────────────
    public function export(array $filters): string
    {
        $salaires = $this->getSalairesWithFilters($filters);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setTitle('Export Salaires - INTEGRA RH')
            ->setCreator('INTEGRA RH');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Salaires');
        $this->buildSalairesSheet($sheet, $salaires, $filters);

        if (!empty($filters['inclure_statistiques'])) {
            $statsSheet = $spreadsheet->createSheet();
            $statsSheet->setTitle('Statistiques');
            $this->buildStatistiquesSheet($statsSheet, $salaires, $filters);
        }

        if (!empty($filters['inclure_bonus'])) {
            $bonusSheet = $spreadsheet->createSheet();
            $bonusSheet->setTitle('Détails Bonus');
            $this->buildBonusSheet($bonusSheet, $salaires, $filters);
        }

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }

    // ─────────────────────────────────────────────
    // FILTRAGE
    // ─────────────────────────────────────────────
    private function getSalairesWithFilters(array $filters): array
    {
        $all = $this->salaireRepo->findAll();

        return array_values(array_filter($all, function (Salaire $s) use ($filters) {
            $date        = $s->getDatePaiement();
            $periodeType = $filters['periode_type'] ?? 'TOUS';

            if ($periodeType === 'MOIS' && $date) {
                $mois  = (int)($filters['mois']       ?? 0);
                $annee = (int)($filters['annee_mois'] ?? 0);
                if ((int)$date->format('n') !== $mois || (int)$date->format('Y') !== $annee) {
                    return false;
                }
            }

            if ($periodeType === 'ANNEE' && $date) {
                $annee = (int)($filters['annee'] ?? 0);
                if ((int)$date->format('Y') !== $annee) return false;
            }

            if ($periodeType === 'PERSONNALISEE' && $date) {
                $debut = !empty($filters['date_debut']) ? new \DateTime($filters['date_debut']) : null;
                $fin   = !empty($filters['date_fin'])   ? new \DateTime($filters['date_fin'])   : null;
                if ($debut && $date < $debut) return false;
                if ($fin   && $date > $fin)   return false;
            }

            $statusFilters = array_values(array_filter([
                !empty($filters['status_paye'])     ? 'PAYÉ'     : null,
                !empty($filters['status_en_cours']) ? 'EN_COURS' : null,
                !empty($filters['status_cree'])     ? 'CREÉ'     : null,
            ]));
            if (!empty($statusFilters) && !in_array($s->getStatus(), $statusFilters)) {
                return false;
            }

            if (!empty($filters['montant_min']) && (float)$filters['montant_min'] > 0) {
                if ($s->getTotalAmount() < (float)$filters['montant_min']) return false;
            }

            return true;
        }));
    }

    // ─────────────────────────────────────────────
    // HELPER : écrire une cellule par col (int) + row (int)
    // ─────────────────────────────────────────────
    private function cell(int $col, int $row): string
    {
        return Coordinate::stringFromColumnIndex($col) . $row;
    }

    private function write($sheet, int $col, int $row, $value): void
    {
        $sheet->setCellValue($this->cell($col, $row), $value);
    }

    // ─────────────────────────────────────────────
    // FEUILLE 1 : SALAIRES
    // ─────────────────────────────────────────────
    private function buildSalairesSheet($sheet, array $salaires, array $filters): void
    {
        $formatage = !empty($filters['appliquer_formatage']);

        // ── Titre ──
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'LISTE DES SALAIRES - INTEGRA RH — ' . $this->getPeriodeLabel($filters));
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 15, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF667EEA']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(38);

        // ── Sous-titre ──
        $sheet->mergeCells('A2:H2');
        $sheet->setCellValue('A2',
            count($salaires) . ' salaire(s) — Généré le ' . date('d/m/Y à H:i'));
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 10, 'color' => ['argb' => 'FF64748B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getRowDimension(3)->setRowHeight(8);

        // ── En-têtes ──
        $headers = ['ID', 'Employé', 'Email', 'Base (DT)', 'Bonus (DT)', 'Total (DT)', 'Statut', 'Date Paiement'];
        foreach ($headers as $i => $h) {
            $this->write($sheet, $i + 1, 4, $h);
        }
        $sheet->getStyle('A4:H4')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF1E293B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                             'color'       => ['argb' => 'FF334155']]],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(26);

        // ── Données ──
        $row   = 5;
        $zebra = false;

        foreach ($salaires as $salaire) {
            $bgColor = ($zebra && $formatage) ? 'FFF8FAFC' : 'FFFFFFFF';

            $this->write($sheet, 1, $row, $salaire->getId());
            $this->write($sheet, 2, $row, $salaire->getUser()->getUsername());
            $this->write($sheet, 3, $row, $salaire->getUser()->getEmail());
            $this->write($sheet, 4, $row, $salaire->getBaseAmount());
            $this->write($sheet, 5, $row, $salaire->getBonusAmount());
            $this->write($sheet, 6, $row, $salaire->getTotalAmount());
            $this->write($sheet, 7, $row, $salaire->getStatus());
            $this->write($sheet, 8, $row,
                $salaire->getDatePaiement() ? $salaire->getDatePaiement()->format('d/m/Y') : '—');

            // Style ligne
            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => $bgColor]],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                                 'color'       => ['argb' => 'FFE2E8F0']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);

            // Format numérique cols 4,5,6
            foreach ([4, 5, 6] as $numCol) {
                $sheet->getStyle($this->cell($numCol, $row))
                      ->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle($this->cell($numCol, $row))
                      ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }

            // Badge statut coloré
            if ($formatage) {
                [$bg, $fg] = match($salaire->getStatus()) {
                    'PAYÉ'     => ['FF166534', 'FFFFFFFF'],
                    'EN_COURS' => ['FF0369A1', 'FFFFFFFF'],
                    default    => ['FF854D0E', 'FFFFFFFF'],
                };
                $sheet->getStyle("G{$row}")->applyFromArray([
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => $bg]],
                    'font'      => ['bold' => true, 'color' => ['argb' => $fg]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }

            $sheet->getRowDimension($row)->setRowHeight(20);
            $zebra = !$zebra;
            $row++;
        }

        // ── Ligne TOTAL ──
        $lastData = $row - 1;
        $totalRow = $row + 1;

        $sheet->mergeCells("A{$totalRow}:C{$totalRow}");
        $sheet->setCellValue("A{$totalRow}", 'TOTAL');
        $sheet->setCellValue("D{$totalRow}", "=SUM(D5:D{$lastData})");
        $sheet->setCellValue("E{$totalRow}", "=SUM(E5:E{$lastData})");
        $sheet->setCellValue("F{$totalRow}", "=SUM(F5:F{$lastData})");

        $totalStyle = [
            'font'      => ['bold' => true, 'size' => 11],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FFFDE68A']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM,
                                             'color'       => ['argb' => 'FFD97706']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT,
                            'vertical'   => Alignment::VERTICAL_CENTER],
        ];
        $sheet->getStyle("A{$totalRow}:H{$totalRow}")->applyFromArray($totalStyle);
        foreach ([4, 5, 6] as $c) {
            $sheet->getStyle($this->cell($c, $totalRow))
                  ->getNumberFormat()->setFormatCode('#,##0.00');
        }
        $sheet->getRowDimension($totalRow)->setRowHeight(22);

        // ── Ligne MOYENNE ──
        $avgRow = $totalRow + 1;
        $sheet->mergeCells("A{$avgRow}:C{$avgRow}");
        $sheet->setCellValue("A{$avgRow}", 'MOYENNE');
        $sheet->setCellValue("D{$avgRow}", "=AVERAGE(D5:D{$lastData})");
        $sheet->setCellValue("E{$avgRow}", "=AVERAGE(E5:E{$lastData})");
        $sheet->setCellValue("F{$avgRow}", "=AVERAGE(F5:F{$lastData})");

        $sheet->getStyle("A{$avgRow}:H{$avgRow}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FFE0F2FE']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM,
                                             'color'       => ['argb' => 'FF0369A1']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT,
                            'vertical'   => Alignment::VERTICAL_CENTER],
        ]);
        foreach ([4, 5, 6] as $c) {
            $sheet->getStyle($this->cell($c, $avgRow))
                  ->getNumberFormat()->setFormatCode('#,##0.00');
        }

        // ── Largeurs colonnes ──
        foreach ([6, 20, 28, 14, 14, 14, 12, 16] as $i => $w) {
            $sheet->getColumnDimensionByColumn($i + 1)->setWidth($w);
        }
    }

    // ─────────────────────────────────────────────
    // FEUILLE 2 : STATISTIQUES
    // ─────────────────────────────────────────────
    private function buildStatistiquesSheet($sheet, array $salaires, array $filters): void
    {
        $sheet->mergeCells('A1:B1');
        $sheet->setCellValue('A1', 'STATISTIQUES — ' . $this->getPeriodeLabel($filters));
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF667EEA']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(34);

        $total    = count($salaires);
        $payes    = count(array_filter($salaires, fn($s) => $s->getStatus() === 'PAYÉ'));
        $enCours  = count(array_filter($salaires, fn($s) => $s->getStatus() === 'EN_COURS'));
        $crees    = count(array_filter($salaires, fn($s) => $s->getStatus() === 'CREÉ'));

        $amounts        = array_map(fn($s) => $s->getTotalAmount(), $salaires);
        $bonuses        = array_map(fn($s) => $s->getBonusAmount(), $salaires);
        $totalMontant   = array_sum($amounts);
        $moyenneMontant = $total > 0 ? $totalMontant / $total : 0;
        $maxMontant     = $total > 0 ? max($amounts) : 0;
        $minMontant     = $total > 0 ? min($amounts) : 0;
        $totalBonus     = array_sum($bonuses);
        $moyenneBonus   = $total > 0 ? $totalBonus / $total : 0;

        $stats = [
            ['Nombre de salaires',   $total,          false],
            [null, null, null],
            ['Salaires PAYÉS',       $payes,          false],
            ['Salaires EN COURS',    $enCours,        false],
            ['Salaires CRÉÉS',       $crees,          false],
            [null, null, null],
            ['Total montant versé',  $totalMontant,   true],
            ['Salaire moyen',        $moyenneMontant, true],
            ['Salaire maximum',      $maxMontant,     true],
            ['Salaire minimum',      $minMontant,     true],
            [null, null, null],
            ['Total bonus versés',   $totalBonus,     true],
            ['Bonus moyen',          $moyenneBonus,   true],
        ];

        $row = 3;
        foreach ($stats as [$label, $value, $isAmount]) {
            if ($label === null) { $row++; continue; }

            $sheet->setCellValue("A{$row}", $label);
            $sheet->setCellValue("B{$row}",
                $isAmount ? number_format((float)$value, 2, ',', ' ') . ' DT' : $value);

            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $sheet->getStyle("B{$row}")->getAlignment()
                  ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("A{$row}:B{$row}")->getBorders()
                  ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(26);
        $sheet->getColumnDimension('B')->setWidth(18);
    }

    // ─────────────────────────────────────────────
    // FEUILLE 3 : DÉTAILS BONUS
    // ─────────────────────────────────────────────
    private function buildBonusSheet($sheet, array $salaires, array $filters): void
    {
        $formatage = !empty($filters['appliquer_formatage']);

        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'DÉTAILS DES RÈGLES DE BONUS');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF764BA2']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(34);

        $headers = ['Employé', 'Règle', 'Pourcentage', 'Montant (DT)', 'Condition', 'Statut'];
        foreach ($headers as $i => $h) {
            $this->write($sheet, $i + 1, 3, $h);
        }
        $sheet->getStyle('A3:F3')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF1E293B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $row   = 4;
        $zebra = false;

        foreach ($salaires as $salaire) {
            foreach ($salaire->getBonusRules() as $rule) {
                $bg = ($zebra && $formatage) ? 'FFF8FAFC' : 'FFFFFFFF';

                $this->write($sheet, 1, $row, $salaire->getUser()->getUsername());
                $this->write($sheet, 2, $row, $rule->getNomRegle());
                $this->write($sheet, 3, $row, $rule->getPercentage() . '%');
                $this->write($sheet, 4, $row, $rule->getBonus());
                $this->write($sheet, 5, $row, $rule->getConditionText());
                $this->write($sheet, 6, $row, $rule->getStatus());

                $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                    'fill'    => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => $bg]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                                   'color'       => ['argb' => 'FFE2E8F0']]],
                ]);
                $sheet->getStyle($this->cell(4, $row))
                      ->getNumberFormat()->setFormatCode('#,##0.00');

                $row++;
                $zebra = !$zebra;
            }
        }

        foreach ([18, 22, 12, 14, 35, 12] as $i => $w) {
            $sheet->getColumnDimensionByColumn($i + 1)->setWidth($w);
        }
    }

    // ─────────────────────────────────────────────
    // UTILITAIRES
    // ─────────────────────────────────────────────
    private function getPeriodeLabel(array $filters): string
    {
        $moisNoms = ['', 'Janvier','Février','Mars','Avril','Mai','Juin',
                     'Juillet','Août','Septembre','Octobre','Novembre','Décembre'];

        return match($filters['periode_type'] ?? 'TOUS') {
            'MOIS'          => ($moisNoms[$filters['mois'] ?? 1] ?? '') . ' ' . ($filters['annee_mois'] ?? date('Y')),
            'ANNEE'         => 'Année ' . ($filters['annee'] ?? date('Y')),
            'PERSONNALISEE' => 'Du ' . ($filters['date_debut'] ?? '—') . ' au ' . ($filters['date_fin'] ?? '—'),
            default         => 'Tous les salaires',
        };
    }

    public function getFilename(array $filters): string
    {
        $moisNoms = ['', 'janvier','fevrier','mars','avril','mai','juin',
                     'juillet','aout','septembre','octobre','novembre','decembre'];

        $suffix = match($filters['periode_type'] ?? 'TOUS') {
            'MOIS'          => ($moisNoms[$filters['mois'] ?? 1] ?? 'mois') . '_' . ($filters['annee_mois'] ?? date('Y')),
            'ANNEE'         => (string)($filters['annee'] ?? date('Y')),
            'PERSONNALISEE' => ($filters['date_debut'] ?? '') . '_au_' . ($filters['date_fin'] ?? ''),
            default         => 'complet_' . date('d-m-Y'),
        };

        return 'salaires_' . $suffix . '.xlsx';
    }
}