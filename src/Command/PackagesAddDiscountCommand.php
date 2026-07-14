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
    name: 'app:packages:add-discount',
    description: 'Applies a 25% discount to all active packages for Happy Hour',
)]
#[AsCronTask('0 20 * * *')]
class PackagesAddDiscountCommand extends Command
{
    public function __construct(private readonly PackageRepository $packageRepository, private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $threshold = new \DateTimeImmutable('-24 hours');

        $packages = $this->packageRepository->createQueryBuilder('p')
            ->leftJoin('p.consumer_order', 'co')
            ->where('p.discountPercentage IS NULL')
            ->andWhere('p.created_at >= :threshold')
            ->andWhere('co.id IS NULL')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();

        foreach ($packages as $package) {
            $price = $package->getPrice();
            $package->setOriginalPrice($price);
            $package->setPrice($price * 0.75);
            $package->setDiscountPercentage(25);
        }

        $this->entityManager->flush();

        $io->success('Applied 25% discount to active packages.');

        return Command::SUCCESS;
    }
}
