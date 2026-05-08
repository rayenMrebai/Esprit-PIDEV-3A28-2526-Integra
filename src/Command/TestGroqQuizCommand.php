<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\AIQuizGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestGroqQuizCommand extends Command
{
    protected static $defaultName = 'test:groq-quiz';

    public function __construct(
        private AIQuizGenerator $quizGenerator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $title = 'Symfony Framework';
        $description = 'Formation sur Symfony 6, les contrôleurs, les services, Doctrine ORM et Twig';

        $output->writeln("Génération d'un quiz pour : $title");

        /** @var array<int, array{question: string, options: array<int, string>, correct: int}> $quiz */
        $quiz = $this->quizGenerator->generateQuiz($title, $description);

        $output->writeln("Nombre de questions générées : " . count($quiz));

        foreach ($quiz as $index => $question) {
            $questionNumber = (int) $index + 1;
            $output->writeln("\n" . $questionNumber . ". " . $question['question']);
            foreach ($question['options'] as $optIndex => $option) {
                $marker = $optIndex === $question['correct'] ? '✓' : ' ';
                $output->writeln("   [$marker] " . $option);
            }
        }

        return Command::SUCCESS;
    }
}