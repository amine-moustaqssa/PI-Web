<?php

namespace App\Command;

use App\Repository\RendezVousRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:send-reminders',
    description: 'Envoie des emails de rappel pour les rendez-vous du lendemain',
)]
class SendRemindersCommand extends Command
{
    public function __construct(
        private RendezVousRepository $rendezVousRepository,
        private MailerInterface $mailer
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        // Calcul de la date de demain
        $tomorrow = new \DateTime('+1 day');
        $dateString = $tomorrow->format('Y-m-d');

        // On récupère les RDV validés pour demain
        $rendezVousDemain = $this->rendezVousRepository->createQueryBuilder('r')
            ->where('r.date_debut LIKE :date')
            ->andWhere('r.statut = :statut')
            ->setParameter('date', $dateString . '%')
            ->setParameter('statut', 'validé')
            ->getQuery()
            ->getResult();

        if (empty($rendezVousDemain)) {
            $io->info('Aucun rendez-vous prévu pour demain (' . $dateString . ').');
            return Command::SUCCESS;
        }

        foreach ($rendezVousDemain as $rdv) {
            $patientName = $rdv->getProfil()->getPrenom() . ' ' . $rdv->getProfil()->getNom();
            
            $email = (new Email())
                ->from('rappel@votreclinique.tn')
                ->to('patient@test.com') // À remplacer par $rdv->getProfil()->getEmail()
                ->subject('Rappel : Votre rendez-vous de demain')
                ->html("
                    <p>Bonjour {$patientName},</p>
                    <p>Ceci est un rappel pour votre rendez-vous de demain le <strong>{$rdv->getDateDebut()->format('d/m/Y à H:i')}</strong>.</p>
                    <p>Type : {$rdv->getType()}</p>
                ");

            $this->mailer->send($email);
        }

        $io->success(count($rendezVousDemain) . ' emails de rappel envoyés.');

        return Command::SUCCESS;
    }
}