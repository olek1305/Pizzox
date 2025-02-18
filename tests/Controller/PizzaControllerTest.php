<?php

namespace App\Tests\Controller;

use App\Document\Admin;
use App\Document\Pizza;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PizzaControllerTest extends WebTestCase
{
    private ?KernelBrowser $client = null;
    private ?Pizza $pizza = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Tworzymy klienta raz podczas cyklu testowego
        $this->client = PizzaControllerTest::createClient();
        $manager = PizzaControllerTest::getContainer()->get('doctrine_mongodb')->getManager();
        $manager->getDocumentCollection(Pizza::class)->drop();

        // Tworzymy pizzę, która będzie używana w testach
        $pizza = new Pizza();
        $pizza->setName('Pepperoni');
        $pizza->setPrice(25.0);
        $pizza->setSize(['medium', 'large']);
        $pizza->setToppings(['cheese', 'pepperoni']);

        // Zapis pizzy w MongoDB
        $manager->persist($pizza);
        $manager->flush();

        // Przechowujemy pizzę jako obiekt, aby jej ID wykorzystać w testach
        $this->pizza = $pizza;
    }

    public function testIndex(): void
    {
        // Wykorzystujemy klienta utworzonego w setUp()
        $this->client->request('GET', '/pizza');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.pizza-list');
    }

    public function testCreatePizza(): void
    {
        $manager = PizzaControllerTest::getContainer()->get('doctrine_mongodb')->getManager();
        $admin = $manager->getRepository(Admin::class)->findOneBy(['email' => 'admin@example.com']);
        self::assertNotNull($admin, 'Admin nie został znaleziony w bazie testowej!');

        $this->client->loginUser($admin);
        $this->client->request('GET', '/pizza/create');

        self::assertResponseIsSuccessful();

        $crawler = $this->client->getCrawler();
        $form = $crawler->selectButton('Save')->form([
            'pizza[name]' => 'Hawaiian Pizza',
            'pizza[price]' => '24.99',
            'pizza[size]' => ['small', 'medium']
        ]);

        // Dodajemy toppings przez formularz prototypu
        $formData = $form->getPhpValues();
        $formData['pizza']['toppings'] = [
            0 => 'pineapple',
            1 => 'ham',
            2 => 'cheese'
        ];

        $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            $formData,
            $form->getPhpFiles()
        );

        self::assertResponseRedirects('/pizza');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'Pizza created successfully!');
    }

    public function testShowPizza(): void
    {
        // Pobierz admina
        $manager = PizzaControllerTest::getContainer()->get('doctrine_mongodb')->getManager();
        $admin = $manager->getRepository(Admin::class)->findOneBy(['email' => 'admin@example.com']);
        self::assertNotNull($admin, 'Admin nie został znaleziony w bazie testowej!');

        // Zaloguj admina
        $this->client->loginUser($admin);

        // Sprawdzenie szczegółów pizzy
        self::assertNotNull($this->pizza, 'Nie stworzono pizzy w bazie testowej!');

        $this->client->request('GET', '/pizza/' . $this->pizza->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.pizza-name', 'Pepperoni');
    }
}
