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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

use App\Dto\OrderSearchFilter;
use App\Form\OrderFiltersType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class OrderController extends AbstractController
{
    #[Route('/order', name: 'app_order')]
    public function index(Request $request, OrderRepository $orderRepository): Response
    {
        $orderRepository->deleteExpiredOrders();
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
    public function new(Request $request, EntityManagerInterface $entityManager, ?Package $package = null,MailerInterface $mailer, UrlGeneratorInterface $urlGenerator): Response
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
            $orderUrl = $urlGenerator->generate('app_order_view', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            $limitTime = $order->getCreatedAt()->modify('+2 hours')->format('H:i');

            $consumerEmail = (new Email())
                ->from('eco.meal@example.com')
                ->to($user->getEmail())
                ->subject('Order Confirmed - Eco Meal')
                ->html(sprintf('
                    <p>Hello %s,</p>
                    <p>Thank you for choosing Eco-Meal. Your reservation is confirmed!</p>
                    <p><strong>Order Details:</strong></p>
                    <ul>
                        <li>Order ID: #%d</li>
                        <li>Package: %s</li>
                        <li>Price: %s lei</li>
                        <li>Store: %s</li>
                    </ul>
                    <p>⏰ You must pick up your package by <strong>%s</strong>!</p>
                    <p>View details here: <a href="%s">%s</a></p>
                ',
                $user->getConsumer()->getFirstName(),
                $order->getId(),
                $order->getPackage()->getName(),
                number_format($order->getPackage()->getPrice(), 2, '.', ','),
                $order->getPackage()->getBusiness()->getName(),
                $limitTime,
                $orderUrl,
                $orderUrl
                ));
            $mailer->send($consumerEmail);

            $businessUser = $order->getPackage()->getBusiness()->getUser();
            if ($businessUser && $businessUser->getEmail()) {
                $businessEmail = (new Email())
                    ->from('eco.meal@example.com')
                    ->to($businessUser->getEmail())
                    ->subject('New Order Received - Eco Meal')
                    ->html(sprintf('
                        <p>Hello %s,</p>
                        <p>One of your packages has been ordered by a consumer.</p>
                        <p><strong>Reservation Details:</strong></p>
                        <ul>
                            <li>Order ID: #%d</li>
                            <li>Package: %s</li>
                            <li>Price: %s lei</li>
                            <li>Customer: %s</li>
                        </ul>
                        <p>⏰ The customer has until <strong>%s</strong> to pick it up.</p>
                        <p>Manage your orders here: <a href="%s">%s</a></p>
                    ',
                    $order->getPackage()->getBusiness()->getName(),
                    $order->getId(),
                    $order->getPackage()->getName(),
                    number_format($order->getPackage()->getPrice(), 2, '.', ','),
                    $order->getConsumer()->getFirstName() . ' ' . $order->getConsumer()->getLastName(),
                    $limitTime,
                    $urlGenerator->generate('app_order', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    $urlGenerator->generate('app_order', [], UrlGeneratorInterface::ABSOLUTE_URL)
                    ));
                $mailer->send($businessEmail);
            }
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

        $package = $order->getPackage();
        if ($package && $package->getBusiness()) {
            $record = new \App\Entity\SaleRecord();
            $record->setBusiness($package->getBusiness());
            $record->setPackageName($package->getName());
            $record->setPackagePrice($package->getPrice());
            $record->setCategoryName($package->getCategory()?->getName());
            $record->setOrderedAt($order->getCreatedAt());
            $record->setFulfilledAt(new \DateTimeImmutable());
            $record->setStatus('cancelled');
            $entityManager->persist($record);
        }

        $entityManager->remove($order);
        $entityManager->flush();

        $this->addFlash('success', 'Order cancelled successfully.');
        return $this->redirectToRoute('app_order');
    }

    #[Route('/order/fulfill/{id}', name: 'app_order_fulfill', methods: ['POST'])]
    public function fulfill(Order $order, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $user = $this->getUser();
        if (!$user || !$user->getBusiness() || !$order->getPackage() || !$order->getPackage()->getBusiness() ||
            $user->getBusiness()->getId() !== $order->getPackage()->getBusiness()->getId()) {
            throw $this->createAccessDeniedException();
        }

        $package = $order->getPackage();

        $record = new \App\Entity\SaleRecord();
        $record->setBusiness($user->getBusiness());
        $record->setPackageName($package->getName());
        $record->setPackagePrice($package->getPrice());
        $record->setCategoryName($package->getCategory()?->getName());
        $record->setOrderedAt($order->getCreatedAt());
        $record->setFulfilledAt(new \DateTimeImmutable());

        $em->persist($record);
        $order->setFulfilledAt(new \DateTimeImmutable());
        $em->flush();

        $consumerUser = $order->getConsumer()?->getUser();
        if ($consumerUser && $consumerUser->getEmail()) {
            $email = (new Email())
                ->from('eco.meal@example.com')
                ->to($consumerUser->getEmail())
                ->subject('Order Fulfilled - Eco Meal')
                ->html(sprintf('
                    <p>Hello %s,</p>
                    <p>Your order for the package <strong>%s</strong> has been successfully picked up and marked as completed.</p>
                    <p><strong>Order Summary:</strong></p>
                    <ul>
                        <li>Order ID: #%d</li>
                        <li>Price Paid: %s lei</li>
                        <li>Store: %s</li>
                        <li>Fulfilled At: %s</li>
                    </ul>
                    <p>Thank you for helping us reduce food waste!</p>
                ',
                $order->getConsumer()->getFirstName(),
                $package->getName(),
                $order->getId(),
                number_format($package->getPrice(), 2, '.', ','),
                $package->getBusiness()->getName(),
                $order->getFulfilledAt()->format('d.m.Y H:i')
                ));
            $mailer->send($email);
        }

        $this->addFlash('success', 'Order marked as fulfilled!');
        return $this->redirectToRoute('app_order');
    }
}
