<?php

namespace App\Security;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class GoogleAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private RouterInterface $router,
    ) {}

    public function supports(Request $request): ?bool
    {
        // Only activate on the Google callback route
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);

                $email = $googleUser->getEmail();
                $googleId = $googleUser->getId();

                // 1. Check if a user with this googleId already exists
                $existingUser = $this->entityManager->getRepository(Utilisateur::class)
                    ->findOneBy(['googleId' => $googleId]);

                if ($existingUser) {
                    return $existingUser;
                }

                // 2. Check if a user with this email already exists (link accounts)
                $existingUser = $this->entityManager->getRepository(Utilisateur::class)
                    ->findOneBy(['email' => $email]);

                if ($existingUser) {
                    // Link the Google account to the existing user
                    $existingUser->setGoogleId($googleId);
                    $this->entityManager->flush();

                    return $existingUser;
                }

                // 3. Create a new Titulaire user from Google data
                $user = new Utilisateur();
                $user->setEmail($email);
                $user->setNom($googleUser->getLastName() ?? 'Nom');
                $user->setPrenom($googleUser->getFirstName() ?? 'Prénom');
                $user->setGoogleId($googleId);
                $user->setRoles(['ROLE_TITULAIRE']);
                $user->setIsVerified(true); // Google emails are already verified

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var Utilisateur $user */
        $user = $token->getUser();
        $roles = $user->getRoles();

        // First-login checks (for accounts created by admin/receptionist that linked via Google)
        if (!$user->isVerified()) {
            return new RedirectResponse($this->router->generate('first_login_verify_email'));
        }
        if ($user->isMustChangePassword()) {
            return new RedirectResponse($this->router->generate('first_login_change_password'));
        }

        // Role-based redirects (same logic as AppAuthenticator)
        if (in_array('ROLE_ADMIN', $roles, true)) {
            return new RedirectResponse($this->router->generate('admin_dashboard'));
        }
        if (in_array('ROLE_MEDECIN', $roles, true)) {
            return new RedirectResponse($this->router->generate('medecin_dashboard', ['id' => $user->getId()]));
        }
        if (in_array('ROLE_PERSONNEL', $roles, true) && $user->getNiveauAcces() === 'INFIRMIER') {
            return new RedirectResponse($this->router->generate('infirmier_dashboard'));
        }
        if (in_array('ROLE_PERSONNEL', $roles, true) && $user->getNiveauAcces() === 'RECEPTIONIST') {
            return new RedirectResponse($this->router->generate('receptionniste_dashboard'));
        }

        return new RedirectResponse($this->router->generate('app_titulaire_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->router->generate('app_login'), Response::HTTP_TEMPORARY_REDIRECT);
    }
}
