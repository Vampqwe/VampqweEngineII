<?php

declare(strict_types=1);

namespace Vampqwe\Engine\Database;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class MigrateCommand extends Command
{
    public function __construct(
        private readonly MigrationRunner $runner,
        private readonly string $directory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('db:migrate')
            ->setDescription('Apply pending database migrations.')
            ->addOption('rollback', 'r', InputOption::VALUE_NONE, 'Roll back the latest migration batch.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rollback = $input->getOption('rollback');
        $changed = $rollback
            ? $this->runner->rollbackLastBatch($this->directory)
            : $this->runner->migrate($this->directory);

        if ($changed === []) {
            $output->writeln('<comment>No migrations to process.</comment>');

            return Command::SUCCESS;
        }

        $verb = $rollback ? 'Rolled back' : 'Applied';
        foreach ($changed as $migration) {
            $output->writeln(sprintf('<info>%s %s</info>', $verb, $migration));
        }

        return Command::SUCCESS;
    }
}