<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class SecurityController extends AbstractController
{
    public function __construct(private LoggerInterface $logger, private MailerInterface $mailer)
    {
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        if ($error) {
            $this->logger->error('Login error: ' . $error->getMessage());
        }

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        // Debug: dump error
        if ($error) {
            dump($error);
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $name = $request->request->get('name');
            $password = $request->request->get('password');

            // Vérifier si l'email existe déjà
            $userRepository = $entityManager->getRepository(User::class);
            $existingUser = $userRepository->findOneBy(['email' => $email]);
            if ($existingUser) {
                $this->addFlash('error', 'Un compte avec cette adresse email existe déjà.');
                return $this->render('security/register.html.twig', [
                    'name' => $name,
                    'email' => $email,
                ]);
            }

            // Générer un code de vérification
            $verificationCode = rand(100000, 999999);

            // Stocker temporairement les données en session
            $request->getSession()->set('registration_data', [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'code' => $verificationCode,
                'expires' => time() + 600, // 10 minutes
            ]);

            // Envoyer l'email avec le code
            $emailMessage = (new Email())
                ->from('noreply@covoiturage.com')
                ->to($email)
                ->subject('Code de vérification - Covoiturage')
                ->text('Bonjour ' . $name . ',votre code de vérification est : ' . $verificationCode . '. Il expire dans 10 minutes.Cordialement,L\'équipe Covoiturage');

            $this->mailer->send($emailMessage);

            $this->addFlash('success', 'Un code de vérification a été envoyé à votre adresse email.');

            return $this->redirectToRoute('app_verify_email');
        }

        return $this->render('security/register.html.twig');
    }

    #[Route('/verify-email', name: 'app_verify_email')]
    public function verifyEmail(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $session = $request->getSession();
        $registrationData = $session->get('registration_data');

        if (!$registrationData || time() > $registrationData['expires']) {
            $this->addFlash('error', 'Session expirée. Veuillez recommencer l\'inscription.');
            $session->remove('registration_data');
            return $this->redirectToRoute('app_register');
        }

        if ($request->isMethod('POST')) {
            $enteredCode = $request->request->get('verification_code');

            if ($enteredCode == $registrationData['code']) {
                // Créer l'utilisateur
                $user = new User();
                $user->setEmail($registrationData['email']);
                $user->setName($registrationData['name']);
                $user->setPassword($passwordHasher->hashPassword($user, $registrationData['password']));
                $user->setRoles(['ROLE_USER']);

                $entityManager->persist($user);
                $entityManager->flush();

                $session->remove('registration_data');
                $this->addFlash('success', 'Inscription réussie ! Vous pouvez maintenant vous connecter.');

                return $this->redirectToRoute('app_login');
            } else {
                $this->addFlash('error', 'Code de vérification incorrect.');
            }
        }

        return $this->render('security/verify_email.html.twig');
    }
}