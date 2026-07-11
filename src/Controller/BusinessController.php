<?php

namespace App\Controller;

use App\Entity\Business;
use App\Entity\Package;
use App\Entity\User;
use App\Form\BusinessAccountFormType;
use App\Form\BusinessFormType;
use App\Form\PackageFormType;
use App\Repository\BusinessRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class BusinessController extends AbstractController
{
    #[Route('/business', name: 'app_business')]
    public function index(BusinessRepository $businessRepository): Response
    {
        $businesses = $businessRepository->findAll();

        return $this->render('business/index.html.twig', [
            'businesses' => $businesses,
        ]);
    }

    #[Route('/business/new', name: 'app_business_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $business = new Business();

        $form = $this->createForm(BusinessFormType::class, $business);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($business);
            $entityManager->flush();

            return $this->redirectToRoute('app_business_view', ['id' => $business->getId()]);
        }
        return $this->render('business/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/business/{id}/edit', name: 'app_business_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request,Business $business, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            if (!$user || !$user->getBusiness() || $user->getBusiness()->getId() !== $business->getId()) {
                throw $this->createAccessDeniedException();
            }
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
        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            if (!$user || !$user->getBusiness() || $user->getBusiness()->getId() !== $business->getId()) {
                throw $this->createAccessDeniedException();
            }
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
}
