<?php

namespace App\Command;

use App\Repository\OrderRepository;
use App\Repository\PackageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCommand(
    name: 'app:cleanup:expired-data',
    description: 'Deletes orders and packages older than 30 days',
)]
#[AsCronTask('0 3 * * *')]
class CleanupExpiredDataCommand extends Command
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly PackageRepository $packageRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $threshold = new \DateTimeImmutable('-30 days');

        $oldOrders = $this->orderRepository->createQueryBuilder('o')
            ->where('o.created_at < :threshold')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();

        foreach ($oldOrders as $order) {
            $this->entityManager->remove($order);
        }

        $oldPackages = $this->packageRepository->createQueryBuilder('p')
            ->where('p.created_at < :threshold')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();

        foreach ($oldPackages as $package) {
            $this->entityManager->remove($package);
        }

        $this->entityManager->flush();

        $io->success(sprintf('Deleted %d orders and %d packages older than 30 days.', count($oldOrders), count($oldPackages)));

        return Command::SUCCESS;
    }
}
