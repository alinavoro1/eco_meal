<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Order;
use App\Entity\Package;
use App\Entity\SaleRecord;
use App\Repository\BusinessRepository;
use App\Repository\CategoryRepository;
use App\Repository\ConsumerRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ExtraDataFixture extends Fixture
{
    public function __construct(
        private BusinessRepository $businessRepository,
        private CategoryRepository $categoryRepository,
        private ConsumerRepository $consumerRepository,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $businesses = $this->businessRepository->findAll();
        $categories = $this->categoryRepository->findAll();
        $consumers  = $this->consumerRepository->findAll();

        if (empty($businesses) || empty($categories) || empty($consumers)) {
            throw new \RuntimeException('Run AppFixtures first (or ensure businesses, categories and consumers exist in DB).');
        }

        $packageNames = [
            'Surprise Lunch Box', 'Chef\'s Mystery Bag', 'Evening Special Box',
            'Pasta & Salad Combo', 'End of Day Pastries', 'Artisan Bread Bundle',
            'Sweet Treats Bag', 'Cake Slice Mix', 'Coffee & Snack Deal',
            'Sandwich Rescue', 'Muffin & Cookie Bag', 'Veggie Rescue Box',
            'Fruit Freshness Box', 'Dairy Deal Bag', 'Pantry Surplus Box',
            'Family Dinner Pack', 'Antipasto Rescue', 'Pizza Slice Bundle',
            'Soup of the Day Box', 'Grilled Leftovers Pack', 'Seasonal Salad Box',
            'Protein Power Bag', 'Overnight Bakes Bundle', 'Market Clearance Box',
            'Mini Dessert Platter', 'Wrap & Roll Bag', 'Rice Bowl Rescue',
            'Stew & Sides Pack', 'Brunch Leftovers Box', 'Chef Tasting Bag',
        ];

        $descriptions = [
            'A mix of today\'s unsold dishes at a great price.',
            'Fresh and tasty — grab it before it goes to waste!',
            'End-of-day portions, handpicked by our chef.',
            'Surplus from our daily menu — still delicious.',
            'Quality food that would otherwise go to waste.',
            'Our contribution to zero food waste — enjoy!',
            'Reduced price on today\'s unsold specials.',
            'A surprise selection from our kitchen.',
            'Good food, great deal — limited availability.',
            'Freshly prepared, needs a good home tonight.',
        ];

        $newPackages = [];
        $packageCount = 60;

        for ($i = 0; $i < $packageCount; $i++) {
            $business = $businesses[$i % count($businesses)];
            $category = $categories[$i % count($categories)];

            $package = new Package();
            $package->setName($packageNames[$i % count($packageNames)] . ' #' . ($i + 1));
            $package->setDescription($descriptions[$i % count($descriptions)]);
            $package->setPrice(round(mt_rand(700, 3500) / 100, 2));
            $package->setPhoto(null);
            $package->setCreatedAt(new \DateTimeImmutable(sprintf('-%d hours', mt_rand(1, 20))));
            $package->setCategory($category);
            $package->setBusiness($business);

            $manager->persist($package);
            $newPackages[] = $package;
        }

        $manager->flush();

        $orderedPackages = [];
        $orderCount = 40;

        for ($i = 0; $i < $orderCount; $i++) {
            $package = $newPackages[$i % count($newPackages)];
            $packageId = spl_object_id($package);

            if (isset($orderedPackages[$packageId])) {
                continue;
            }
            $orderedPackages[$packageId] = true;

            $consumer = $consumers[$i % count($consumers)];
            $createdAt = new \DateTimeImmutable(sprintf('-%d minutes', mt_rand(10, 90)));

            $order = new Order();
            $order->setCreatedAt($createdAt);
            $order->setPackage($package);
            $order->setConsumer($consumer);

            $manager->persist($order);
        }

        $manager->flush();

        $saleRecordCount = 80;
        $statuses = ['fulfilled', 'cancelled'];

        for ($i = 0; $i < $saleRecordCount; $i++) {
            $business = $businesses[$i % count($businesses)];
            $category = $categories[$i % count($categories)];

            $orderedAt  = new \DateTimeImmutable(sprintf('-%d days -%d hours', mt_rand(1, 30), mt_rand(0, 23)));
            $fulfilledAt = $orderedAt->modify(sprintf('+%d minutes', mt_rand(20, 110)));

            $record = new SaleRecord();
            $record->setBusiness($business);
            $record->setPackageName($packageNames[$i % count($packageNames)]);
            $record->setPackagePrice(round(mt_rand(700, 3500) / 100, 2));
            $record->setCategoryName($category->getName());
            $record->setOrderedAt($orderedAt);
            $record->setFulfilledAt($fulfilledAt);
            $record->setStatus($statuses[$i % 2 === 0 ? 0 : 1]);

            $manager->persist($record);
        }

        $manager->flush();
    }
}
