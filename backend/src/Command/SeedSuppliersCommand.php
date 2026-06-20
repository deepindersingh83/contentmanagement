<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Supplier;
use App\Repository\SupplierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-suppliers',
    description: 'Create 10 placeholder suppliers to get started (skips existing codes).',
)]
class SeedSuppliersCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SupplierRepository $suppliers,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $created = 0;

        for ($i = 1; $i <= 10; ++$i) {
            $code = 'supplier-'.$i;
            if ($this->suppliers->findOneByCode($code) !== null) {
                continue;
            }

            $supplier = new Supplier();
            $supplier->setName('Supplier '.$i);
            $supplier->setCode($code);
            $supplier->setActive(true);
            $supplier->setDefaultCurrency('AUD');
            $supplier->setDefaultWeightUnit('kg');

            $this->em->persist($supplier);
            ++$created;
        }

        $this->em->flush();
        $io->success(sprintf('Seeded %d supplier(s).', $created));

        return Command::SUCCESS;
    }
}
