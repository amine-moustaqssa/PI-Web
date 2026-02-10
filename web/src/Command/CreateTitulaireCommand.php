<?php

namespace App\Command;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-titulaire',
    description: 'Creates a new Titulaire user for testing',
)]
class CreateTitulaireCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = 'bilel.riahi@gmail.com';

        // 1. Check if user already exists
        $existingUser = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);

        if ($existingUser) {
            $output->writeln('<comment>User already exists! Updating password to "123456"...</comment>');
            $user = $existingUser;
        } else {
            $output->writeln('<info>Creating new user...</info>');
            $user = new Utilisateur();
            $user->setEmail($email);
            $user->setRoles(['ROLE_TITULAIRE']);
            $user->setNom('Riahi');
            $user->setPrenom('Bilel');
            // Important: Set the discriminator column value if your logic relies on it manually
            // (Doctrine handles this automatically via the Class mapping usually, but good to be safe)
        }

        // 2. Hash the password "123456" CLEANLY
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            '123456'
        );
        $user->setPassword($hashedPassword);

        // 3. Save
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('<info>Success! You can now log in with:</info>');
        $output->writeln('Email: ' . $email);
        $output->writeln('Password: 123456');

        return Command::SUCCESS;
    }
}
