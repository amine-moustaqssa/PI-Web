<?php

namespace App\Tests\Security;

use App\Entity\Utilisateur;
use App\Security\AppAuthenticator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Tests unitaires pour l'authentification — redirection basée sur les rôles.
 * Fonctionnalité 4 : Authentification avancée (redirection selon rôle).
 */
class AppAuthenticatorTest extends TestCase
{
    private UrlGeneratorInterface $urlGenerator;
    private AppAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->authenticator = new AppAuthenticator($this->urlGenerator);
    }

    // ──────────────────────────────────────────────────────────────
    //  Redirection ROLE_ADMIN → admin_dashboard
    // ──────────────────────────────────────────────────────────────

    public function testRedirectionAdmin(): void
    {
        $this->urlGenerator->method('generate')
            ->with('admin_dashboard')
            ->willReturn('/admin');

        $user = $this->createUserMock(['ROLE_ADMIN'], true, false);
        $response = $this->callOnAuthenticationSuccess($user);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/admin', $response->getTargetUrl());
    }

    // ──────────────────────────────────────────────────────────────
    //  Redirection ROLE_MEDECIN → medecin_dashboard
    // ──────────────────────────────────────────────────────────────

    public function testRedirectionMedecin(): void
    {
        $this->urlGenerator->method('generate')
            ->with('medecin_dashboard', $this->anything())
            ->willReturn('/medecin/42');

        $user = $this->createUserMock(['ROLE_MEDECIN'], true, false, 42);
        $response = $this->callOnAuthenticationSuccess($user);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/medecin/42', $response->getTargetUrl());
    }

    // ──────────────────────────────────────────────────────────────
    //  Redirection utilisateur non vérifié → first_login_verify_email
    // ──────────────────────────────────────────────────────────────

    public function testRedirectionUtilisateurNonVerifie(): void
    {
        $this->urlGenerator->method('generate')
            ->with('first_login_verify_email')
            ->willReturn('/first-login/verify');

        $user = $this->createUserMock(['ROLE_USER'], false, false);
        $response = $this->callOnAuthenticationSuccess($user);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/first-login/verify', $response->getTargetUrl());
    }

    // ──────────────────────────────────────────────────────────────
    //  Redirection must_change_password → first_login_change_password
    // ──────────────────────────────────────────────────────────────

    public function testRedirectionChangementMotDePasse(): void
    {
        $this->urlGenerator->method('generate')
            ->with('first_login_change_password')
            ->willReturn('/first-login/password');

        $user = $this->createUserMock(['ROLE_USER'], true, true);
        $response = $this->callOnAuthenticationSuccess($user);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/first-login/password', $response->getTargetUrl());
    }

    // ──────────────────────────────────────────────────────────────
    //  Redirection par défaut (Titulaire) → app_titulaire_dashboard
    // ──────────────────────────────────────────────────────────────

    public function testRedirectionDefautTitulaire(): void
    {
        $this->urlGenerator->method('generate')
            ->with('app_titulaire_dashboard')
            ->willReturn('/titulaire');

        $user = $this->createUserMock(['ROLE_USER'], true, false);
        $response = $this->callOnAuthenticationSuccess($user);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/titulaire', $response->getTargetUrl());
    }

    // ──────────────────────────────────────────────────────────────
    //  getLoginUrl retourne la bonne route
    // ──────────────────────────────────────────────────────────────

    public function testGetLoginUrl(): void
    {
        $this->urlGenerator->method('generate')
            ->with('app_login')
            ->willReturn('/login');

        // Use reflection to access protected method
        $reflection = new \ReflectionMethod(AppAuthenticator::class, 'getLoginUrl');
        $reflection->setAccessible(true);

        $request = $this->createMock(Request::class);
        $url = $reflection->invoke($this->authenticator, $request);

        $this->assertSame('/login', $url);
    }

    // ──────────────────────────────────────────────────────────────
    //  LOGIN_ROUTE constante
    // ──────────────────────────────────────────────────────────────

    public function testLoginRouteConstant(): void
    {
        $this->assertSame('app_login', AppAuthenticator::LOGIN_ROUTE);
    }

    // ──────────────────────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────────────────────

    private function createUserMock(array $roles, bool $verified, bool $mustChangePassword, int $id = 1): Utilisateur
    {
        $user = $this->createMock(Utilisateur::class);
        $user->method('getRoles')->willReturn($roles);
        $user->method('isVerified')->willReturn($verified);
        $user->method('isMustChangePassword')->willReturn($mustChangePassword);
        $user->method('getId')->willReturn($id);
        return $user;
    }

    private function callOnAuthenticationSuccess(Utilisateur $user): ?RedirectResponse
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->willReturn(null); // No target path

        $request = $this->createMock(Request::class);
        $request->method('getSession')->willReturn($session);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        /** @var RedirectResponse|null */
        return $this->authenticator->onAuthenticationSuccess($request, $token, 'main');
    }
}
