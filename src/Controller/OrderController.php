<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Package;
use App\Form\OrderFormType;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use App\Dto\OrderSearchFilter;
use App\Form\OrderFiltersType;

final class OrderController extends AbstractController
{
    #[Route('/order', name: 'app_order')]
    public function index(Request $request, OrderRepository $orderRepository): Response
    {
        $user = $this->getUser();
        $consumerContext = null;
        $businessContext = null;

        if ($this->isGranted('ROLE_ADMIN')) {
            // Admin sees all
        } elseif ($user && $user->getConsumer()) {
            $consumerContext = $user->getConsumer();
        } elseif ($user && $user->getBusiness()) {
            $businessContext = $user->getBusiness();
        }

        $filter = new OrderSearchFilter();
        $form = $this->createForm(OrderFiltersType::class, $filter, [
            'method' => 'GET',
            'show_business' => $businessContext === null,
            'show_consumer' => $consumerContext === null,
        ]);
        $form->handleRequest($request);

        $orders = $orderRepository->findByFilter($filter, $consumerContext, $businessContext);

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
            'order_filter_form' => $form->createView(),
        ]);
    }
    #[Route('/order/new/{id?}', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ?Package $package = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CONSUMER');

        $user = $this->getUser();
        $consumer = $user->getConsumer();

        $order = new Order();
        $order->setCreatedAt(new \DateTimeImmutable());

        if ($package) {
            $order->setPackage($package);
        }

        if ($consumer) {
            $order->setConsumer($consumer);
        }

        $form = $this->createForm(OrderFormType::class, $order, [
            'include_package' => $package ? false : true,
            'include_consumer' => $consumer ? false : true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$order->getConsumer() && $consumer) {
                $order->setConsumer($consumer);
            }
            if (!$order->getPackage() && $package) {
                $order->setPackage($package);
            }

            $entityManager->persist($order);
            $entityManager->flush();

            return $this->redirectToRoute('app_order');
        }

        return $this->render('order/new.html.twig', [
            'form' => $form,
            'package' => $package,
        ]);
    }

    #[Route('/order/{id}/edit', name: 'app_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        throw $this->createAccessDeniedException();
    }

    #[Route('/order/{id}', name: 'app_order_view')]
    public function view(int $id, OrderRepository $orderRepository): Response
    {
        $order = $orderRepository->find($id);
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
        } elseif ($user && $user->getConsumer() && $order->getConsumer() && $order->getConsumer()->getId() === $user->getConsumer()->getId()) {
        } elseif ($user && $user->getBusiness() && $order->getPackage() && $order->getPackage()->getBusiness() && $order->getPackage()->getBusiness()->getId() === $user->getBusiness()->getId()) {
        } else {
            throw $this->createAccessDeniedException();
        }

        return $this->render('order/view.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/order/delete/{id}', name: 'app_order_delete', methods: ['GET'])]
    public function delete(Order $order, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $isConsumerOwner = $user->getConsumer() && $order->getConsumer() && $order->getConsumer()->getId() === $user->getConsumer()->getId();
        $isBusinessOwner = $user->getBusiness() && $order->getPackage() && $order->getPackage()->getBusiness() && $order->getPackage()->getBusiness()->getId() === $user->getBusiness()->getId();

        if (!$isConsumerOwner && !$isBusinessOwner) {
            throw $this->createAccessDeniedException();
        }

        $entityManager->remove($order);
        $entityManager->flush();

        return $this->redirectToRoute('app_order');
    }
}
