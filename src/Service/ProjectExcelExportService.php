<?php

namespace App\Service;

use App\Entity\Project;
use App\Entity\Projectassignment;
use App\Entity\UserAccount;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Layout;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectExcelExportService
{
    private const C_BLUE       = 'FF0B63CE';
    private const C_GREEN      = 'FF0FA36B';
    private const C_RED        = 'FFEF4444';
    private const C_ORANGE     = 'FFF59E0B';
    private const C_DARK       = 'FF1E3A5F';
    private const C_LIGHT_BLUE = 'FFDBEAFE';
    private const C_WHITE      = 'FFFFFFFF';
    private const C_GRAY       = 'FF6B7280';

    /** @var array<int, string> */
    private array $employeeNameMap = [];

    /** @var array<int, Projectassignment[]> */
    private array $assignmentsByProject = [];

    /** @var array<int, float> */
    private array $totalAllocByEmployee = [];

    /**
     * @param Project[]              $projects
     * @param Projectassignment[]    $assignments
     * @param UserAccount[]          $employees
     */
    public function exportAllData(array $projects, array $assignments, array $employees): StreamedResponse
    {
        $this->buildMaps($assignments, $employees);
        $wb = new Spreadsheet();

        $this->sheetDashboard($wb, $projects, $assignments);
        $this->sheetProjects($wb, $projects);
        $this->sheetAssignments($wb, $assignments, $projects);
        $this->sheetStats($wb, $projects, $assignments);

        $wb->removeSheetByIndex(0);
        $wb->setActiveSheetIndex(0);

        return $this->stream($wb, 'INTEGRA_rapport_global_' . date('Ymd_His') . '.xlsx');
    }

    /**
     * @param Project              $project
     * @param Projectassignment[]  $assignments
     * @param UserAccount[]        $employees
     */
    public function exportSingleProject($project, array $assignments, array $employees): StreamedResponse
    {
        $this->buildMaps($assignments, $employees);
        $wb = new Spreadsheet();

        $this->sheetSingleProject($wb, $project, $assignments);
        $this->sheetSingleStats($wb, $assignments);

        $wb->removeSheetByIndex(0);
        $wb->setActiveSheetIndex(0);

        return $this->stream($wb, 'INTEGRA_projet_' . $project->getProjectId() . '_' . date('Ymd_His') . '.xlsx');
    }

    /**
     * @param Projectassignment[] $assignments
     * @param UserAccount[]       $employees
     */
    private function buildMaps(array $assignments, array $employees): void
    {
        $this->employeeNameMap = [];
        foreach ($employees as $e) {
            $this->employeeNameMap[$e->getUserId()] = $e->getUsername();
        }

        $this->assignmentsByProject = [];
        $this->totalAllocByEmployee = [];
        foreach ($assignments as $a) {
            $pid = $a->getProject()->getProjectId();
            $eid = $a->getUserAccount()->getUserId();
            $this->assignmentsByProject[$pid][] = $a;
            $this->totalAllocByEmployee[$eid] = ($this->totalAllocByEmployee[$eid] ?? 0) + $a->getAllocationRate();
        }
    }

    /**
     * @param Project[]           $projects
     * @param Projectassignment[] $assignments
     */
    private function sheetDashboard(Spreadsheet $wb, array $projects, array $assignments): void
    {
        $sh = $wb->createSheet();
        $sh->setTitle('📊 Dashboard');
        $sh->getTabColor()->setARGB('FF0B63CE');

        $sh->mergeCells('A1:H1');
        $sh->setCellValue('A1', '🏢  INTEGRA HR — RAPPORT ANALYTIQUE');
        $this->style($sh, 'A1:H1', [
            'font'      => ['bold' => true, 'size' => 18, 'color' => ['argb' => self::C_WHITE]],
            'fill'      => $this->solidFill('FF0B3D8C'),
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sh->getRowDimension(1)->setRowHeight(40);

        $sh->mergeCells('A2:H2');
        $sh->setCellValue('A2', 'Généré le ' . date('d/m/Y à H:i') . '  •  INTEGRA HR Management System');
        $this->style($sh, 'A2:H2', [
            'font'      => ['italic' => true, 'size' => 10, 'color' => ['argb' => self::C_WHITE]],
            'fill'      => $this->solidFill('FF0B63CE'),
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $row = 4;
        $kpiLabels = ['A' => '📁 Total Projets', 'B' => '👥 Total Affectations',
            'C' => '💰 Budget Total (TND)', 'D' => '📈 Alloc. Moyenne %',
            'E' => '✅ Projets Terminés', 'F' => '⚠️ Employés Surchargés'];

        foreach ($kpiLabels as $col => $label) {
            $sh->setCellValue($col . $row, $label);
            $this->style($sh, $col . $row, [
                'font'      => ['bold' => true, 'size' => 10, 'color' => ['argb' => self::C_DARK]],
                'fill'      => $this->solidFill(self::C_LIGHT_BLUE),
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders'   => $this->allBorders(),
            ]);
        }

        $row = 5;
        $totalBudget   = array_sum(array_map(fn($p) => $p->getBudget(), $projects));
        $avgAlloc      = count($assignments) > 0
            ? array_sum(array_map(fn($a) => $a->getAllocationRate(), $assignments)) / count($assignments) : 0;
        $completed     = count(array_filter($projects, fn($p) => $p->getStatus() === 'COMPLETED'));
        $overloaded    = count(array_filter($this->totalAllocByEmployee, fn($v) => $v > 100));

        $kpiValues = [
            'A' => count($projects),
            'B' => count($assignments),
            'C' => number_format($totalBudget, 2),
            'D' => number_format($avgAlloc, 1) . '%',
            'E' => $completed . ' / ' . count($projects),
            'F' => $overloaded,
        ];

        $kpiColors = ['A' => self::C_BLUE, 'B' => self::C_GREEN, 'C' => self::C_BLUE,
            'D' => self::C_ORANGE, 'E' => self::C_GREEN, 'F' => ($overloaded > 0 ? self::C_RED : self::C_GREEN)];

        foreach ($kpiValues as $col => $val) {
            $sh->setCellValue($col . $row, $val);
            $this->style($sh, $col . $row, [
                'font'      => ['bold' => true, 'size' => 16, 'color' => ['argb' => $kpiColors[$col]]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => $this->allBorders(),
            ]);
            $sh->getRowDimension($row)->setRowHeight(35);
        }

        $row = 7;
        $sh->mergeCells("A{$row}:C{$row}");
        $sh->setCellValue("A{$row}", '📌 Répartition par Statut');
        $this->style($sh, "A{$row}:C{$row}", [
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['argb' => self::C_WHITE]],
            'fill'      => $this->solidFill('FF0B63CE'),
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $statusColors = [
            'PLANNING'    => 'FFFFF3CD',
            'IN PROGRESS' => 'FFCFE2FF',
            'ACTIVE'      => 'FFD1FAE5',
            'ON HOLD'     => 'FFFEE2E2',
            'COMPLETED'   => 'FFF1F5F9',
        ];
        $statusBadgeColors = [
            'PLANNING'    => 'FFF59E0B',
            'IN PROGRESS' => 'FF3B82F6',
            'ACTIVE'      => 'FF10B981',
            'ON HOLD'     => 'FFEF4444',
            'COMPLETED'   => 'FF6B7280',
        ];

        $statusCount = array_count_values(array_map(fn($p) => $p->getStatus(), $projects));
        $row++;
        $sh->setCellValue("A{$row}", 'Statut');
        $sh->setCellValue("B{$row}", 'Nombre');
        $sh->setCellValue("C{$row}", '% du total');
        $this->style($sh, "A{$row}:C{$row}", [
            'font'    => ['bold' => true, 'size' => 10, 'color' => ['argb' => self::C_WHITE]],
            'fill'    => $this->solidFill('FF1E3A5F'),
            'borders' => $this->allBorders(),
        ]);

        $chartDataStart = $row + 1;
        foreach ($statusCount as $status => $cnt) {
            $row++;
            $pct = count($projects) > 0 ? round($cnt * 100 / count($projects), 1) : 0;
            $sh->setCellValue("A{$row}", $status);
            $sh->setCellValue("B{$row}", $cnt);
            $sh->setCellValue("C{$row}", $pct . '%');
            $bgColor = $statusColors[$status] ?? 'FFF8FAFC';
            $this->style($sh, "A{$row}:C{$row}", [
                'fill'    => $this->solidFill($bgColor),
                'font'    => ['bold' => true, 'color' => ['argb' => $statusBadgeColors[$status] ?? self::C_DARK]],
                'borders' => $this->allBorders(),
            ]);
        }
        $chartDataEnd = $row;

        $row += 2;
        $sh->mergeCells("A{$row}:C{$row}");
        $sh->setCellValue("A{$row}", '💰 Top 5 Projets par Budget');
        $this->style($sh, "A{$row}:C{$row}", [
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['argb' => self::C_WHITE]],
            'fill'      => $this->solidFill('FF0FA36B'),
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $row++;
        $sh->setCellValue("A{$row}", 'Projet');
        $sh->setCellValue("B{$row}", 'Budget (TND)');
        $sh->setCellValue("C{$row}", 'Statut');
        $this->style($sh, "A{$row}:C{$row}", [
            'font' => ['bold' => true, 'color' => ['argb' => self::C_WHITE]],
            'fill' => $this->solidFill('FF1E3A5F'),
            'borders' => $this->allBorders(),
        ]);

        $sorted = $projects;
        usort($sorted, fn($a, $b) => $b->getBudget() <=> $a->getBudget());
        $top5 = array_slice($sorted, 0, 5);
        $budgetChartStart = $row + 1;
        foreach ($top5 as $i => $p) {
            $row++;
            $sh->setCellValue("A{$row}", $p->getName());
            $sh->setCellValue("B{$row}", $p->getBudget());
            $sh->setCellValue("C{$row}", $p->getStatus());
            $bg = $i % 2 === 0 ? 'FFFAFAFA' : 'FFFFFFFF';
            $this->style($sh, "A{$row}:C{$row}", [
                'fill'    => $this->solidFill($bg),
                'borders' => $this->allBorders(),
            ]);
        }
        $budgetChartEnd = $row;

        $row += 2;
        $sh->mergeCells("A{$row}:C{$row}");
        $sh->setCellValue("A{$row}", '⚠️ Employés Surchargés (> 100%)');
        $this->style($sh, "A{$row}:C{$row}", [
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['argb' => self::C_WHITE]],
            'fill'      => $this->solidFill('FFEF4444'),
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $row++;
        $sh->setCellValue("A{$row}", 'Employé');
        $sh->setCellValue("B{$row}", 'Allocation totale %');
        $sh->setCellValue("C{$row}", 'Niveau de risque');
        $this->style($sh, "A{$row}:C{$row}", [
            'font'    => ['bold' => true, 'color' => ['argb' => self::C_WHITE]],
            'fill'    => $this->solidFill('FF1E3A5F'),
            'borders' => $this->allBorders(),
        ]);

        $over = array_filter($this->totalAllocByEmployee, fn($v) => $v > 100);
        arsort($over);
        foreach (array_slice($over, 0, 5, true) as $eid => $alloc) {
            $row++;
            $name = $this->employeeNameMap[$eid] ?? "Employé #$eid";
            $risk = $alloc >= 150 ? '🔴 CRITIQUE' : '🟠 ÉLEVÉ';
            $sh->setCellValue("A{$row}", $name);
            $sh->setCellValue("B{$row}", $alloc . '%');
            $sh->setCellValue("C{$row}", $risk);
            $this->style($sh, "A{$row}:C{$row}", [
                'fill'    => $this->solidFill('FFFEF2F2'),
                'font'    => ['color' => ['argb' => 'FFDC2626'], 'bold' => true],
                'borders' => $this->allBorders(),
            ]);
        }

        if (count($statusCount) > 1) {
            $this->addPieChart($sh, 'chart_status',
                'Répartition des projets par statut',
                '📊 Dashboard', $chartDataStart, $chartDataEnd,
                'E4', 'H20'
            );
        }

        if (count($top5) > 1) {
            $this->addBarChart($sh, 'chart_budget',
                'Top 5 Projets par Budget (TND)',
                '📊 Dashboard', $budgetChartStart, $budgetChartEnd,
                'E21', 'H38'
            );
        }

        foreach (range('A', 'H') as $c) {
            $sh->getColumnDimension($c)->setAutoSize(true);
        }
    }

    /**
     * @param Project[] $projects
     */
    private function sheetProjects(Spreadsheet $wb, array $projects): void
    {
        $sh = $wb->createSheet();
        $sh->setTitle('📁 Projets');
        $sh->getTabColor()->setARGB('FF0FA36B');

        $sh->mergeCells('A1:N1');
        $sh->setCellValue('A1', '📁  LISTE COMPLÈTE DES PROJETS RH');
        $this->style($sh, 'A1:N1', [
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => self::C_WHITE]],
            'fill'      => $this->solidFill('FF0FA36B'),
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sh->getRowDimension(1)->setRowHeight(30);

        $sh->mergeCells('A2:N2');
        $sh->setCellValue('A2', 'Exporté le ' . date('d/m/Y H:i'));
        $this->style($sh, 'A2:N2', [
            'font'      => ['italic' => true, 'size' => 9, 'color' => ['argb' => self::C_GRAY]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $headers = ['ID', 'Nom du projet', 'Description', 'Date début', 'Date fin',
            'Statut', 'Budget (TND)', 'Durée (j)', 'Nb Employés',
            'Alloc. totale %', 'Alloc. moy. %', 'Budget/Emp.', 'Budget/Jour', 'Efficacité'];
        $row = 3;
        foreach ($headers as $i => $h) {
            $col = $this->colLetter($i);
            $sh->setCellValue($col . $row, $h);
            $this->style($sh, $col . $row, [
                'font'      => ['bold' => true, 'size' => 10, 'color' => ['argb' => self::C_WHITE]],
                'fill'      => $this->solidFill('FF0FA36B'),
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
                'borders'   => $this->allBorders(),
            ]);
        }
        $sh->getRowDimension($row)->setRowHeight(25);

        $row = 4;
        $statusBg = [
            'PLANNING'    => 'FFFFF3CD',
            'IN PROGRESS' => 'FFCFE2FF',
            'ACTIVE'      => 'FFD1FAE5',
            'ON HOLD'     => 'FFFEE2E2',
            'COMPLETED'   => 'FFF1F5F9',
        ];

        foreach ($projects as $p) {
            $pas       = $this->assignmentsByProject[$p->getProjectId()] ?? [];
            $nb        = count($pas);
            $totAlloc  = array_sum(array_map(fn($a) => $a->getAllocationRate(), $pas));
            $avgAlloc  = $nb > 0 ? round($totAlloc / $nb, 2) : 0;
            $duration  = 0;
            if ($p->getStartDate() && $p->getEndDate()) {
                $duration = $p->getStartDate()->diff($p->getEndDate())->days;
            }
            $bpe  = $nb > 0 ? round($p->getBudget() / $nb, 2) : 0;
            $bpj  = $duration > 0 ? round($p->getBudget() / $duration, 2) : 0;
            $bg   = ($row % 2 === 0) ? 'FFFAFAFA' : 'FFFFFFFF';

            $values = [
                $p->getProjectId(), $p->getName(), $p->getDescription() ?? '',
                $p->getStartDate() ? $p->getStartDate()->format('d/m/Y') : '',
                $p->getEndDate() ? $p->getEndDate()->format('d/m/Y') : '',
                $p->getStatus(), $p->getBudget(), $duration, $nb,
                $totAlloc, $avgAlloc, $bpe, $bpj, $bpj,
            ];

            foreach ($values as $i => $v) {
                $col = $this->colLetter($i);
                $sh->setCellValue($col . $row, $v);
                $cellBg = ($i === 5) ? ($statusBg[$p->getStatus()] ?? $bg) : $bg;
                $this->style($sh, $col . $row, [
                    'fill'    => $this->solidFill($cellBg),
                    'borders' => $this->allBorders(),
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);
            }
            $row++;
        }

        if ($row > 4) {
            $cond = new Conditional();
            $cond->setConditionType(Conditional::CONDITION_CELLIS)
                ->setOperatorType(Conditional::OPERATOR_LESSTHAN)
                ->addCondition('1000');
            $cond->getStyle()->getFont()->getColor()->setARGB('FFEA580C');
            $cond->getStyle()->getFont()->setBold(true);
            $sh->getStyle('L4:L' . ($row - 1))->setConditionalStyles([$cond]);
        }

        foreach (range(0, count($headers) - 1) as $i) {
            $sh->getColumnDimension($this->colLetter($i))->setAutoSize(true);
        }
    }

    /**
     * @param Projectassignment[] $assignments
     * @param Project[]           $projects
     */
    private function sheetAssignments(Spreadsheet $wb, array $assignments, array $projects): void
    {
        $sh = $wb->createSheet();
        $sh->setTitle('👥 Affectations');
        $sh->getTabColor()->setARGB('FF0B63CE');

        $sh->mergeCells('A1:I1');
        $sh->setCellValue('A1', '👥  AFFECTATIONS DES EMPLOYÉS AUX PROJETS');
        $this->style($sh, 'A1:I1', [
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => self::C_WHITE]],
            'fill'      => $this->solidFill('FF0B63CE'),
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sh->getRowDimension(1)->setRowHeight(30);

        $headers = ['ID Affectation', 'Projet', 'Employé', 'Rôle',
            'Allocation %', 'Début', 'Fin', 'Alloc. totale emp.', 'Risque'];
        $row = 2;
        foreach ($headers as $i => $h) {
            $col = $this->colLetter($i);
            $sh->setCellValue($col . $row, $h);
            $this->style($sh, $col . $row, [
                'font'      => ['bold' => true, 'size' => 10, 'color' => ['argb' => self::C_WHITE]],
                'fill'      => $this->solidFill('FF1E3A5F'),
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
                'borders'   => $this->allBorders(),
            ]);
        }
        $sh->getRowDimension($row)->setRowHeight(25);

        $projMap = [];
        foreach ($projects as $p) {
            $projMap[$p->getProjectId()] = $p->getName();
        }

        $row = 3;
        foreach ($assignments as $a) {
            $eid        = $a->getUserAccount()->getUserId();
            $totalEmp   = $this->totalAllocByEmployee[$eid] ?? 0;
            $risk       = $totalEmp > 100 ? '🔴 SURCHARGÉ' : ($totalEmp >= 80 ? '🟠 CHARGE ÉLEVÉE' : '🟢 NORMAL');
            $riskColor  = $totalEmp > 100 ? 'FFFEF2F2' : ($totalEmp >= 80 ? 'FFFFF7ED' : 'FFF0FDF4');
            $bg         = $row % 2 === 0 ? 'FFFAFAFA' : 'FFFFFFFF';

            $values = [
                $a->getIdAssignment(),
                $projMap[$a->getProject()->getProjectId()] ?? '?',
                $this->employeeNameMap[$eid] ?? "Emp #$eid",
                $a->getRole(),
                $a->getAllocationRate(),
                $a->getAssignedFrom() ? $a->getAssignedFrom()->format('d/m/Y') : '',
                $a->getAssignedTo() ? $a->getAssignedTo()->format('d/m/Y') : '',
                $totalEmp . '%',
                $risk,
            ];

            foreach ($values as $i => $v) {
                $col = $this->colLetter($i);
                $sh->setCellValue($col . $row, $v);
                $cellBg = ($i === 8) ? $riskColor : $bg;
                $this->style($sh, $col . $row, [
                    'fill'    => $this->solidFill($cellBg),
                    'borders' => $this->allBorders(),
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);
            }
            $row++;
        }

        if ($row > 3) {
            $cond = new Conditional();
            $cond->setConditionType(Conditional::CONDITION_CELLIS)
                ->setOperatorType(Conditional::OPERATOR_GREATERTHAN)
                ->addCondition('100');
            $cond->getStyle()->getFont()->getColor()->setARGB('FFDC2626');
            $cond->getStyle()->getFont()->setBold(true);
            $sh->getStyle('E3:E' . ($row - 1))->setConditionalStyles([$cond]);
        }

        foreach (range(0, count($headers) - 1) as $i) {
            $sh->getColumnDimension($this->colLetter($i))->setAutoSize(true);
        }
    }

    /**
     * @param Project[]           $projects
     * @param Projectassignment[] $assignments
     */
    private function sheetStats(Spreadsheet $wb, array $projects, array $assignments): void
    {
        $sh = $wb->createSheet();
        $sh->setTitle('📈 Statistiques');
        $sh->getTabColor()->setARGB('FFF59E0B');

        $sh->mergeCells('A1:D1');
        $sh->setCellValue('A1', '📈  STATISTIQUES & ANALYSES');
        $this->style($sh, 'A1:D1', [
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => self::C_WHITE]],
            'fill'      => $this->solidFill('FFF59E0B'),
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sh->getRowDimension(1)->setRowHeight(30);

        $row = 3;

        $row = $this->statsSection($sh, $row, '📌 Projets par Statut', ['Statut', 'Nb', '%'],
            function () use ($projects): array {
                $sc = array_count_values(array_map(fn($p) => $p->getStatus(), $projects));
                $data = [];
                foreach ($sc as $s => $n) {
                    $data[] = [$s, $n, count($projects) > 0 ? round($n * 100 / count($projects), 1) . '%' : '0%'];
                }
                return $data;
            }, 'FF0B63CE');

        $row++;

        $row = $this->statsSection($sh, $row, '💰 Budget Total par Statut (TND)', ['Statut', 'Budget total', 'Budget moy.'],
            function () use ($projects): array {
                $budgets = [];
                foreach ($projects as $p) {
                    $budgets[$p->getStatus()][] = $p->getBudget();
                }
                $data = [];
                foreach ($budgets as $s => $vals) {
                    $data[] = [$s, number_format(array_sum($vals), 2), number_format(array_sum($vals) / count($vals), 2)];
                }
                return $data;
            }, 'FF0FA36B');

        $row++;

        $row = $this->statsSection($sh, $row, '📊 Tranches d\'Allocation Employés', ['Tranche', 'Nb employés', '%'],
            function () use ($assignments): array {
                $bands = ['🟢 0–50%' => 0, '🟡 51–80%' => 0, '🟠 81–100%' => 0, '🔴 >100%' => 0];
                foreach ($assignments as $a) {
                    $r = $a->getAllocationRate();
                    if ($r <= 50)       $bands['🟢 0–50%']++;
                    elseif ($r <= 80)   $bands['🟡 51–80%']++;
                    elseif ($r <= 100)  $bands['🟠 81–100%']++;
                    else                $bands['🔴 >100%']++;
                }
                $total = array_sum($bands);
                $data = [];
                foreach ($bands as $b => $n) {
                    $data[] = [$b, $n, $total > 0 ? round($n * 100 / $total, 1) . '%' : '0%'];
                }
                return $data;
            }, 'FFF59E0B');

        $row++;

        $row = $this->statsSection($sh, $row, '📅 Projets par Mois de Début', ['Mois', 'Nb projets'],
            function () use ($projects): array {
                $months = [];
                foreach ($projects as $p) {
                    if ($p->getStartDate()) {
                        $m = $p->getStartDate()->format('Y-m');
                        $months[$m] = ($months[$m] ?? 0) + 1;
                    }
                }
                ksort($months);
                $data = [];
                foreach ($months as $m => $n) {
                    $data[] = [$m, $n];
                }
                return $data;
            }, 'FF6B7280');

        foreach (range('A', 'D') as $c) {
            $sh->getColumnDimension($c)->setAutoSize(true);
        }

        $this->addBarChart($sh, 'chart_alloc',
            'Tranches d\'Allocation Employés',
            '📈 Statistiques', 3, 6,
            'E3', 'H20'
        );
    }

    /**
     * @param Project             $project
     * @param Projectassignment[] $assignments
     */
    private function sheetSingleProject(Spreadsheet $wb, $project, array $assignments): void
    {
        $sh = $wb->createSheet();
        $sh->setTitle('📋 Projet');
        $sh->getTabColor()->setARGB('FF0B63CE');

        $sh->mergeCells('A1:H1');
        $sh->setCellValue('A1', '📋  RAPPORT DE PROJET : ' . strtoupper($project->getName()));
        $this->style($sh, 'A1:H1', [
            'font'      => ['bold' => true, 'size' => 16, 'color' => ['argb' => self::C_WHITE]],
            'fill'      => $this->solidFill('FF0B3D8C'),
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sh->getRowDimension(1)->setRowHeight(40);

        $sh->mergeCells('A2:H2');
        $sh->setCellValue('A2', 'Généré le ' . date('d/m/Y à H:i'));
        $this->style($sh, 'A2:H2', [
            'font'      => ['italic' => true, 'color' => ['argb' => self::C_GRAY]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $row = 4;
        $sh->mergeCells("A{$row}:H{$row}");
        $sh->setCellValue("A{$row}", 'ℹ️  INFORMATIONS GÉNÉRALES');
        $this->style($sh, "A{$row}:H{$row}", [
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['argb' => self::C_WHITE]],
            'fill'      => $this->solidFill('FF0B63CE'),
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);
        $sh->getRowDimension($row)->setRowHeight(22);

        $nb        = count($assignments);
        $totAlloc  = array_sum(array_map(fn($a) => $a->getAllocationRate(), $assignments));
        $avgAlloc  = $nb > 0 ? round($totAlloc / $nb, 2) : 0;
        $duration  = 0;
        if ($project->getStartDate() && $project->getEndDate()) {
            $duration = $project->getStartDate()->diff($project->getEndDate())->days;
        }
        $bpe = $nb > 0 ? round($project->getBudget() / $nb, 2) : 0;
        $bpj = $duration > 0 ? round($project->getBudget() / $duration, 2) : 0;

        $infos = [
            ['ID Projet',              $project->getProjectId()],
            ['Nom',                    $project->getName()],
            ['Description',            $project->getDescription() ?? '—'],
            ['Date de début',          $project->getStartDate() ? $project->getStartDate()->format('d/m/Y') : '—'],
            ['Date de fin',            $project->getEndDate() ? $project->getEndDate()->format('d/m/Y') : '—'],
            ['Statut',                 $project->getStatus()],
            ['Budget (TND)',           number_format($project->getBudget(), 2)],
            ['Durée (jours)',          $duration],
            ['Nb. employés affectés',  $nb],
            ['Allocation totale %',    $totAlloc . '%'],
            ['Allocation moyenne %',   $avgAlloc . '%'],
            ['Budget par employé',     number_format($bpe, 2) . ' TND'],
            ['Budget par jour',        number_format($bpj, 2) . ' TND'],
        ];

        foreach ($infos as $i => $info) {
            $row++;
            $bg = $i % 2 === 0 ? 'FFF0F8FF' : 'FFFFFFFF';
            $sh->setCellValue("A{$row}", $info[0]);
            $sh->setCellValue("B{$row}", $info[1]);
            $sh->mergeCells("B{$row}:H{$row}");
            $this->style($sh, "A{$row}", [
                'font'    => ['bold' => true, 'size' => 10, 'color' => ['argb' => self::C_DARK]],
                'fill'    => $this->solidFill($bg),
                'borders' => $this->allBorders(),
            ]);
            $this->style($sh, "B{$row}:H{$row}", [
                'fill'    => $this->solidFill($bg),
                'borders' => $this->allBorders(),
            ]);
        }

        $row += 2;
        $sh->mergeCells("A{$row}:H{$row}");
        $sh->setCellValue("A{$row}", '👥  AFFECTATIONS DES EMPLOYÉS');
        $this->style($sh, "A{$row}:H{$row}", [
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['argb' => self::C_WHITE]],
            'fill'      => $this->solidFill('FF0FA36B'),
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);
        $sh->getRowDimension($row)->setRowHeight(22);

        $row++;
        $hdrs = ['ID', 'Employé', 'Rôle', 'Alloc. %', 'Début', 'Fin', 'Alloc. totale', 'Risque'];
        foreach ($hdrs as $i => $h) {
            $col = $this->colLetter($i);
            $sh->setCellValue($col . $row, $h);
            $this->style($sh, $col . $row, [
                'font'    => ['bold' => true, 'size' => 10, 'color' => ['argb' => self::C_WHITE]],
                'fill'    => $this->solidFill('FF1E3A5F'),
                'borders' => $this->allBorders(),
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }
        $sh->getRowDimension($row)->setRowHeight(22);
        $allocStartRow = $row + 1;

        foreach ($assignments as $a) {
            $row++;
            $eid       = $a->getUserAccount()->getUserId();
            $totEmp    = $this->totalAllocByEmployee[$eid] ?? 0;
            $risk      = $totEmp > 100 ? '🔴 SURCHARGÉ' : ($totEmp >= 80 ? '🟠 CHARGE ÉLEVÉE' : '🟢 NORMAL');
            $riskBg    = $totEmp > 100 ? 'FFFEF2F2' : ($totEmp >= 80 ? 'FFFFF7ED' : 'FFF0FDF4');
            $bg        = $row % 2 === 0 ? 'FFFAFAFA' : 'FFFFFFFF';

            $vals = [
                $a->getIdAssignment(),
                $this->employeeNameMap[$eid] ?? "Emp #$eid",
                $a->getRole(),
                $a->getAllocationRate(),
                $a->getAssignedFrom() ? $a->getAssignedFrom()->format('d/m/Y') : '',
                $a->getAssignedTo() ? $a->getAssignedTo()->format('d/m/Y') : '',
                $totEmp . '%',
                $risk,
            ];

            foreach ($vals as $i => $v) {
                $col = $this->colLetter($i);
                $sh->setCellValue($col . $row, $v);
                $cellBg = ($i === 7) ? $riskBg : $bg;
                $this->style($sh, $col . $row, [
                    'fill'    => $this->solidFill($cellBg),
                    'borders' => $this->allBorders(),
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);
            }
        }

        if ($row >= $allocStartRow) {
            $cond = new Conditional();
            $cond->setConditionType(Conditional::CONDITION_CELLIS)
                ->setOperatorType(Conditional::OPERATOR_GREATERTHAN)
                ->addCondition('100');
            $cond->getStyle()->getFont()->getColor()->setARGB('FFDC2626');
            $cond->getStyle()->getFont()->setBold(true);
            $sh->getStyle("D{$allocStartRow}:D{$row}")->setConditionalStyles([$cond]);
        }

        foreach (range(0, 7) as $i) {
            $sh->getColumnDimension($this->colLetter($i))->setAutoSize(true);
        }
    }

    /**
     * @param Projectassignment[] $assignments
     */
    private function sheetSingleStats(Spreadsheet $wb, array $assignments): void
    {
        $sh = $wb->createSheet();
        $sh->setTitle('📈 Statistiques');
        $sh->getTabColor()->setARGB('FFF59E0B');

        $sh->mergeCells('A1:C1');
        $sh->setCellValue('A1', '📈  STATISTIQUES DU PROJET');
        $this->style($sh, 'A1:C1', [
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['argb' => self::C_WHITE]],
            'fill'      => $this->solidFill('FFF59E0B'),
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $row = 3;

        $row = $this->statsSection($sh, $row, '👔 Employés par Rôle', ['Rôle', 'Nb'],
            function () use ($assignments): array {
                $roles = [];
                foreach ($assignments as $a) {
                    $roles[$a->getRole()] = ($roles[$a->getRole()] ?? 0) + 1;
                }
                $data = [];
                foreach ($roles as $r => $n) {
                    $data[] = [$r, $n];
                }
                return $data;
            }, 'FF0B63CE');

        $row++;

        $row = $this->statsSection($sh, $row, '📊 Tranches d\'Allocation', ['Tranche', 'Nb', '%'],
            function () use ($assignments): array {
                $bands = ['🟢 0–50%' => 0, '🟡 51–80%' => 0, '🟠 81–100%' => 0, '🔴 >100%' => 0];
                foreach ($assignments as $a) {
                    $r = $a->getAllocationRate();
                    if ($r <= 50)      $bands['🟢 0–50%']++;
                    elseif ($r <= 80)  $bands['🟡 51–80%']++;
                    elseif ($r <= 100) $bands['🟠 81–100%']++;
                    else               $bands['🔴 >100%']++;
                }
                $total = array_sum($bands);
                $data = [];
                foreach ($bands as $b => $n) {
                    $data[] = [$b, $n, $total > 0 ? round($n * 100 / $total, 1) . '%' : '0%'];
                }
                return $data;
            }, 'FF0FA36B');

        foreach (range('A', 'D') as $c) {
            $sh->getColumnDimension($c)->setAutoSize(true);
        }
    }

    /**
     * @param Worksheet $sh
     * @param string[]  $headers
     * @return int
     */
    private function statsSection(Worksheet $sh, int $row, string $title, array $headers, callable $dataFn, string $colorHex): int
    {
        $nbCols = count($headers);
        $lastCol = $this->colLetter($nbCols - 1);

        $sh->mergeCells("A{$row}:{$lastCol}{$row}");
        $sh->setCellValue("A{$row}", $title);
        $this->style($sh, "A{$row}:{$lastCol}{$row}", [
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['argb' => self::C_WHITE]],
            'fill'      => $this->solidFill($colorHex),
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);
        $sh->getRowDimension($row)->setRowHeight(20);
        $row++;

        foreach ($headers as $i => $h) {
            $col = $this->colLetter($i);
            $sh->setCellValue($col . $row, $h);
            $this->style($sh, $col . $row, [
                'font'    => ['bold' => true, 'size' => 10, 'color' => ['argb' => self::C_WHITE]],
                'fill'    => $this->solidFill('FF1E3A5F'),
                'borders' => $this->allBorders(),
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }
        $row++;

        $data = $dataFn();
        foreach ($data as $ri => $rowData) {
            $bg = $ri % 2 === 0 ? 'FFFAFAFA' : 'FFFFFFFF';
            foreach ($rowData as $i => $v) {
                $col = $this->colLetter($i);
                $sh->setCellValue($col . $row, $v);
                $this->style($sh, $col . $row, [
                    'fill'    => $this->solidFill($bg),
                    'borders' => $this->allBorders(),
                ]);
            }
            $row++;
        }

        return $row;
    }

    private function addPieChart(Worksheet $sheet, string $name, string $title, string $sheetTitle, int $start, int $end, string $tl, string $br): void
    {
        $labels = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$A\${$start}:\$A\${$end}", null, $end - $start + 1)];
        $values = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$B\${$start}:\$B\${$end}", null, $end - $start + 1)];

        $series  = new DataSeries(DataSeries::TYPE_PIECHART, null, range(0, 0), [], $labels, $values);
        $layout  = new Layout();
        $layout->setShowPercent(true);
        $layout->setShowCatName(true);
        $chart = new Chart($name, new Title($title), new Legend(Legend::POSITION_BOTTOM), new PlotArea($layout, [$series]));
        $chart->setTopLeftPosition($tl);
        $chart->setBottomRightPosition($br);
        $sheet->addChart($chart);
    }

    private function addBarChart(Worksheet $sheet, string $name, string $title, string $sheetTitle, int $start, int $end, string $tl, string $br): void
    {
        $labels = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!\$A\${$start}:\$A\${$end}", null, $end - $start + 1)];
        $values = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!\$B\${$start}:\$B\${$end}", null, $end - $start + 1)];

        $series = new DataSeries(DataSeries::TYPE_BARCHART, DataSeries::GROUPING_CLUSTERED, range(0, 0), [], $labels, $values);
        $chart  = new Chart($name, new Title($title), new Legend(Legend::POSITION_BOTTOM), new PlotArea(null, [$series]));
        $chart->setTopLeftPosition($tl);
        $chart->setBottomRightPosition($br);
        $sheet->addChart($chart);
    }

    /**
     * @param Worksheet $sheet
     * @param array<string, mixed> $styles
     */
    private function style(Worksheet $sheet, string $range, array $styles): void
    {
        $sheet->getStyle($range)->applyFromArray($styles);
    }

    /** @return array{fillType: string, startColor: array{argb: string}} */
    private function solidFill(string $argb): array
    {
        return ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $argb]];
    }

    /** @return array{allBorders: array{borderStyle: string, color: array{argb: string}}} */
    private function allBorders(): array
    {
        return ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE2E8F0']]];
    }

    private function colLetter(int $index): string
    {
        $letters = range('A', 'Z');
        if ($index < 26) return $letters[$index];
        return $letters[intdiv($index, 26) - 1] . $letters[$index % 26];
    }

    private function stream(Spreadsheet $wb, string $filename): StreamedResponse
    {
        $writer = new Xlsx($wb);
        $writer->setIncludeCharts(true);

        $response = new StreamedResponse(function () use ($writer): void {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');
        $response->headers->set('Pragma', 'public');

        return $response;
    }
}