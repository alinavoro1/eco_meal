<?php

namespace App\Controller;


use App\Entity\Package;
use App\Form\PackageFormType;
use App\Repository\PackageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PackageController extends AbstractController
{
    #[Route('/package', name: 'app_package')]
    public function index(PackageRepository $packageRepository): Response
    {
        $packages = $packageRepository->findAll();

        return $this->render('package/index.html.twig', [
            'packages' => $packages,
        ]);
    }

//    #[Route('/package/new', name: 'app_package_new', methods: ['GET', 'POST'])]
//    public function new(Request $request, EntityManagerInterface $entityManager): Response
//    {
//        $package = new Package();
//
//        $form = $this->createForm(PackageFormType::class, $package);
//        $form->handleRequest($request);
//
//        if($form->isSubmitted() && $form->isValid()) {
//            $entityManager->persist($package);
//            $entityManager->flush();
//            return $this->redirectToRoute('app_package');
//        }
//
//        return $this->render('package/new.html.twig', [
//            'form' => $form,
//        ]);
//    }

    #[Route('/package/{id}/edit', name: 'app_package_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request,Package $package, EntityManagerInterface $entityManager): Response
    {
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
        $entityManager->remove($package);
        $entityManager->flush();
        return $this->redirectToRoute('app_package');
    }

}
