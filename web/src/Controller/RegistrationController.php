<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\RegistrationFormType;
use App\Repository\UtilisateurRepository;
use App\Security\AppAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        UtilisateurRepository $utilisateurRepository,
    ): Response {
        $session = $request->getSession();

        // If user clicks "back to register" while a verification is pending,
        // delete the unverified user so they can re-register cleanly
        $pendingUserId = $session->get('verify_user_id');
        if ($pendingUserId) {
            $pendingUser = $utilisateurRepository->find($pendingUserId);
            if ($pendingUser && !$pendingUser->isVerified()) {
                $entityManager->remove($pendingUser);
                $entityManager->flush();
            }
            $session->remove('verify_user_id');
            $session->remove('verify_code');
            $session->remove('verify_code_expires_at');
        }

        $user = new Utilisateur();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // assign ROLE_TITULAIRE to registered users
            $user->setRoles(['ROLE_TITULAIRE']);
            $user->setIsVerified(false);

            $entityManager->persist($user);
            $entityManager->flush();

            // generate a 6-digit verification code and store it in the session
            $verificationCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $session = $request->getSession();
            $session->set('verify_user_id', $user->getId());
            $session->set('verify_code', $verificationCode);
            $session->set('verify_code_expires_at', (new \DateTime('+15 minutes'))->getTimestamp());

            // send the verification code by email via Mailtrap
            $email = (new TemplatedEmail())
                ->from(new Address('no-reply@clinique360.com', 'Clinique 360'))
                ->to((string) $user->getEmail())
                ->subject('Votre code de vérification - Clinique 360')
                ->htmlTemplate('registration/confirmation_email.html.twig')
                ->context([
                    'verificationCode' => $verificationCode,
                    'userName' => $user->getPrenom(),
                ]);

            $mailer->send($email);

            $this->addFlash('success', 'Un code de vérification a été envoyé à votre adresse email.');

            return $this->redirectToRoute('app_verify_email');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(
        Request $request,
        EntityManagerInterface $entityManager,
        UtilisateurRepository $utilisateurRepository,
        Security $security,
    ): Response {
        $session = $request->getSession();
        $userId = $session->get('verify_user_id');

        if (!$userId) {
            $this->addFlash('error', 'Aucune vérification en cours. Veuillez vous inscrire.');
            return $this->redirectToRoute('app_register');
        }

        $user = $utilisateurRepository->find($userId);

        if (!$user) {
            $session->remove('verify_user_id');
            $session->remove('verify_code');
            $session->remove('verify_code_expires_at');
            $this->addFlash('error', 'Utilisateur introuvable. Veuillez vous réinscrire.');
            return $this->redirectToRoute('app_register');
        }

        if ($user->isVerified()) {
            $session->remove('verify_user_id');
            $session->remove('verify_code');
            $session->remove('verify_code_expires_at');
            $this->addFlash('success', 'Votre email est déjà vérifié.');
            return $this->redirectToRoute('app_login');
        }

        // handle code submission
        if ($request->isMethod('POST')) {
            $submittedCode = $request->request->get('verification_code');
            $storedCode = $session->get('verify_code');
            $expiresAt = $session->get('verify_code_expires_at');

            if (!$storedCode) {
                $this->addFlash('error', 'Aucun code de vérification trouvé. Veuillez vous réinscrire.');
                return $this->redirectToRoute('app_register');
            }

            // check expiration
            if ($expiresAt < time()) {
                $this->addFlash('error', 'Le code de vérification a expiré. Veuillez demander un nouveau code.');
                return $this->render('registration/verify_email.html.twig', [
                    'email' => $user->getEmail(),
                ]);
            }

            // check code
            if ($submittedCode === $storedCode) {
                $user->setIsVerified(true);
                $entityManager->flush();

                // clean up session
                $session->remove('verify_user_id');
                $session->remove('verify_code');
                $session->remove('verify_code_expires_at');

                $this->addFlash('success', 'Votre email a été vérifié avec succès !');

                // log the user in automatically
                return $security->login($user, AppAuthenticator::class, 'main');
            }

            $this->addFlash('error', 'Code de vérification incorrect. Veuillez réessayer.');
        }

        return $this->render('registration/verify_email.html.twig', [
            'email' => $user->getEmail(),
        ]);
    }

    #[Route('/verify/resend', name: 'app_resend_verification')]
    public function resendVerificationCode(
        Request $request,
        UtilisateurRepository $utilisateurRepository,
        MailerInterface $mailer,
    ): Response {
        $session = $request->getSession();
        $userId = $session->get('verify_user_id');

        if (!$userId) {
            $this->addFlash('error', 'Aucune vérification en cours.');
            return $this->redirectToRoute('app_register');
        }

        $user = $utilisateurRepository->find($userId);

        if (!$user || $user->isVerified()) {
            return $this->redirectToRoute('app_login');
        }

        // generate a new code and store it in the session
        $verificationCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $session->set('verify_code', $verificationCode);
        $session->set('verify_code_expires_at', (new \DateTime('+15 minutes'))->getTimestamp());

        // send the new code via Mailtrap
        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@clinique360.com', 'Clinique 360'))
            ->to((string) $user->getEmail())
            ->subject('Nouveau code de vérification - Clinique 360')
            ->htmlTemplate('registration/confirmation_email.html.twig')
            ->context([
                'verificationCode' => $verificationCode,
                'userName' => $user->getPrenom(),
            ]);

        $mailer->send($email);

        $this->addFlash('success', 'Un nouveau code de vérification a été envoyé.');

        return $this->redirectToRoute('app_verify_email');
    }
}
