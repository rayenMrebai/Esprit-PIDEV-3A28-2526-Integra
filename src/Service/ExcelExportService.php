<?php

namespace App\Service;

use App\Entity\Salaire;
use App\Repository\SalaireRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;
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

        // Feuille 1 : Liste des salaires
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Salaires');
        $this->buildSalairesSheet($sheet, $salaires, $filters);

        // Feuille 2 : Statistiques (si demandé)
        if (!empty($filters['inclure_statistiques'])) {
            $statsSheet = $spreadsheet->createSheet();
            $statsSheet->setTitle('Statistiques');
            $this->buildStatistiquesSheet($statsSheet, $salaires, $filters);
        }

        // Feuille 3 : Détails Bonus (si demandé)
        if (!empty($filters['inclure_bonus'])) {
            $bonusSheet = $spreadsheet->createSheet();
            $bonusSheet->setTitle('Détails Bonus');
            $this->buildBonusSheet($bonusSheet, $salaires, $filters);
        }

        // Générer le fichier en mémoire
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }

    // ─────────────────────────────────────────────
    // FILTRAGE DES SALAIRES
    // ─────────────────────────────────────────────
    private function getSalairesWithFilters(array $filters): array
    {
        $all = $this->salaireRepo->findAll();

        return array_filter($all, function (Salaire $s) use ($filters) {
            // Filtre période
            $date = $s->getDatePaiement();
            $periodeType = $filters['periode_type'] ?? 'TOUS';

            if ($periodeType === 'MOIS' && $date) {
                $mois  = (int)($filters['mois'] ?? 0);
                $annee = (int)($filters['annee'] ?? 0);
                if ($date->format('n') != $mois || $date->format('Y') != $annee) {
                    return false;
                }
            }

            if ($periodeType === 'ANNEE' && $date) {
                $annee = (int)($filters['annee'] ?? 0);
                if ($date->format('Y') != $annee) {
                    return false;
                }
            }

            if ($periodeType === 'PERSONNALISEE' && $date) {
                $debut = $filters['date_debut'] ? new \DateTime($filters['date_debut']) : null;
                $fin   = $filters['date_fin']   ? new \DateTime($filters['date_fin'])   : null;
                if ($debut && $date < $debut) return false;
                if ($fin   && $date > $fin)   return false;
            }

            // Filtre statut
            $statusFilters = array_filter([
                !empty($filters['status_paye'])    ? 'PAYÉ'    : null,
                !empty($filters['status_en_cours']) ? 'EN_COURS' : null,
                !empty($filters['status_cree'])    ? 'CREÉ'    : null,
            ]);
            if (!empty($statusFilters) && !in_array($s->getStatus(), $statusFilters)) {
                return false;
            }

            // Filtre montant minimum
            if (!empty($filters['montant_min']) && $filters['montant_min'] > 0) {
                if ($s->getTotalAmount() < (float)$filters['montant_min']) {
                    return false;
                }
            }

            return true;
        });
    }

    // ─────────────────────────────────────────────
    // FEUILLE 1 : SALAIRES
    // ─────────────────────────────────────────────
    private function buildSalairesSheet($sheet, array $salaires, array $filters): void
    {
        $formatage = !empty($filters['appliquer_formatage']);

        // ── Titre principal ──
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', '📊 LISTE DES SALAIRES - INTEGRA RH — ' . $this->getPeriodeLabel($filters));
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF667EEA']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(40);

        // ── Sous-titre ──
        $sheet->mergeCells('A2:H2');
        $sheet->setCellValue('A2', count($salaires) . ' salaire(s) exporté(s) — Généré le ' . date('d/m/Y à H:i'));
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 10, 'color' => ['argb' => 'FF64748B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getRowDimension(3)->setRowHeight(8);

        // ── En-têtes colonnes ──
        $headers = ['ID', 'Employé', 'Email', 'Base (DT)', 'Bonus (DT)', 'Total (DT)', 'Statut', 'Date Paiement'];
        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValueByColumnAndRow($col, 4, $header);
            $col++;
        }

        $sheet->getStyle('A4:H4')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF1E293B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF334155']]],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(28);

        // ── Données ──
        $row = 5;
        $zebra = false;

        foreach ($salaires as $salaire) {
            $bgColor = $zebra && $formatage ? 'FFF8FAFC' : 'FFFFFFFF';

            $sheet->setCellValueByColumnAndRow(1, $row, $salaire->getId());
            $sheet->setCellValueByColumnAndRow(2, $row, $salaire->getUser()->getUsername());
            $sheet->setCellValueByColumnAndRow(3, $row, $salaire->getUser()->getEmail());
            $sheet->setCellValueByColumnAndRow(4, $row, $salaire->getBaseAmount());
            $sheet->setCellValueByColumnAndRow(5, $row, $salaire->getBonusAmount());
            $sheet->setCellValueByColumnAndRow(6, $row, $salaire->getTotalAmount());
            $sheet->setCellValueByColumnAndRow(7, $row, $salaire->getStatus());
            $sheet->setCellValueByColumnAndRow(8, $row,
                $salaire->getDatePaiement() ? $salaire->getDatePaiement()->format('d/m/Y') : '—'
            );

            // Style de base de la ligne
            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => $bgColor]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE2E8F0']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);

            // Format numérique
            foreach ([4, 5, 6] as $numCol) {
                $cell = Coordinate::stringFromColumnIndex($numCol) . $row;
                $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }

            // Badge statut coloré
            if ($formatage) {
                $statusCol = "G{$row}";
                $statusColor = match($salaire->getStatus()) {
                    'PAYÉ'    => ['bg' => 'FF166534', 'fg' => 'FFFFFFFF'],
                    'EN_COURS' => ['bg' => 'FF0369A1', 'fg' => 'FFFFFFFF'],
                    default   => ['bg' => 'FF854D0E', 'fg' => 'FFFFFFFF'],
                };
                $sheet->getStyle($statusCol)->applyFromArray([
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => $statusColor['bg']]],
                    'font'      => ['bold' => true, 'color' => ['argb' => $statusColor['fg']]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }

            $sheet->getRowDimension($row)->setRowHeight(22);
            $zebra = !$zebra;
            $row++;
        }

        // ── Ligne TOTAL ──
        $totalRow = $row + 1;
        $lastDataRow = $row - 1;

        $sheet->mergeCells("A{$totalRow}:C{$totalRow}");
        $sheet->setCellValue("A{$totalRow}", 'TOTAL');
        $sheet->setCellValue("D{$totalRow}", "=SUM(D5:D{$lastDataRow})");
        $sheet->setCellValue("E{$totalRow}", "=SUM(E5:E{$lastDataRow})");
        $sheet->setCellValue("F{$totalRow}", "=SUM(F5:F{$lastDataRow})");

        $sheet->getStyle("A{$totalRow}:H{$totalRow}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FF1E293B']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FFFDE68A']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FFD97706']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        foreach ([4, 5, 6] as $numCol) {
            $cell = Coordinate::stringFromColumnIndex($numCol) . $totalRow;
            $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0.00');
        }
        $sheet->getRowDimension($totalRow)->setRowHeight(24);

        // ── Ligne MOYENNE ──
        $avgRow = $totalRow + 1;
        $sheet->mergeCells("A{$avgRow}:C{$avgRow}");
        $sheet->setCellValue("A{$avgRow}", 'MOYENNE');
        $sheet->setCellValue("D{$avgRow}", "=AVERAGE(D5:D{$lastDataRow})");
        $sheet->setCellValue("E{$avgRow}", "=AVERAGE(E5:E{$lastDataRow})");
        $sheet->setCellValue("F{$avgRow}", "=AVERAGE(F5:F{$lastDataRow})");

        $sheet->getStyle("A{$avgRow}:H{$avgRow}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FFE0F2FE']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF0369A1']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        foreach ([4, 5, 6] as $numCol) {
            $cell = Coordinate::stringFromColumnIndex($numCol) . $avgRow;
            $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0.00');
        }

        // ── Largeurs colonnes ──
        $widths = [6, 20, 28, 14, 14, 14, 12, 16];
        foreach ($widths as $i => $w) {
            $sheet->getColumnDimensionByColumn($i + 1)->setWidth($w);
        }
    }

    // ─────────────────────────────────────────────
    // FEUILLE 2 : STATISTIQUES
    // ─────────────────────────────────────────────
    private function buildStatistiquesSheet($sheet, array $salaires, array $filters): void
    {
        // Titre
        $sheet->mergeCells('A1:B1');
        $sheet->setCellValue('A1', '📈 STATISTIQUES — ' . $this->getPeriodeLabel($filters));
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF667EEA']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(36);

        // Calculs
        $total    = count($salaires);
        $payes    = count(array_filter($salaires, fn($s) => $s->getStatus() === 'PAYÉ'));
        $enCours  = count(array_filter($salaires, fn($s) => $s->getStatus() === 'EN_COURS'));
        $crees    = count(array_filter($salaires, fn($s) => $s->getStatus() === 'CREÉ'));

        $amounts = array_map(fn($s) => $s->getTotalAmount(), $salaires);
        $bonuses  = array_map(fn($s) => $s->getBonusAmount(), $salaires);

        $totalMontant  = array_sum($amounts);
        $moyenneMontant = $total > 0 ? $totalMontant / $total : 0;
        $maxMontant    = $total > 0 ? max($amounts) : 0;
        $minMontant    = $total > 0 ? min($amounts) : 0;
        $totalBonus    = array_sum($bonuses);
        $moyenneBonus  = $total > 0 ? $totalBonus / $total : 0;

        $stats = [
            ['Nombre de salaires',         $total,          'count'],
            ['─────────────────', '', 'sep'],
            ['Salaires PAYÉS',              $payes,          'count'],
            ['Salaires EN COURS',           $enCours,        'count'],
            ['Salaires CRÉÉS',              $crees,          'count'],
            ['─────────────────', '', 'sep'],
            ['Total montant versé',         $totalMontant,   'amount'],
            ['Salaire moyen',               $moyenneMontant, 'amount'],
            ['Salaire maximum',             $maxMontant,     'amount'],
            ['Salaire minimum',             $minMontant,     'amount'],
            ['─────────────────', '', 'sep'],
            ['Total bonus versés',          $totalBonus,     'amount'],
            ['Bonus moyen',                 $moyenneBonus,   'amount'],
        ];

        $row = 3;
        foreach ($stats as [$label, $value, $type]) {
            if ($type === 'sep') {
                $row++;
                continue;
            }
            $sheet->setCellValue("A{$row}", $label);
            $sheet->setCellValue("B{$row}", $type === 'amount' ? number_format($value, 2, ',', ' ') . ' DT' : $value);

            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("A{$row}:B{$row}")->getBorders()
                ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(18);
    }

    // ─────────────────────────────────────────────
    // FEUILLE 3 : DÉTAILS BONUS
    // ─────────────────────────────────────────────
    private function buildBonusSheet($sheet, array $salaires, array $filters): void
    {
        $formatage = !empty($filters['appliquer_formatage']);

        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', '💰 DÉTAILS DES RÈGLES DE BONUS');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF764BA2']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(36);

        $headers = ['Employé', 'Règle', 'Pourcentage', 'Montant (DT)', 'Condition', 'Statut'];
        $col = 1;
        foreach ($headers as $h) {
            $sheet->setCellValueByColumnAndRow($col++, 3, $h);
        }
        $sheet->getStyle('A3:F3')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF1E293B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $row = 4;
        $zebra = false;
        foreach ($salaires as $salaire) {
            foreach ($salaire->getBonusRules() as $rule) {
                $bg = $zebra && $formatage ? 'FFF8FAFC' : 'FFFFFFFF';

                $sheet->setCellValueByColumnAndRow(1, $row, $salaire->getUser()->getUsername());
                $sheet->setCellValueByColumnAndRow(2, $row, $rule->getNomRegle());
                $sheet->setCellValueByColumnAndRow(3, $row, $rule->getPercentage() . '%');
                $sheet->setCellValueByColumnAndRow(4, $row, $rule->getBonus());
                $sheet->setCellValueByColumnAndRow(5, $row, $rule->getConditionText());
                $sheet->setCellValueByColumnAndRow(6, $row, $rule->getStatus());

                $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                    'fill'    => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => $bg]],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE2E8F0']]],
                ]);
                $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0.00');

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
        return match($filters['periode_type'] ?? 'TOUS') {
            'MOIS'         => ['', 'Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'][$filters['mois'] ?? 1]
                              . ' ' . ($filters['annee'] ?? date('Y')),
            'ANNEE'        => 'Année ' . ($filters['annee'] ?? date('Y')),
            'PERSONNALISEE' => 'Du ' . ($filters['date_debut'] ?? '—') . ' au ' . ($filters['date_fin'] ?? '—'),
            default        => 'Tous les salaires',
        };
    }

    public function getFilename(array $filters): string
    {
        $suffix = match($filters['periode_type'] ?? 'TOUS') {
            'MOIS'         => strtolower(['', 'janvier','fevrier','mars','avril','mai','juin','juillet','aout','septembre','octobre','novembre','decembre'][$filters['mois'] ?? 1])
                              . '_' . ($filters['annee'] ?? date('Y')),
            'ANNEE'        => $filters['annee'] ?? date('Y'),
            'PERSONNALISEE' => ($filters['date_debut'] ?? '') . '_au_' . ($filters['date_fin'] ?? ''),
            default        => 'complet_' . date('d-m-Y'),
        };
        return 'salaires_' . $suffix . '.xlsx';
    }
}