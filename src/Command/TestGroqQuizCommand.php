<?php
// src/Command/TestGroqQuizCommand.php
namespace App\Command;

use App\Service\AIQuizGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestGroqQuizCommand extends Command
{
    protected static $defaultName = 'test:groq-quiz';
    private $quizGenerator;

    public function __construct(AIQuizGenerator $quizGenerator)
    {
        parent::__construct();
        $this->quizGenerator = $quizGenerator;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $title = 'Symfony Framework';
        $description = 'Formation sur Symfony 6, les contrôleurs, les services, Doctrine ORM et Twig';
        
        $output->writeln("Génération d'un quiz pour : $title");
        
        $quiz = $this->quizGenerator->generateQuiz($title, $description);
        
        $output->writeln("Nombre de questions générées : " . count($quiz));
        
        foreach ($quiz as $index => $question) {
            $output->writeln("\n" . ($index + 1) . ". " . $question['question']);
            foreach ($question['options'] as $optIndex => $option) {
                $marker = $optIndex === $question['correct'] ? '✓' : ' ';
                $output->writeln("   [$marker] " . $option);
            }
        }
        
        return Command::SUCCESS;
    }
}