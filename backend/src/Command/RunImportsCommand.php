<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\ImportTemplate;
use App\Repository\ImportTemplateRepository;
use App\Service\ImportRunner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:imports:run',
    description: 'Run an import by id, or all scheduled imports that are due (for cron).',
)]
class RunImportsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ImportTemplateRepository $templates,
        private readonly ImportRunner $runner,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Run a single import template by id')
            ->addOption('due', null, InputOption::VALUE_NONE, 'Run all scheduled imports that are due');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new \DateTimeImmutable();

        if ($input->getOption('id') !== null) {
            $template = $this->templates->find((int) $input->getOption('id'));
            if ($template === null) {
                $io->error('Import template not found.');

                return Command::FAILURE;
            }
            $templates = [$template];
        } elseif ($input->getOption('due')) {
            $templates = $this->templates->findDue($now);
            if ($templates === []) {
                $io->writeln('No scheduled imports are due.');

                return Command::SUCCESS;
            }
        } else {
            $io->error('Pass --id=<id> to run one import, or --due to run scheduled imports.');

            return Command::FAILURE;
        }

        $failures = 0;
        foreach ($templates as $template) {
            $label = sprintf('#%d %s', $template->getId(), $template->getName());

            if ($template->getSupplierRef() === null) {
                $io->warning($label.': skipped (no supplier assigned).');
                ++$failures;
                continue;
            }

            $result = $this->runner->run($template, false);
            if (isset($result['error'])) {
                $io->warning($label.': '.$result['error']);
                ++$failures;
            } else {
                $io->writeln(sprintf(
                    '%s: %s — %d created, %d updated, %d matched, %d failed.',
                    $label, $result['status'], $result['created'], $result['updated'], $result['matched'], $result['failed'],
                ));
            }

            $this->stampSchedule($template, $now);
        }

        $this->em->flush();

        return $failures > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function stampSchedule(ImportTemplate $template, \DateTimeImmutable $now): void
    {
        $template->setLastRunAt($now);
        $interval = $template->intervalForSchedule();
        $template->setNextRunAt($interval !== null ? $now->add($interval) : null);
    }
}
