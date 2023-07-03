<?php

namespace App\Tests\functional\Controller;

use App\Entity\Flat;
use App\Repository\FlatRepository;
use App\Repository\TenantRepository;
use App\Service\InvitationCodeHandler;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Ulid;

class RegistrationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManager $entityManager;
    private FlatRepository $flatRepository;
    private TenantRepository $tenantRepository;
    private Ulid $code;
    private Flat $flat;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->flatRepository = $container->get(FlatRepository::class);
        $this->tenantRepository = $container->get(TenantRepository::class);
        $this->flat = $this->flatRepository->findAll()[0];
    }

    public function testRegistrationControllerForTenant(): void
    {
        $crawler = $this->client->request('GET', '/register');
        $this->assertCount(1, $crawler->filter('form[name="registration_form"]'));

        $this->code = new Ulid();
        $this->flat->setInvitationCode($this->code);
        $this->entityManager->persist($this->flat);
        $this->entityManager->flush();

        $form = $crawler->filter('form[name="registration_form"]')->form([
            'registration_form[name]' => 'Example Tenant',
            'registration_form[email]' => 'example@tenant.pl',
            'registration_form[plainPassword][first]' => 'test12',
            'registration_form[plainPassword][second]' => 'test12',
            'registration_form[dateOfBirth]' => '2000-05-22',
            'registration_form[roles]' => 'ROLE_TENANT',
            'registration_form[code]' => $this->code->toBase32(),
        ]);
        $crawler = $this->client->submit($form);
        $crawler = $this->client->followRedirect();

        $tenant = $this->tenantRepository->findOneBy(['email' => 'example@tenant.pl']);
        $this->assertNotNull($tenant);
        $this->assertEquals('Example Tenant', $tenant->getName());
        $this->assertEquals($tenant, $this->flat->getTenants()[0]);
    }

    public function testRegistrationControllerForTenantForEmptyInvitationCode(): void
    {
        $crawler = $this->client->request('GET', '/register');
        $this->assertCount(1, $crawler->filter('form[name="registration_form"]'));

        $form = $crawler->filter('form[name="registration_form"]')->form([
            'registration_form[name]' => 'Example Tenant',
            'registration_form[email]' => 'example@tenant.pl',
            'registration_form[plainPassword][first]' => 'test12',
            'registration_form[plainPassword][second]' => 'test12',
            'registration_form[dateOfBirth]' => '2000-05-22',
            'registration_form[roles]' => 'ROLE_TENANT',
        ]);
        $crawler = $this->client->submit($form);
        $crawler = $this->client->followRedirect();

        $error = $crawler->filter('.alert.alert-danger')->text();
        $this->assertEquals('Please provide an invitation code.', $error);
    }

    public function testRegistrationControllerForTenantForInvalidInvitationCode(): void
    {
        $crawler = $this->client->request('GET', '/register');
        $this->assertCount(1, $crawler->filter('form[name="registration_form"]'));

        $form = $crawler->filter('form[name="registration_form"]')->form([
            'registration_form[name]' => 'Example Tenant',
            'registration_form[email]' => 'example@tenant.pl',
            'registration_form[plainPassword][first]' => 'test12',
            'registration_form[plainPassword][second]' => 'test12',
            'registration_form[dateOfBirth]' => '2000-05-22',
            'registration_form[roles]' => 'ROLE_TENANT',
            'registration_form[code]' => '00H4E142TE6W1WGJXTFEE7SME8',
        ]);
        $crawler = $this->client->submit($form);
        $crawler = $this->client->followRedirect();

        $error = $crawler->filter('.alert.alert-danger')->text();
        $this->assertEquals('Invalid invitation code.', $error);
    }
}