<?php

namespace App\Controller;


use App\Dto\PackageSearchFilter;
use App\Entity\Package;
use App\Form\PackageFiltersType;
use App\Form\PackageFormType;
use App\Repository\PackageRepository;
use App\Repository\BusinessRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PackageController extends AbstractController
{
    #[Route('/package', name: 'app_package')]
    public function index(Request $request, PackageRepository $packageRepository, BusinessRepository $businessRepository, EntityManagerInterface $entityManager, OrderRepository $orderRepository): Response
    {
        $orderRepository->deleteExpiredOrders();
        $user = $this->getUser();
        $business = null;
        $excludeOrdered = false;
        $isAdminOrBusiness = false;

        if ($this->isGranted('ROLE_ADMIN')) {
            $isAdminOrBusiness = true;
        } elseif ($user && $user->getBusiness()) {
            $business = $user->getBusiness();
            $isAdminOrBusiness = true;
        } else {
            $excludeOrdered = true;
        }


        $filter = new PackageSearchFilter();
        $form = $this->createForm(PackageFiltersType::class, $filter, [
            'show_business' => $business === null,
            'cities' => $businessRepository->findUniqueCities(),
        ]);
        $form->handleRequest($request);

        $consumer = ($user && $user->getConsumer()) ? $user->getConsumer() : null;
        $preferencesForm = null;
        if ($consumer) {
            $preferencesForm = $this->createForm(\App\Form\ConsumerPreferencesType::class, $consumer);
            $preferencesForm->handleRequest($request);
            if ($preferencesForm->isSubmitted() && $preferencesForm->isValid()) {
                $entityManager->flush();
                $this->addFlash('success', 'Preferences saved successfully!');
                return $this->redirectToRoute('app_package');
            }
        }

        if ($isAdminOrBusiness) {
            $allPackages = $packageRepository->findByFilter($filter, $business, false);
            $threshold = new \DateTimeImmutable('-24 hours');

            $availablePackages = [];
            $ongoingPackages = [];
            $expiredPackages = [];

            $needFlush = false;
            foreach ($allPackages as $pkg) {
                $order = $pkg->getConsumerOrder();
                $isExpired = ($pkg->getCreatedAt() !== null && $pkg->getCreatedAt() < $threshold);
                $isOrdered = ($order !== null);
                $isFulfilled = ($order !== null && $order->isFulfilled());

                if ($isFulfilled || ($isOrdered && $isExpired)) {
                    $expiredPackages[] = $pkg;
                } elseif ($isOrdered) {
                    $ongoingPackages[] = $pkg;
                } elseif (!$isExpired) {
                    $availablePackages[] = $pkg;
                }  else {
                    $entityManager->remove($pkg);
                    $needFlush = true;
                }
            }
            if ($needFlush) {
                $entityManager->flush();
            }


            return $this->render('package/index.html.twig', [
                'packages' => null,
                'available_packages' => $availablePackages,
                'ongoing_packages' => $ongoingPackages,
                'expired_packages' => $expiredPackages,
                'is_admin_or_business' => true,
                'package_filter_form' => $form->createView(),
                'preferences_form' => null,
            ]);
        }

        $favoritePackages = [];
        if ($consumer) {
            $favoritePackages = $packageRepository->findByFavoriteBusinesses($consumer, $filter);
        }

        return $this->render('package/index.html.twig', [
            'packages' => $packageRepository->findByFilter($filter, $business, $excludeOrdered, $consumer),
            'available_packages' => [],
            'ongoing_packages' => [],
            'expired_packages' => [],
            'favorite_packages' => $favoritePackages,
            'is_admin_or_business' => false,
            'package_filter_form' => $form->createView(),
            'preferences_form' => $preferencesForm ? $preferencesForm->createView() : null,
        ]);
    }

    #[Route('/package/{id}/edit', name: 'app_package_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Package $package, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user || !$user->getBusiness() || $user->getBusiness()->getId() !== $package->getBusiness()->getId()) {
            throw $this->createAccessDeniedException();
        }

        $threshold = new \DateTimeImmutable('-24 hours');
        if ($package->getConsumerOrder() !== null || ($package->getCreatedAt() !== null && $package->getCreatedAt() < $threshold)) {
            $this->addFlash('error', 'Ongoing or expired packages cannot be edited.');
            return $this->redirectToRoute('app_package');
        }

        $form = $this->createForm(PackageFormType::class, $package);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $newFilename = uniqid().'.'.$photoFile->guessExtension();
                try {
                    $photoFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/packages',
                        $newFilename
                    );
                    $package->setPhoto('/uploads/packages/'.$newFilename);
                } catch (\Exception $e) {
                    dd($e);
                }
            }

            $entityManager->flush();
            return $this->redirectToRoute('app_package_view', ['id' => $package->getId()]);
        }

        return $this->render('package/edit.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/package/{id}', name: 'app_package_view', methods: ['GET'])]
    public function view(int $id, PackageRepository $packageRepository): Response
    {
        $package = $packageRepository->find($id);

        return $this->render('package/view.html.twig', [
            'package' => $package,
        ]);
    }

    #[Route('/package/delete/{id}', name: 'app_package_delete', methods: ['GET'])]
    public function delete(Package $package, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user || !$user->getBusiness() || $user->getBusiness()->getId() !== $package->getBusiness()->getId()) {
            throw $this->createAccessDeniedException();
        }

        $threshold = new \DateTimeImmutable('-24 hours');
        if ($package->getConsumerOrder() !== null || ($package->getCreatedAt() !== null && $package->getCreatedAt() < $threshold)) {
            $this->addFlash('error', 'Ongoing or expired packages cannot be deleted.');
            return $this->redirectToRoute('app_package');
        }

        $entityManager->remove($package);
        $entityManager->flush();
        return $this->redirectToRoute('app_package');
    }

}
