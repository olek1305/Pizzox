<?php

namespace App\Command;

use App\Document\Admin;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-admin')]
class CreateAdminCommand extends Command
{
    /**
     * @var DocumentManager
     */
    private DocumentManager $dm;

    /**
     * @var UserPasswordHasherInterface
     */
    private UserPasswordHasherInterface $passwordHasher;

    /**
     * @param DocumentManager $dm
     * @param UserPasswordHasherInterface $passwordHasher
     */
    public function __construct(DocumentManager $dm, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->dm = $dm;
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Creates an admin user with ROLE_ADMIN')
            ->setHelp('This command allows you to create an admin user interactively by entering email and password.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');

        $emailQuestion = new Question('Enter admin email: ');
        $email = $helper->ask($input, $output, $emailQuestion);

        if (!$email) {
            $output->writeln('<error>Email cannot be empty.</error>');
            return Command::FAILURE;
        }

        $passwordQuestion = new Question('Enter admin password: ');
        $passwordQuestion->setHidden(true); // Ukryj hasło podczas wpisywania
        $passwordQuestion->setHiddenFallback(false); // Zapobiegnij upadkowi w razie problemu z terminalem
        $password = $helper->ask($input, $output, $passwordQuestion);

        if (!$password) {
            $output->writeln('<error>Password cannot be empty.</error>');
            return Command::FAILURE;
        }

        $admin = new Admin();
        $admin->setEmail($email);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, $password));
        $admin->setRoles(['ROLE_ADMIN']);

        $this->dm->persist($admin);
        $this->dm->flush();

        $output->writeln('<info>Admin user created successfully!</info>');

        return Command::SUCCESS;
    }
}