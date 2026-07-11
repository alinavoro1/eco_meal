<?php

namespace App\Controller;

use App\Entity\Consumer;
use App\Entity\User;
use App\Form\ConsumerFormType;
use App\Form\ConsumerAccountFormType;
use App\Repository\ConsumerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ConsumerController extends AbstractController
{
    #[Route('/consumer', name: 'app_consumer')]
    public function index(ConsumerRepository $consumerRepository): Response
    {
        $consumers = $consumerRepository->findAll();

        return $this->render('consumer/index.html.twig', [
            'consumers' => $consumers,
        ]);
    }

    #[Route('/consumer/new', name: 'app_consumer_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $consumer = new Consumer();

        $form = $this->createForm(ConsumerFormType::class, $consumer, [
            'is_create' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = new User();
            $user->setEmail($form->get('email')->getData());
            $user->setRoles(['ROLE_CONSUMER']);
            $user->setPassword($passwordHasher->hashPassword($user, $form->get('password')->getData()));
            $user->setConsumer($consumer);

            $entityManager->persist($consumer);
            $entityManager->persist($user);
            $entityManager->flush();
            return $this->redirectToRoute('app_consumer');
        }

        return $this->render('consumer/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/consumer/{id}/edit', name: 'app_consumer_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Consumer $consumer, EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = $security->getUser();

        if ((!$user || !$user->getConsumer() || $user->getConsumer()->getId() !== $consumer->getId())) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ConsumerFormType::class, $consumer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_consumer_view', ['id' => $consumer->getId()]);
        }

        return $this->render('consumer/edit.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/consumer/{id}', name: 'app_consumer_view', methods: ['GET'])]
    public function view(int $id, ConsumerRepository $consumerRepository, Security $security): Response
    {
        $consumer = $consumerRepository->find($id);

        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->render('consumer/view.html.twig', [
                'consumer' => $consumer,
            ]);
        }

        $user = $security->getUser();

        if ($user && $user->getConsumer() && $user->getConsumer()->getId() === $consumer->getId()) {
            return $this->render('consumer/view.html.twig', [
                'consumer' => $consumer,
            ]);
        }

        throw $this->createAccessDeniedException();
    }

    #[Route('/consumer/delete/{id}', name: 'app_consumer_delete', methods: ['GET'])]
    public function delete(Consumer $consumer, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $entityManager->remove($consumer);
        $entityManager->flush();
        return $this->redirectToRoute('app_consumer');
    }

    #[Route('/consumer/{id}/create_account', name: 'app_consumer_create_account', methods: ['GET', 'POST'])]
    public function createAccount(Request $request, Consumer $consumer, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($consumer->getUser()) {
            return $this->redirectToRoute('app_consumer_view', ['id' => $consumer->getId()]);
        }

        $user = new User();
        $form = $this->createForm(ConsumerAccountFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setConsumer($consumer);
            $user->setRoles(['ROLE_CONSUMER']);
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_consumer_view', ['id' => $consumer->getId()]);
        }

        return $this->render('consumer/create_account.html.twig', [
            'form' => $form,
            'consumer' => $consumer,
        ]);
    }
}
