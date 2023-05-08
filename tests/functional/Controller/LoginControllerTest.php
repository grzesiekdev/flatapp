<?php

namespace App\Tests\functional\Controller;

use App\Entity\User\User;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private User $user;
    private EntityManager $entityManager;
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $userPasswordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();


        $this->user = new User();
        $this->user->setName('Jan Kowalski')
            ->setEmail('jkowalski@o2.pl')
            ->setPassword(
                $userPasswordHasher->hashPassword(
                    $this->user,
                    'test12'
                )
            )
            ->setDateOfBirth(new \DateTime('1922-02-01'));
        $this->entityManager->persist($this->user);
        $this->entityManager->flush();
    }

    public function testUserBeingRedirectedAfterLogIn()
    {
        $crawler = $this->client->request('GET', '/login');
        $this->assertCount(1, $crawler->filter('form[name="login"]'));

        $form = $crawler->filter('form[name="login"]')->form([
            '_username' => 'jkowalski@o2.pl',
            '_password' => 'test12',
        ]);

        $this->client->submit($form);
        $this->assertEquals('http://localhost/', $this->client->getResponse()->headers->get('location'));
    }


    public function testUserNotBeingRedirectedWithInvalidEmail()
    {
        $crawler = $this->client->request('GET', '/login');
        $this->assertCount(1, $crawler->filter('form[name="login"]'));

        $form = $crawler->filter('form[name="login"]')->form([
            '_username' => 'jnowak@o2.pl',
            '_password' => 'test12',
        ]);

        $this->client->submit($form);
        $this->assertNotEquals('http://localhost/', $this->client->getResponse()->headers->get('location'));
    }

    public function testUserNotBeingRedirectedWithInvalidPassword()
    {
        $crawler = $this->client->request('GET', '/login');
        $this->assertCount(1, $crawler->filter('form[name="login"]'));

        $form = $crawler->filter('form[name="login"]')->form([
            '_username' => 'jkowalski@o2.pl',
            '_password' => 'test123',
        ]);

        $this->client->submit($form);
        $this->assertNotEquals('http://localhost/', $this->client->getResponse()->headers->get('location'));
    }

    public function testUserNotBeingRedirectedWithBlankEmail()
    {
        $crawler = $this->client->request('GET', '/login');
        $this->assertCount(1, $crawler->filter('form[name="login"]'));

        $form = $crawler->filter('form[name="login"]')->form([
            '_username' => '',
            '_password' => 'test123',
        ]);

        $this->client->submit($form);
        $this->assertNotEquals('http://localhost/', $this->client->getResponse()->headers->get('location'));
    }

    public function testUserNotBeingRedirectedWithBlankPassword()
    {
        $crawler = $this->client->request('GET', '/login');
        $this->assertCount(1, $crawler->filter('form[name="login"]'));

        $form = $crawler->filter('form[name="login"]')->form([
            '_username' => 'jkowalski@o2.pl',
            '_password' => '',
        ]);

        $this->client->submit($form);
        $this->assertNotEquals('http://localhost/', $this->client->getResponse()->headers->get('location'));
    }
}