<?php

namespace App\Command;

use App\Repository\RendezVousRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:send-rdv-reminders',
    description: 'Envoie un email de rappel pour les RDV de demain',
)]
class SendRdvRemindersCommand extends Command
{
    private $rdvRepository;
    private $mailer;

    public function __construct(RendezVousRepository $rdvRepository, MailerInterface $mailer)
    {
        $this->rdvRepository = $rdvRepository;
        $this->mailer = $mailer;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 1. Définir la plage de temps (Demain entre 00h00 et 23h59)
        $start = new \DateTime('tomorrow midnight');
        $end   = new \DateTime('tomorrow midnight +23 hours 59 minutes');

        // 2. Trouver les RDV (Tu devras peut-être ajouter une méthode findByDateRange dans ton Repository)
        // Pour faire simple ici, imaginons qu'on récupère tout et qu'on filtre (pas optimisé mais simple)
        // L'idéal est de créer une méthode findRdvBetween($start, $end) dans le Repository
        
        $rdvs = $this->rdvRepository->findAll(); 
        
        $count = 0;

        foreach ($rdvs as $rdv) {
            // On vérifie si le RDV est demain ET s'il n'est pas annulé
            if ($rdv->getDateDebut() >= $start && $rdv->getDateDebut() <= $end && $rdv->getStatut() !== 'Annulé') {
                
                // On récupère l'email du patient lié au profil
                // ATTENTION : Adapte ceci selon comment tu récupères l'email depuis $rdv->getProfil()
                // Exemple : $emailAddress = $rdv->getProfil()->getUser()->getEmail();
                // Si tu n'as pas de lien direct, il faut le créer.
                // Pour l'exemple, je mets une adresse fictive si je ne trouve pas :
                $emailAddress = 'patient@test.com'; 

                $email = (new Email())
                    ->from('no-reply@docteur-rdv.com')
                    ->to($emailAddress)
                    ->subject('Rappel : Votre rendez-vous est demain !')
                    ->html('<p>N\'oubliez pas votre rendez-vous demain à ' . $rdv->getDateDebut()->format('H:i') . '</p>');

                $this->mailer->send($email);
                $count++;
            }
        }

        $output->writeln("$count emails de rappel envoyés.");

        return Command::SUCCESS;
    }
}