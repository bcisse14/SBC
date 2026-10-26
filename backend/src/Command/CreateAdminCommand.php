<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-admin', description: 'Cree le premier compte administrateur.')]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_REQUIRED)
            ->addOption('password', null, InputOption::VALUE_REQUIRED)
            ->addOption('first-name', null, InputOption::VALUE_OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');

        $email = $input->getOption('email');
        if (!$email) {
            $question = new Question('Email administrateur: ');
            $email = $helper->ask($input, $output, $question);
        }

        $password = $input->getOption('password');
        if (!$password) {
            $question = new Question('Mot de passe: ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $password = $helper->ask($input, $output, $question);
        }

        if (!is_string($email) || !is_string($password) || $email === '' || $password === '') {
            $output->writeln('<error>Email et mot de passe sont obligatoires.</error>');

            return Command::FAILURE;
        }

        $existing = $this->entityManager->getRepository(User::class)->findOneBy(['email' => mb_strtolower(trim($email))]);
        if ($existing instanceof User) {
            $output->writeln('<error>Un utilisateur avec cet email existe deja.</error>');

            return Command::FAILURE;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setFirstName(is_string($input->getOption('first-name')) ? $input->getOption('first-name') : null);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('<info>Administrateur cree avec succes.</info>');

        return Command::SUCCESS;
    }
}
