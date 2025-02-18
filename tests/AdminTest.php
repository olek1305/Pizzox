<?php

namespace App\Tests;

use App\Document\Admin;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AdminTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        // Tworzymy kernel Symfony dla bazy testowej
        self::bootKernel();
        $manager = self::getContainer()->get('doctrine_mongodb')->getManager();

        // Sprawdź, czy admin już istnieje
        $existingAdmin = $manager->getRepository(Admin::class)->findOneBy(['email' => 'admin@example.com']);
        if (!$existingAdmin) {
            // Tworzenie nowego admina
            $admin = new Admin();
            $admin->setEmail('admin@example.com');
            $admin->setRoles(['ROLE_ADMIN']);

            // Hashowanie hasła (opcjonalne)
            $passwordHasher = self::getContainer()->get('security.user_password_hasher');
            $hashedPassword = $passwordHasher->hashPassword($admin, 'test_password');
            $admin->setPassword($hashedPassword);

            // Zapis admina w MongoDB
            $manager->persist($admin);
            $manager->flush();
        }
    }

    public function testAdminCreated(): void
    {
        // Sprawdzenie, czy admin faktycznie został stworzony
        $manager = self::getContainer()->get('doctrine_mongodb')->getManager();
        $admin = $manager->getRepository(Admin::class)->findOneBy(['email' => 'admin@example.com']);

        $this->assertNotNull($admin, 'Administrator nie został znaleziony w bazie testowej!');
        $this->assertSame('admin@example.com', $admin->getEmail());
    }
}
