<?php

namespace App\Command;

use App\Repository\PackageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCommand(
    name: 'app:packages:remove-discount',
    description: 'Removes the Happy Hour discount and restores original prices',
)]
#[AsCronTask('0 22 * * *')]
class PackagesRemoveDiscountCommand extends Command
{
    public function __construct(private readonly PackageRepository $packageRepository, private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $packages = $this->packageRepository->createQueryBuilder('p')
            ->where('p.discountPercentage IS NOT NULL')
            ->getQuery()
            ->getResult();

        foreach ($packages as $package) {
            if ($package->getOriginalPrice() !== null) {
                $package->setPrice($package->getOriginalPrice());
                $package->setOriginalPrice(null);
                $package->setDiscountPercentage(null);
            }
        }

        $this->entityManager->flush();

        $io->success('Restored original prices for all packages.');

        return Command::SUCCESS;
    }
}
