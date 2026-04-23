<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;

class PdfExportService
{
    private const PRIMARY_COLOR = '#0B63CE';
    private const SECONDARY_COLOR = '#0FA36B';
    private const BACKGROUND_COLOR = '#f8fafc';

    public function generateProjectPdfResponse(string $html, string $filename): Response
    {
        // Compatibilité avec Dompdf 1.x et 2.x
        if (class_exists(Options::class)) {
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'Arial');
            $dompdf = new Dompdf($options);
        } else {
            $dompdf = new Dompdf();
        }

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',  // ← MODIFIÉ ICI
            ]
        );
    }

    public function renderAllProjectsHtml(array $projects, array $assignmentsByProject, ?string $logoPath = null): string
    {
        $logoHtml = $logoPath ? '<img src="' . $logoPath . '" style="height:40px;">' : '<h2 style="color:' . self::PRIMARY_COLOR . ';">INTEGRA</h2>';

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
            body { font-family: Arial, sans-serif; margin: 20px; color: #1e293b; }
            .header { text-align: center; margin-bottom: 20px; }
            h1 { color: ' . self::PRIMARY_COLOR . '; font-size: 24px; }
            h2 { color: ' . self::PRIMARY_COLOR . '; font-size: 18px; margin-top: 25px; border-bottom: 2px solid ' . self::PRIMARY_COLOR . '; padding-bottom: 5px; }
            h3 { color: ' . self::SECONDARY_COLOR . '; font-size: 16px; margin-top: 20px; }
            table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            th { background-color: ' . self::PRIMARY_COLOR . '; color: white; padding: 8px; text-align: left; }
            td { padding: 8px; border-bottom: 1px solid #e2e8f0; }
            .project-details { background-color: ' . self::BACKGROUND_COLOR . '; padding: 15px; border-radius: 8px; }
            .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #64748b; }
        </style></head><body>';

        $html .= '<div class="header">' . $logoHtml . '<h1>Rapport complet des projets</h1></div>';
        $html .= '<h2>Liste des projets</h2>';
        $html .= $this->buildProjectsTable($projects);

        foreach ($projects as $project) {
            $html .= '<h3>Projet : ' . htmlspecialchars($project->getName()) . '</h3>';
            $assignments = $assignmentsByProject[$project->getProjectId()] ?? [];
            if (empty($assignments)) {
                $html .= '<p><em>Aucune affectation pour ce projet.</em></p>';
            } else {
                $html .= $this->buildAssignmentsTable($assignments);
            }
        }

        $html .= '<div class="footer">INTEGRA HR Management System © ' . date('Y') . ' - Généré le ' . date('d/m/Y H:i') . '</div>';
        $html .= '</body></html>';

        return $html;
    }

    public function renderSingleProjectHtml($project, array $assignments, ?string $logoPath = null): string
    {
        $logoHtml = $logoPath ? '<img src="' . $logoPath . '" style="height:40px;">' : '<h2 style="color:' . self::PRIMARY_COLOR . ';">INTEGRA</h2>';

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
            body { font-family: Arial, sans-serif; margin: 20px; color: #1e293b; }
            .header { text-align: center; margin-bottom: 20px; }
            h1 { color: ' . self::PRIMARY_COLOR . '; font-size: 24px; }
            h2 { color: ' . self::PRIMARY_COLOR . '; font-size: 18px; margin-top: 25px; border-bottom: 2px solid ' . self::PRIMARY_COLOR . '; padding-bottom: 5px; }
            table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            th { background-color: ' . self::SECONDARY_COLOR . '; color: white; padding: 8px; text-align: left; }
            td { padding: 8px; border-bottom: 1px solid #e2e8f0; }
            .detail-table td:first-child { background-color: ' . self::BACKGROUND_COLOR . '; font-weight: bold; width: 30%; }
            .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #64748b; }
        </style></head><body>';

        $html .= '<div class="header">' . $logoHtml . '<h1>Détail du projet</h1></div>';
        $html .= '<h2>Informations générales</h2>';
        $html .= $this->buildProjectDetailsTable($project);
        $html .= '<h2>Affectations</h2>';
        if (empty($assignments)) {
            $html .= '<p><em>Aucune affectation pour ce projet.</em></p>';
        } else {
            $html .= $this->buildAssignmentsTable($assignments);
        }
        $html .= '<div class="footer">INTEGRA HR Management System © ' . date('Y') . ' - Généré le ' . date('d/m/Y H:i') . '</div>';
        $html .= '</body></html>';

        return $html;
    }

    private function buildProjectsTable(array $projects): string
    {
        $html = '<table><thead><tr><th>ID</th><th>Nom</th><th>Début</th><th>Fin</th><th>Statut</th><th>Budget (TND)</th></tr></thead><tbody>';
        foreach ($projects as $p) {
            $html .= '<tr>';
            $html .= '<td>' . $p->getProjectId() . '</td>';
            $html .= '<td>' . htmlspecialchars($p->getName()) . '</td>';
            $html .= '<td>' . ($p->getStartDate() ? $p->getStartDate()->format('Y-m-d') : '—') . '</td>';
            $html .= '<td>' . ($p->getEndDate() ? $p->getEndDate()->format('Y-m-d') : '—') . '</td>';
            $html .= '<td>' . htmlspecialchars($p->getStatus()) . '</td>';
            $html .= '<td>' . number_format($p->getBudget(), 2) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }

    private function buildAssignmentsTable(array $assignments): string
    {
        $html = '<table><thead><tr><th>ID</th><th>Employé</th><th>Rôle</th><th>Allocation</th><th>Début</th><th>Fin</th></tr></thead><tbody>';
        foreach ($assignments as $a) {
            $html .= '<tr>';
            $html .= '<td>' . $a->getIdAssignment() . '</td>';
            $html .= '<td>' . htmlspecialchars($a->getUserAccount() ? $a->getUserAccount()->getUsername() : '—') . '</td>';
            $html .= '<td>' . htmlspecialchars($a->getRole()) . '</td>';
            $html .= '<td>' . $a->getAllocationRate() . '%</td>';
            $html .= '<td>' . ($a->getAssignedFrom() ? $a->getAssignedFrom()->format('Y-m-d') : '—') . '</td>';
            $html .= '<td>' . ($a->getAssignedTo() ? $a->getAssignedTo()->format('Y-m-d') : '—') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }

    private function buildProjectDetailsTable($project): string
    {
        $html = '<table class="detail-table">';
        $html .= '<tr><td>ID du projet</td><td>' . $project->getProjectId() . '</td></tr>';
        $html .= '<tr><td>Nom</td><td>' . htmlspecialchars($project->getName()) . '</td></tr>';
        $html .= '<tr><td>Description</td><td>' . nl2br(htmlspecialchars($project->getDescription() ?? '—')) . '</td></tr>';
        $html .= '<tr><td>Date de début</td><td>' . ($project->getStartDate() ? $project->getStartDate()->format('Y-m-d') : '—') . '</td></tr>';
        $html .= '<tr><td>Date de fin</td><td>' . ($project->getEndDate() ? $project->getEndDate()->format('Y-m-d') : '—') . '</td></tr>';
        $html .= '<tr><td>Statut</td><td>' . htmlspecialchars($project->getStatus()) . '</td></tr>';
        $html .= '<tr><td>Budget (TND)</td><td>' . number_format($project->getBudget(), 2) . '</td></tr>';
        $html .= '</table>';
        return $html;
    }
}