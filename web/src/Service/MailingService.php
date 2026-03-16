<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Twig\Environment;

/**
 * Service de mailing utilisant le bundle externe symfony/mailer.
 * Envoie des emails d'alertes pour les constantes vitales critiques
 * et des rapports de consultation par email.
 */
class MailingService
{
    private MailerInterface $mailer;
    private Environment $twig;
    private string $senderEmail;
    private string $senderName;

    public function __construct(
        MailerInterface $mailer,
        Environment $twig,
        string $senderEmail = 'noreply@clinique360.tn',
        string $senderName = 'Clinique 360'
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->senderEmail = $senderEmail;
        $this->senderName = $senderName;
    }

    /**
     * Envoie un email d'alerte pour des constantes vitales critiques.
     *
     * @param string $recipientEmail Email du destinataire (infirmier/médecin)
     * @param string $recipientName  Nom du destinataire
     * @param array  $criticalAlerts Liste des alertes critiques
     * @param int    $consultationId ID de la consultation concernée
     */
    public function sendCriticalAlert(
        string $recipientEmail,
        string $recipientName,
        array $criticalAlerts,
        int $consultationId
    ): void {
        $html = $this->twig->render('front/infirmier/email/critical_alert.html.twig', [
            'recipientName' => $recipientName,
            'criticalAlerts' => $criticalAlerts,
            'consultationId' => $consultationId,
            'sentAt' => new \DateTime(),
        ]);

        $email = (new Email())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to(new Address($recipientEmail, $recipientName))
            ->subject('⚠️ ALERTE CRITIQUE - Constantes vitales anormales - Consultation #' . $consultationId)
            ->html($html)
            ->priority(Email::PRIORITY_HIGH);

        $this->mailer->send($email);
    }

    /**
     * Envoie un rapport de constantes vitales par email avec le PDF en pièce jointe.
     *
     * @param string $recipientEmail Email du destinataire
     * @param string $recipientName  Nom du destinataire
     * @param array  $reportData     Données du rapport (constantes, analysis, etc.)
     * @param string|null $pdfContent Contenu binaire du PDF à attacher
     */
    public function sendConstantesReport(
        string $recipientEmail,
        string $recipientName,
        array $reportData,
        ?string $pdfContent = null
    ): void {
        $html = $this->twig->render('front/infirmier/email/constantes_report.html.twig', [
            'recipientName' => $recipientName,
            'constantes' => $reportData['constantes'] ?? [],
            'analysis' => $reportData['analysis'] ?? [],
            'comparisonData' => $reportData['comparisonData'] ?? [],
            'consultationA' => $reportData['consultationA'] ?? null,
            'sentAt' => new \DateTime(),
        ]);

        $email = (new Email())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to(new Address($recipientEmail, $recipientName))
            ->subject('📊 Rapport des constantes vitales - Clinique 360')
            ->html($html);

        if ($pdfContent) {
            $email->attach($pdfContent, 'rapport_constantes_' . date('Y-m-d_H-i') . '.pdf', 'application/pdf');
        }

        $this->mailer->send($email);
    }

    /**
     * Envoie un email en masse à plusieurs destinataires (envoi en masse).
     *
     * @param array  $recipients  Liste de ['email' => '...', 'name' => '...']
     * @param string $subject     Sujet de l'email
     * @param string $template    Template Twig pour le contenu
     * @param array  $data        Données passées au template
     */
    public function sendBulkEmail(
        array $recipients,
        string $subject,
        string $template,
        array $data = []
    ): void {
        foreach ($recipients as $recipient) {
            $html = $this->twig->render($template, array_merge($data, [
                'recipientName' => $recipient['name'] ?? 'Utilisateur',
                'sentAt' => new \DateTime(),
            ]));

            $email = (new Email())
                ->from(new Address($this->senderEmail, $this->senderName))
                ->to(new Address($recipient['email'], $recipient['name'] ?? ''))
                ->subject($subject)
                ->html($html);

            $this->mailer->send($email);
        }
    }
}
