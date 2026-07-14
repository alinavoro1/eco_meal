<?php

namespace App\Controller;

use App\Entity\Business;
use App\Entity\Package;
use App\Entity\User;
use App\Form\BusinessAccountFormType;
use App\Form\BusinessFormType;
use App\Form\PackageFormType;
use App\Repository\BusinessRepository;
use App\Repository\SaleRecordRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

use App\Dto\BusinessSearchFilter;
use App\Form\BusinessFiltersType;

final class BusinessController extends AbstractController
{
    #[Route('/business', name: 'app_business')]
    public function index(Request $request, BusinessRepository $businessRepository): Response
    {
        $filter = new BusinessSearchFilter();
        $form = $this->createForm(BusinessFiltersType::class, $filter, [
            'method' => 'GET',
            'cities' => $businessRepository->findUniqueCities(),
        ]);
        $form->handleRequest($request);

        return $this->render('business/index.html.twig', [
            'businesses' => $businessRepository->findByFilter($filter),
            'business_filter_form' => $form->createView(),
        ]);
    }

    #[Route('/business/new', name: 'app_business_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $business = new Business();

        $form = $this->createForm(BusinessFormType::class, $business, [
            'is_create' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = new User();
            $user->setEmail($form->get('email')->getData());
            $user->setRoles(['ROLE_BUSINESS']);
            $user->setPassword($passwordHasher->hashPassword($user, $form->get('password')->getData()));
            $user->setBusiness($business);

            $entityManager->persist($business);
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_business_view', ['id' => $business->getId()]);
        }
        return $this->render('business/new.html.twig', [
            'form' => $form,
        ]);
    }
    #[Route('/business/{id}/edit', name: 'app_business_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Business $business, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user || !$user->getBusiness() || $user->getBusiness()->getId() !== $business->getId()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(BusinessFormType::class, $business);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_business_view', ['id' => $business->getId()]);
        }
        return $this->render('business/edit.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/business/{id}/add_package', name: 'app_business_add_package', methods: ['GET', 'POST'])]
    public function addPackage(Request $request, Business $business, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user || !$user->getBusiness() || $user->getBusiness()->getId() !== $business->getId()) {
            throw $this->createAccessDeniedException();
        }

        $package = new Package();
        $form = $this->createForm(PackageFormType::class, $package);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $package->setBusiness($business);
            $entityManager->persist($package);
            $entityManager->flush();
            return $this->redirectToRoute('app_business_view', ['id' => $business->getId()]);
        }
        return $this->render('package/new.html.twig', [
            'form' => $form,
            'business' => $business,
        ]);

    }

    #[Route('/business/{id}/create_account', name: 'app_business_create_account', methods: ['GET', 'POST'])]
    public function createAccount(Request $request, Business $business, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($business->getUser()) {
            return $this->redirectToRoute('app_business_view', ['id' => $business->getId()]);
        }

        $user = new User();
        $form = $this->createForm(BusinessAccountFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setRoles(['ROLE_BUSINESS']);
            $user->setPassword($passwordHasher->hashPassword($user, $form->get('plainPassword')->getData()));
            $user->setBusiness($business);

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_business_view', ['id' => $business->getId()]);
        }

        return $this->render('business/create_account.html.twig', [
            'form' => $form,
            'business' => $business,
        ]);
    }

    #[Route('/business/{id}', name: 'app_business_view', methods: ['GET'])]
    public function view(int $id, BusinessRepository $businessRepository): Response
    {
        $business = $businessRepository->find($id);

        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            if (!$user || !$user->getBusiness() || $user->getBusiness()->getId() !== $id) {
                throw $this->createAccessDeniedException();
            }
        }

        return $this->render('business/view.html.twig', [
            'business' => $business,
        ]);
    }

    #[Route('business/delete/{id}', name: 'app_business_delete', methods: ['GET'])]
    public function delete(int $id, Business $business, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $entityManager->remove($business);
        $entityManager->flush();
        return $this->redirectToRoute('app_business');
    }

    #[Route('/business/{id}/stats', name: 'app_business_stats')]
    public function stats(Request $request,Business $business, SaleRecordRepository $saleRecordRepository): Response
    {
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN') &&
            (!$user || !$user->getBusiness() || $user->getBusiness()->getId() !== $business->getId())) {
            throw $this->createAccessDeniedException();
        }

        $period = $request->query->get('period', 'all_time');
        $allowedPeriods = ['all_time', 'last_month', 'last_week', 'today'];
        if (!in_array($period, $allowedPeriods)) {
            $period = 'all_time';
        }

        $records = $saleRecordRepository->getRecordsByPeriod($business, $period);

        $fulfilledRecords = array_filter($records, function($r) {
            return $r->getStatus() === 'fulfilled';
        });

        $cancelledRecords = array_filter($records, function($r) {
            return $r->getStatus() === 'cancelled';
        });

        $totalRevenue = 0.0;
        foreach ($fulfilledRecords as $r) {
            $totalRevenue += $r->getPackagePrice();
        }
        $totalOrders = count($fulfilledRecords);
        $totalCancelled = count($cancelledRecords);

        $monthlyGrouped = [];
        foreach ($fulfilledRecords as $r) {
            $month = $r->getOrderedAt()->format('Y-m');
            if (!isset($monthlyGrouped[$month])) {
                $monthlyGrouped[$month] = 0.0;
            }
            $monthlyGrouped[$month] += $r->getPackagePrice();
        }
        $monthlyRevenue = [];
        foreach ($monthlyGrouped as $m => $tot) {
            $monthlyRevenue[] = ['month' => $m, 'total' => $tot];
        }

        $categoryGrouped = [];
        foreach ($fulfilledRecords as $r) {
            $cat = $r->getCategoryName() ?? 'Unknown';
            if (!isset($categoryGrouped[$cat])) {
                $categoryGrouped[$cat] = ['total' => 0, 'revenue' => 0.0];
            }
            $categoryGrouped[$cat]['total']++;
            $categoryGrouped[$cat]['revenue'] += $r->getPackagePrice();
        }
        $categoryStats = [];
        foreach ($categoryGrouped as $name => $data) {
            $categoryStats[] = [
                'categoryName' => $name,
                'total' => $data['total'],
                'revenue' => $data['revenue']
            ];
        }
        usort($categoryStats, function($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        return $this->render('business/stats.html.twig', [
            'business'       => $business,
            'monthlyRevenue' => $monthlyRevenue,
            'categoryStats'  => $categoryStats,
            'totalRevenue'   => $totalRevenue,
            'totalOrders'    => $totalOrders,
            'totalCancelled' => $totalCancelled,
            'currentPeriod'  => $period,
        ]);
    }
}
