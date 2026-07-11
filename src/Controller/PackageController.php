<?php

namespace App\Controller;


use App\Dto\PackageSearchFilter;
use App\Entity\Package;
use App\Form\PackageFiltersType;
use App\Form\PackageFormType;
use App\Repository\PackageRepository;
use App\Repository\BusinessRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PackageController extends AbstractController
{
    #[Route('/package', name: 'app_package')]
    public function index(Request $request, PackageRepository $packageRepository, BusinessRepository $businessRepository): Response
    {
        $user = $this->getUser();
        $business = null;
        $excludeOrdered = false;

        if ($this->isGranted('ROLE_ADMIN')) {
        } elseif ($user && $user->getBusiness()) {
            $business = $user->getBusiness();
        } else {
            $excludeOrdered = true;
        }

        $filter = new PackageSearchFilter();
        $form = $this->createForm(PackageFiltersType::class, $filter, [
            'show_business' => $business === null,
            'cities' => $businessRepository->findUniqueCities(),
        ]);
        $form->handleRequest($request);

        return $this->render('package/index.html.twig', [
            'packages' => $packageRepository->findByFilter($filter, $business),
            'package_filter_form' => $form->createView(),
        ]);
    }

    #[Route('/package/{id}/edit', name: 'app_package_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Package $package, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user || !$user->getBusiness() || $user->getBusiness()->getId() !== $package->getBusiness()->getId()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(PackageFormType::class, $package);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
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

        $entityManager->remove($package);
        $entityManager->flush();
        return $this->redirectToRoute('app_package');
    }

}
