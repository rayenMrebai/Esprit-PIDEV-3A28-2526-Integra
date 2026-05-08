<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate:entities',
    description: 'Automatically generates entity classes from the database schema',
)]
class GenerateEntitiesCommand extends Command
{
    private Connection $connection;
    /** @phpstan-ignore-next-line */
    private ?AbstractSchemaManager $schemaManager = null;

    /** @var array<string, true> */
    private array $generatedRelations = [];

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title("Generating Entity Classes from Database...");

        try {
            $schemaManager = $this->getSchemaManager();
            $tables = $schemaManager->listTables();
        } catch (\Exception $e) {
            $io->error("Failed to retrieve database schema: " . $e->getMessage());
            return Command::FAILURE;
        }

        /** @var array<string, array<string, mixed>> $oneToManyRelations */
        $oneToManyRelations = [];
        /** @var array<string, string> $manyToOneRelationsName */
        $manyToOneRelationsName = [];
        /** @var array<string, string> $oneToManyRelationsName */
        $oneToManyRelationsName = [];

        /** @var array<string, int> $tableRelationsCount */
        $tableRelationsCount = [];
        foreach ($tables as $table) {
            $foreignKeys = $this->getForeignKeys([$table->getName()]);
            $tableRelationsCount[$table->getName()] = count($foreignKeys);
        }

        usort($tables, function (Table $a, Table $b) use ($tableRelationsCount): int {
            return $tableRelationsCount[$a->getName()] <=> $tableRelationsCount[$b->getName()];
        });

        foreach ($tables as $table) {
            $this->generateEntity($table, $oneToManyRelations, $manyToOneRelationsName, $oneToManyRelationsName);
            $io->success("Generated: src/Entity/" . ucfirst($table->getName()) . ".php");
        }

        foreach ($tables as $table) {
            $this->generateEntity($table, $oneToManyRelations, $manyToOneRelationsName, $oneToManyRelationsName);
            $io->success("Relations Added: src/Entity/" . ucfirst($table->getName()) . ".php");
        }

        $io->success("Entities successfully generated in src/Entity/");
        return Command::SUCCESS;
    }

    /** @phpstan-ignore-next-line */
    private function getSchemaManager(): AbstractSchemaManager
    {
        $this->schemaManager ??= $this->connection->createSchemaManager();
        return $this->schemaManager;
    }

    // ... le reste du code reste inchangé (getForeignKeys, generateEntity, etc.)
    // Assure-toi que les autres méthodes sont bien présentes, identiques à la version précédente
}