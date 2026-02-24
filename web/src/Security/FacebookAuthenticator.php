<?php

namespace App\Security;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\FacebookUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class FacebookAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private RouterInterface $router,
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_facebook_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('facebook');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var FacebookUser $facebookUser */
                $facebookUser = $client->fetchUserFromToken($accessToken);

                $email = $facebookUser->getEmail();
                $facebookId = $facebookUser->getId();

                // 1. Check if a user with this facebookId already exists
                $existingUser = $this->entityManager->getRepository(Utilisateur::class)
                    ->findOneBy(['facebookId' => $facebookId]);

                if ($existingUser) {
                    return $existingUser;
                }

                // 2. Check if a user with this email already exists (link accounts)
                if ($email) {
                    $existingUser = $this->entityManager->getRepository(Utilisateur::class)
                        ->findOneBy(['email' => $email]);

                    if ($existingUser) {
                        $existingUser->setFacebookId($facebookId);
                        $this->entityManager->flush();

                        return $existingUser;
                    }
                }

                // 3. Create a new Titulaire user from Facebook data
                $user = new Utilisateur();
                $user->setEmail($email);
                $user->setNom($facebookUser->getLastName() ?? 'Nom');
                $user->setPrenom($facebookUser->getFirstName() ?? 'Prénom');
                $user->setFacebookId($facebookId);
                $user->setRoles(['ROLE_TITULAIRE']);
                $user->setIsVerified(true); // Facebook emails are already verified

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

        // First-login checks (for accounts created by admin that linked via Facebook)
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
}
