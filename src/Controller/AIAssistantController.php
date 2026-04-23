<?php

namespace App\Controller;

use App\Entity\Project;
use App\Repository\ProjectassignmentRepository;
use App\Service\OllamaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\VoskService;

#[Route('/ai')]
class AIAssistantController extends AbstractController
{
    #[Route('/assistant/{projectId}', name: 'ai_assistant')]
    public function assistant(int $projectId, Project $project): Response
    {
        return $this->render('ai/assistant.html.twig', [
            'project' => $project,
        ]);
    }

    #[Route('/generate-summary/{projectId}', name: 'ai_generate_summary', methods: ['POST'])]
    public function generateSummary(
        int $projectId,
        Project $project,
        OllamaService $ollama,
        ProjectassignmentRepository $assignmentRepo
    ): JsonResponse {
        $assignments = $assignmentRepo->findBy(['project' => $project]);

        $nbEmployees = count($assignments);
        $roles       = [];
        $totalAlloc  = 0;
        $overloaded  = 0;

        foreach ($assignments as $a) {
            $roles[]     = $a->getRole();
            $totalAlloc += $a->getAllocationRate();
            if ($a->getAllocationRate() > 80) $overloaded++;
        }

        $uniqueRoles  = array_unique($roles);
        $avgAlloc     = $nbEmployees > 0 ? round($totalAlloc / $nbEmployees) : 0;
        $budgetPerEmp = $nbEmployees > 0
            ? number_format($project->getBudget() / $nbEmployees, 2)
            : 'N/A';

        $duration = '';
        if ($project->getStartDate() && $project->getEndDate()) {
            $days     = $project->getStartDate()->diff($project->getEndDate())->days;
            $duration = $days . ' jours (' . round($days / 30, 1) . ' mois)';
        }

        $context  = "Nom : "     . $project->getName()   . "\n";
        $context .= "Description : " . ($project->getDescription() ?: 'Non renseignée') . "\n";
        $context .= "Statut : "  . $project->getStatus() . "\n";
        $context .= "Début : "   . ($project->getStartDate()?->format('d/m/Y') ?? 'N/A') . "\n";
        $context .= "Fin : "     . ($project->getEndDate()?->format('d/m/Y')   ?? 'N/A') . "\n";
        $context .= "Durée : "   . ($duration ?: 'N/A')  . "\n";
        $context .= "Budget : "  . number_format($project->getBudget(), 2) . " TND\n";
        $context .= "Équipe : "  . $nbEmployees . " employé(s)\n";

        if ($nbEmployees > 0) {
            $context .= "Rôles : "               . implode(', ', $uniqueRoles) . "\n";
            $context .= "Allocation moyenne : "  . $avgAlloc     . "%\n";
            $context .= "Budget par employé : "  . $budgetPerEmp . " TND\n";
            if ($overloaded > 0) {
                $context .= "Employés à forte charge (>80%) : " . $overloaded . "\n";
            }
            $context .= "Détail des affectations :\n";
            foreach ($assignments as $a) {
                $context .= "  • " . ($a->getUserAccount()?->getUsername() ?? 'Inconnu')
                    . " — " . $a->getRole()
                    . " (" . $a->getAllocationRate() . "%)"
                    . ($a->getAssignedFrom() ? " du " . $a->getAssignedFrom()->format('d/m/Y') : '')
                    . ($a->getAssignedTo()   ? " au " . $a->getAssignedTo()->format('d/m/Y')   : '')
                    . "\n";
            }
        } else {
            $context .= "Aucune affectation enregistrée.\n";
        }

        $prompt = <<<PROMPT
Tu es un analyste RH senior. Rédige UN SEUL paragraphe analytique professionnel en français à partir des données suivantes.

Contraintes strictes :
- UN seul paragraphe fluide, entre 80 et 120 mots
- Commence directement par le contenu, sans titre ni introduction
- Intègre naturellement : nom, statut, durée, budget, équipe, rôles, allocation
- Termine par une appréciation concise de la situation RH du projet
- Aucun point de liste, aucun sous-titre, aucun commentaire hors paragraphe

Données du projet :
{$context}

Paragraphe analytique :
PROMPT;

        try {
            $summary = $ollama->generate($prompt);
            $summary = preg_replace('/^(Paragraphe analytique\s*:?\s*|Voici\s.*?:\s*)/i', '', trim($summary));
            return $this->json(['summary' => trim($summary)]);
        } catch (\Exception $e) {
            return $this->json(['summary' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }

    #[Route('/improve-description/{projectId}', name: 'ai_improve_description', methods: ['POST'])]
    public function improveDescription(
        int $projectId,
        Project $project,
        OllamaService $ollama
    ): JsonResponse {
        $currentDesc = trim($project->getDescription() ?: $project->getName());

        $prompt = <<<PROMPT
Tu es un expert en communication RH. Améliore la description de projet suivante en français.

Contraintes strictes :
- Rédige UN seul paragraphe amélioré, entre 60 et 100 mots
- Conserve le sens original mais enrichis le vocabulaire et la formulation
- Rends le texte plus professionnel, précis et engageant
- Ne commence pas par "Voici", "Bien sûr", ni aucune phrase d'introduction
- Pas de titre, pas de liste, uniquement le paragraphe amélioré

Description originale :
"{$currentDesc}"

Description améliorée :
PROMPT;

        try {
            $improved = $ollama->generate($prompt);
            $improved = preg_replace('/^(Description améliorée\s*:?\s*|Voici\s.*?:\s*|Bien sûr.*?:\s*)/i', '', trim($improved));
            return $this->json(['improved' => trim($improved)]);
        } catch (\Exception $e) {
            return $this->json(['improved' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }

    #[Route('/translate-text', name: 'ai_translate', methods: ['POST'])]
    public function translate(Request $request, OllamaService $ollama): JsonResponse
    {
        $data   = json_decode($request->getContent(), true);
        $text   = $data['text']   ?? $request->getPayload()->get('text', '');
        $target = $data['target'] ?? $request->getPayload()->get('target', 'fr');

        if (empty(trim($text))) {
            return $this->json(['translated' => '']);
        }

        if ($target === 'fr') {
            $prompt = "Translate the following text to French. Output ONLY the translated text. No explanations, no alternatives, no comments, no labels, nothing else.\n\n" . $text;
        } else {
            $prompt = "Translate the following text to English. Output ONLY the translated text. No explanations, no alternatives, no comments, no labels, nothing else.\n\n" . $text;
        }

        try {
            $result = $ollama->generate($prompt);

            $lines = explode("\n", trim($result));
            $clean = '';
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $clean = $line;
                    break;
                }
            }

            return $this->json(['translated' => $clean ?: trim($result)]);

        } catch (\Exception $e) {
            return $this->json(['translated' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }

    #[Route('/replace-description/{projectId}', name: 'ai_replace_description', methods: ['POST'])]
    public function replaceDescription(
        int $projectId,
        Project $project,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $newDesc = $request->request->get('description')
            ?? $request->getPayload()->get('description');

        if ($newDesc !== null) {
            $project->setDescription($newDesc);
            $em->flush();
            $this->addFlash('success', '✅ Description mise à jour par l\'IA.');
        }

        return $this->redirectToRoute('app_project_index');
    }

    #[Route('/transcribe-audio', name: 'ai_transcribe', methods: ['POST'])]
    public function transcribeAudio(Request $request, VoskService $vosk): JsonResponse
    {
        $file = $request->files->get('audio');
        if (!$file) {
            return $this->json(['text' => '', 'error' => 'Aucun fichier audio'], 400);
        }

        $tmpDir = $this->getParameter('kernel.project_dir') . '/var/';
        $tmpName = 'audio_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $tmpPath = $tmpDir . $tmpName;

        try {
            $file->move($tmpDir, $tmpName);
        } catch (\Exception $e) {
            return $this->json(['text' => '', 'error' => 'Erreur sauvegarde fichier'], 500);
        }

        $text = '';
        try {
            $text = $vosk->transcribeFile($tmpPath);
        } catch (\Exception $e) {
            $text = 'Erreur transcription : ' . $e->getMessage();
        } finally {
            if (file_exists($tmpPath)) {
                unlink($tmpPath);
            }
        }

        return $this->json(['text' => $text]);
    }
}