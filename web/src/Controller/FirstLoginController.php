<?php

namespace App\Controller;

use App\Entity\Utilisateur;
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

#[Route('/first-login')]
class FirstLoginController extends AbstractController
{
    /**
     * Step 1: Email verification for receptionist-created accounts.
     * Sends a 6-digit code on first visit, then validates the submitted code.
     */
    #[Route('/verify-email', name: 'first_login_verify_email')]
    public function verifyEmail(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
    ): Response {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // If already verified, skip to password change (or dashboard)
        if ($user->isVerified()) {
            if ($user->isMustChangePassword()) {
                return $this->redirectToRoute('first_login_change_password');
            }
            return $this->redirectToRoute('app_titulaire_dashboard');
        }

        $session = $request->getSession();

        // Send code on first visit (if no code in session yet for this user)
        if (!$session->has('first_login_code') || $session->get('first_login_user_id') !== $user->getId()) {
            $this->sendVerificationCode($session, $user, $mailer);
            $this->addFlash('success', 'Un code de vérification a été envoyé à votre adresse email.');
        }

        // Handle code submission
        if ($request->isMethod('POST')) {
            $submittedCode = $request->request->get('verification_code');
            $storedCode = $session->get('first_login_code');
            $expiresAt = $session->get('first_login_code_expires_at');

            if (!$storedCode) {
                $this->addFlash('error', 'Aucun code de vérification trouvé. Veuillez rafraîchir la page.');
                return $this->render('first_login/verify_email.html.twig', [
                    'email' => $user->getEmail(),
                ]);
            }

            // Check expiration
            if ($expiresAt < time()) {
                $this->addFlash('error', 'Le code de vérification a expiré. Veuillez demander un nouveau code.');
                return $this->render('first_login/verify_email.html.twig', [
                    'email' => $user->getEmail(),
                ]);
            }

            // Check code
            if ($submittedCode === $storedCode) {
                $user->setIsVerified(true);
                $entityManager->flush();

                // Clean up verification session data
                $session->remove('first_login_code');
                $session->remove('first_login_code_expires_at');
                $session->remove('first_login_user_id');

                $this->addFlash('success', 'Votre email a été vérifié avec succès !');

                // If they also need to change password, go there next
                if ($user->isMustChangePassword()) {
                    return $this->redirectToRoute('first_login_change_password');
                }

                return $this->redirectToRoute('app_titulaire_dashboard');
            }

            $this->addFlash('error', 'Code de vérification incorrect. Veuillez réessayer.');
        }

        return $this->render('first_login/verify_email.html.twig', [
            'email' => $user->getEmail(),
        ]);
    }

    /**
     * Resend the verification code.
     */
    #[Route('/resend', name: 'first_login_resend')]
    public function resendCode(
        Request $request,
        MailerInterface $mailer,
    ): Response {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();

        if (!$user || $user->isVerified()) {
            return $this->redirectToRoute('app_login');
        }

        $session = $request->getSession();
        $this->sendVerificationCode($session, $user, $mailer);

        $this->addFlash('success', 'Un nouveau code de vérification a été envoyé.');

        return $this->redirectToRoute('first_login_verify_email');
    }

    /**
     * Step 2: Force password change for receptionist-created accounts.
     */
    #[Route('/change-password', name: 'first_login_change_password')]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        Security $security,
    ): Response {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Must verify email first
        if (!$user->isVerified()) {
            return $this->redirectToRoute('first_login_verify_email');
        }

        // If password change not required, go to dashboard
        if (!$user->isMustChangePassword()) {
            return $this->redirectToRoute('app_titulaire_dashboard');
        }

        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');

            // Validation
            if (empty($newPassword) || empty($confirmPassword)) {
                $this->addFlash('error', 'Veuillez remplir tous les champs.');
            } elseif (strlen($newPassword) < 8) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
            } elseif ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
            } else {
                // Hash and save the new password
                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                $user->setMustChangePassword(false);
                $entityManager->flush();

                $this->addFlash('success', 'Votre mot de passe a été mis à jour avec succès !');

                // Re-authenticate with the new password and redirect to dashboard
                return $security->login($user, AppAuthenticator::class, 'main');
            }
        }

        return $this->render('first_login/change_password.html.twig');
    }

    /**
     * Generate a 6-digit code, store in session, and send via email.
     */
    private function sendVerificationCode($session, $user, MailerInterface $mailer): void
    {
        $verificationCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $session->set('first_login_user_id', $user->getId());
        $session->set('first_login_code', $verificationCode);
        $session->set('first_login_code_expires_at', (new \DateTime('+15 minutes'))->getTimestamp());

        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@clinique360.com', 'Clinique 360'))
            ->to((string) $user->getEmail())
            ->subject('Vérification de votre compte - Clinique 360')
            ->htmlTemplate('registration/confirmation_email.html.twig')
            ->context([
                'verificationCode' => $verificationCode,
                'userName' => $user->getPrenom(),
            ]);

        $mailer->send($email);
    }
}
