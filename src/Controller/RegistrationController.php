<?php

namespace App\Controller;

use App\Entity\Consumer;
use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, Security $security, MailerInterface $mailer, UrlGeneratorInterface $urlGenerator): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_package');
        }

        $user = new User();
        $consumer = new Consumer();
        $user->setConsumer($consumer);

        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();

            $user->setRoles(['ROLE_CONSUMER']);
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            $loginUrl = $urlGenerator->generate('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $email = (new Email())
                ->from('eco.meal@example.com')
                ->to($user->getEmail())
                ->subject('Welcome to Eco-Meal')
                ->html(sprintf('
                    <p>Hello %s,</p>
                    <p>Thank you for registering on Eco-Meal! We are excited to have you join our mission of reducing food waste while enjoying great meals at excellent prices.</p>
                    <p>Click here to login: <a href="%s">%s</a></p>
                ', $user->getConsumer()->getFirstName(), $loginUrl, $loginUrl));
            $mailer->send($email);
            return $security->login($user, 'App\Security\LoginFormAuthenticator', 'main');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
