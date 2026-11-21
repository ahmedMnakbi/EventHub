<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Creates an administrator (or promotes an existing user).
 * The password is asked interactively so it never appears in the shell history.
 */
#[AsCommand(name: 'app:create-admin', description: 'Crée un compte administrateur ou promeut un utilisateur existant')]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Email de l\'administrateur');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = mb_strtolower(trim((string) $input->getArgument('email')));

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if ($user) {
            $user->setRoles([User::ROLE_ADMIN]);
            $this->entityManager->flush();
            $io->success(sprintf('%s est maintenant administrateur.', $email));

            return Command::SUCCESS;
        }

        $user = (new User())
            ->setEmail($email)
            ->setFirstName($io->ask('Prénom', 'Admin'))
            ->setLastName($io->ask('Nom', 'EventHub'))
            ->setRoles([User::ROLE_ADMIN]);

        $plain = (string) $io->askHidden('Mot de passe (8 caractères minimum)', function (?string $value) {
            if (null === $value || mb_strlen($value) < 8) {
                throw new \RuntimeException('Le mot de passe doit contenir au moins 8 caractères.');
            }

            return $value;
        });
        $user->setPassword($this->passwordHasher->hashPassword($user, $plain));

        $errors = $this->validator->validate($user);
        if (\count($errors) > 0) {
            foreach ($errors as $error) {
                $io->error($error->getPropertyPath().' : '.$error->getMessage());
            }

            return Command::FAILURE;
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $io->success(sprintf('Administrateur %s créé.', $email));

        return Command::SUCCESS;
    }
}
