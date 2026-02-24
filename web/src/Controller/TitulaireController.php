<?php

namespace App\Controller;

use App\Entity\DossierClinique;
use App\Entity\ProfilMedical;
use App\Form\ProfilMedicalType;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\DisponibiliteRepository;
use App\Service\GeminiChatService;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/espace-client')]
class TitulaireController extends AbstractController
{
    #[Route('/settings', name: 'app_titulaire_settings')]
    public function settings(): Response
    {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        return $this->render('titulaire/settings.html.twig');
    }

    #[Route('/2fa/setup', name: 'app_titulaire_2fa_setup')]
    public function twoFactorSetup(
        Request $request,
        TotpAuthenticatorInterface $totpAuthenticator,
        EntityManagerInterface $em
    ): Response {
        /** @var \App\Entity\Utilisateur $user */
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        // Already enabled — go back to settings
        if ($user->isTotpAuthenticationEnabled()) {
            return $this->redirectToRoute('app_titulaire_settings');
        }

        // Generate and store a pending secret in the session until confirmed
        $session = $request->getSession();
        if (!$session->has('totp_secret_pending')) {
            $session->set('totp_secret_pending', $totpAuthenticator->generateSecret());
        }
        $pendingSecret = $session->get('totp_secret_pending');

        // Temporarily assign the secret so the bundle can build the OTP URI
        $user->setTotpSecret($pendingSecret);
        $qrContent = $totpAuthenticator->getQRContent($user);
        $user->setTotpSecret(null); // don't persist yet

        // Generate QR code as a base64 PNG data URI
        $qrCode = new QrCode(data: $qrContent, size: 200);
        $result = (new SvgWriter())->write($qrCode);
        $qrCodeDataUri = $result->getDataUri();

        // Handle confirmation form submission
        if ($request->isMethod('POST')) {
            $code = (string) $request->request->get('_auth_code', '');
            $user->setTotpSecret($pendingSecret);

            if ($totpAuthenticator->checkCode($user, $code)) {
                $em->persist($user);
                $em->flush();
                $session->remove('totp_secret_pending');
                $this->addFlash('success', 'L\'authentification à deux facteurs a été activée avec succès.');
                return $this->redirectToRoute('app_titulaire_settings');
            }

            // Bad code — reset, show error
            $user->setTotpSecret(null);
            $this->addFlash('danger', 'Code invalide. Veuillez réessayer.');
        }

        return $this->render('titulaire/2fa_setup.html.twig', [
            'qrCodeDataUri' => $qrCodeDataUri,
            'secret'        => $pendingSecret,
        ]);
    }

    #[Route('/2fa/disable', name: 'app_titulaire_2fa_disable', methods: ['POST'])]
    public function twoFactorDisable(
        EntityManagerInterface $em
    ): Response {
        /** @var \App\Entity\Utilisateur $user */
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $user->setTotpSecret(null);
        $em->persist($user);
        $em->flush();

        $this->addFlash('success', 'L\'authentification à deux facteurs a été désactivée.');
        return $this->redirectToRoute('app_titulaire_settings');
    }

    #[Route('/ai-chat', name: 'app_titulaire_ai_chat', methods: ['POST'])]
    public function aiChat(Request $request, GeminiChatService $gemini): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $message = trim($data['message'] ?? '');
        $history = $data['history'] ?? [];

        if ($message === '') {
            return new JsonResponse(['error' => 'Message vide'], 400);
        }

        try {
            $reply = $gemini->chat($message, $history);
            return new JsonResponse(['reply' => $reply]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => 'Erreur du service IA : ' . $e->getMessage()], 500);
        }
    }

    #[Route('/medicaments', name: 'app_titulaire_medicaments')]
    public function medicaments(Request $request, \App\Service\PharmacieApiService $pharmacieApi): Response
    {
        $search = $request->query->get('q');
        try {
            $medicaments = $pharmacieApi->getAllMedicaments($search);
        } catch (\Throwable $e) {
            $medicaments = [];
            $this->addFlash('danger', 'Impossible de charger les médicaments : ' . $e->getMessage());
        }

        return $this->render('titulaire/medicaments.html.twig', [
            'medicaments' => $medicaments,
            'search' => $search,
        ]);
    }

    #[Route('/dashboard', name: 'app_titulaire_dashboard')]
    #[Route('/{id}/dashboard', name: 'app_titulaire_dashboard_profil')]
    public function index(
        Request $request,
        ?int $id,
        EntityManagerInterface $entityManager,
        \App\Repository\DisponibiliteRepository $disponibiliteRepository
    ): Response {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        // --- 1. GESTION DU FORMULAIRE D'AJOUT DE PROFIL (MODAL) ---
        $newProfil = new ProfilMedical();
        $newProfil->setTitulaire($user);
        $form = $this->createForm(ProfilMedicalType::class, $newProfil);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dossier = new DossierClinique();
            $dossier->setProfilMedical($newProfil);
            $dossier->setAllergies([]);
            $dossier->setAntecedents("Dossier créé automatiquement.");

            $entityManager->persist($newProfil);
            $entityManager->persist($dossier);
            $entityManager->flush();

            $this->addFlash('success', 'Nouveau profil ajouté avec succès !');

            return $this->redirectToRoute('app_titulaire_dashboard_profil', ['id' => $newProfil->getId()]);
        }

        // --- 2. RÉCUPÉRATION DES DONNÉES DU DASHBOARD ---
        $query = $entityManager->createQuery(
            'SELECT p, d, r 
            FROM App\Entity\ProfilMedical p 
            LEFT JOIN p.dossierClinique d 
            LEFT JOIN d.rapportsMedicaux r
            WHERE p.titulaire = :user'
        )->setParameter('user', $user);

        $allProfils = $query->getArrayResult();

        // --- 3. RÉCUPÉRATION DES DISPONIBILITÉS DES MÉDECINS ---
        // On récupère les 6 dernières disponibilités pour le widget client
        $recent_dispos = $disponibiliteRepository->findBy([], ['id' => 'DESC'], 6);

        // --- 4. NETTOYAGE DES DONNÉES (SANITIZATION) ---
        foreach ($allProfils as &$p) {
            $p['dateNaissance'] = $p['date_naissance'];
            $p['contactUrgence'] = $p['contact_urgence'];

            if (!isset($p['dossierClinique']) || $p['dossierClinique'] === null) {
                $p['dossierClinique'] = [
                    'antecedents' => null,
                    'allergies' => [],
                    'rapportsMedicaux' => []
                ];
            }
        }
        unset($p);

        // --- 5. SÉLECTION DU PROFIL ACTIF ---
        $activeProfil = !empty($allProfils) ? $allProfils[0] : null;
        if ($id && !empty($allProfils)) {
            foreach ($allProfils as $profil) {
                if ($profil['id'] === $id) {
                    $activeProfil = $profil;
                    break;
                }
            }
        }

        // --- 6. RENDU DE LA VUE ---
        return $this->render('titulaire/dashboard.html.twig', [
            'activeProfil' => $activeProfil,
            'allProfils' => $allProfils,
            'recent_dispos' => $recent_dispos, // Variable nécessaire pour votre nouveau tableau
            'form' => $form->createView(),
        ]);
    }
}
