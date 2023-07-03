<?php

namespace App\Tests\functional\Controller;

use App\Entity\Flat;
use App\Repository\FlatRepository;
use App\Repository\LandlordRepository;
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
    private LandlordRepository $landlordRepository;
    private Ulid $code;
    private Flat $flat;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->flatRepository = $container->get(FlatRepository::class);
        $this->tenantRepository = $container->get(TenantRepository::class);
        $this->landlordRepository = $container->get(LandlordRepository::class);
        $this->flat = $this->flatRepository->findAll()[0];
    }

    public function testRegistrationControllerForLandlord(): void
    {
        $crawler = $this->client->request('GET', '/register');
        $this->assertCount(1, $crawler->filter('form[name="registration_form"]'));

        $form = $crawler->filter('form[name="registration_form"]')->form([
            'registration_form[name]' => 'Example Landlord',
            'registration_form[email]' => 'example@landlord.pl',
            'registration_form[plainPassword][first]' => 'test12',
            'registration_form[plainPassword][second]' => 'test12',
            'registration_form[dateOfBirth]' => '2000-05-22',
            'registration_form[roles]' => 'ROLE_LANDLORD',
        ]);

        $crawler = $this->client->submit($form);
        $crawler = $this->client->followRedirect();

        $landlord = $this->landlordRepository->findOneBy(['email' => 'example@landlord.pl']);
        $this->assertNotNull($landlord);
        $this->assertEquals('Example Landlord', $landlord->getName());
        $this->assertEquals('ROLE_LANDLORD', $landlord->getRoles()[0]);
    }

    public function testRegistrationControllerForEmptyName(): void
    {
        $crawler = $this->client->request('GET', '/register');
        $this->assertCount(1, $crawler->filter('form[name="registration_form"]'));

        $form = $crawler->filter('form[name="registration_form"]')->form([
            'registration_form[name]' => '',
            'registration_form[email]' => 'example@landlord.pl',
            'registration_form[plainPassword][first]' => 'test12',
            'registration_form[plainPassword][second]' => 'test12',
            'registration_form[dateOfBirth]' => '2000-05-22',
            'registration_form[roles]' => 'ROLE_LANDLORD',
        ]);

        $crawler = $this->client->submit($form);

        $error = $crawler->filter('.alert.alert-danger')->text();
        $this->assertEquals('Name can\'t be blank', $error);
    }

    public function testRegistrationControllerForShortPassword(): void
    {
        $crawler = $this->client->request('GET', '/register');
        $this->assertCount(1, $crawler->filter('form[name="registration_form"]'));

        $form = $crawler->filter('form[name="registration_form"]')->form([
            'registration_form[name]' => 'Example Landlord',
            'registration_form[email]' => 'example@landlord.pl',
            'registration_form[plainPassword][first]' => 'test',
            'registration_form[plainPassword][second]' => 'test',
            'registration_form[dateOfBirth]' => '2000-05-22',
            'registration_form[roles]' => 'ROLE_LANDLORD',
        ]);

        $crawler = $this->client->submit($form);

        $error = $crawler->filter('.alert.alert-danger')->text();
        $this->assertEquals('Your password should be at least 6 characters', $error);
    }

    public function testRegistrationControllerForInvalidDate(): void
    {
        $crawler = $this->client->request('GET', '/register');
        $this->assertCount(1, $crawler->filter('form[name="registration_form"]'));

        $form = $crawler->filter('form[name="registration_form"]')->form([
            'registration_form[name]' => 'Example Landlord',
            'registration_form[email]' => 'example@landlord.pl',
            'registration_form[plainPassword][first]' => 'test12',
            'registration_form[plainPassword][second]' => 'test12',
            'registration_form[dateOfBirth]' => '2000/05/22',
            'registration_form[roles]' => 'ROLE_LANDLORD',
        ]);

        $crawler = $this->client->submit($form);

        $error = $crawler->filter('.alert.alert-danger')->text();
        $this->assertEquals('Please enter a valid date.', $error);
    }
    public function testRegistrationControllerForEmptyDate(): void
    {
        $crawler = $this->client->request('GET', '/register');
        $this->assertCount(1, $crawler->filter('form[name="registration_form"]'));

        $form = $crawler->filter('form[name="registration_form"]')->form([
            'registration_form[name]' => 'Example Landlord',
            'registration_form[email]' => 'example@landlord.pl',
            'registration_form[plainPassword][first]' => 'test12',
            'registration_form[plainPassword][second]' => 'test12',
            'registration_form[dateOfBirth]' => '',
            'registration_form[roles]' => 'ROLE_LANDLORD',
        ]);

        $crawler = $this->client->submit($form);

        $error = $crawler->filter('.alert.alert-danger')->text();
        $this->assertEquals('Please enter a valid date.', $error);
    }

    public function testRegistrationControllerForNotMatchingPasswords(): void
    {
        $crawler = $this->client->request('GET', '/register');
        $this->assertCount(1, $crawler->filter('form[name="registration_form"]'));

        $form = $crawler->filter('form[name="registration_form"]')->form([
            'registration_form[name]' => 'Example Landlord',
            'registration_form[email]' => 'example@landlord.pl',
            'registration_form[plainPassword][first]' => 'test123',
            'registration_form[plainPassword][second]' => 'test12',
            'registration_form[dateOfBirth]' => '2000-05-22',
            'registration_form[roles]' => 'ROLE_LANDLORD',
        ]);

        $crawler = $this->client->submit($form);

        $error = $crawler->filter('.alert.alert-danger')->text();
        $this->assertEquals('The password fields must match.', $error);
    }

    public function testRegistrationControllerForEmptyPasswords(): void
    {
        $crawler = $this->client->request('GET', '/register');
        $this->assertCount(1, $crawler->filter('form[name="registration_form"]'));

        $form = $crawler->filter('form[name="registration_form"]')->form([
            'registration_form[name]' => 'Example Landlord',
            'registration_form[email]' => 'example@landlord.pl',
            'registration_form[plainPassword][first]' => '',
            'registration_form[plainPassword][second]' => '',
            'registration_form[dateOfBirth]' => '2000-05-22',
            'registration_form[roles]' => 'ROLE_LANDLORD',
        ]);

        $crawler = $this->client->submit($form);

        $error = $crawler->filter('.alert.alert-danger')->text();
        $this->assertEquals('Please enter a password', $error);
    }

    public function testRegistrationControllerForMultipleErrors(): void
    {
        $crawler = $this->client->request('GET', '/register');
        $this->assertCount(1, $crawler->filter('form[name="registration_form"]'));

        $form = $crawler->filter('form[name="registration_form"]')->form([
            'registration_form[name]' => '',
            'registration_form[email]' => 'example@landlord.pl',
            'registration_form[plainPassword][first]' => '',
            'registration_form[plainPassword][second]' => '',
            'registration_form[dateOfBirth]' => '2000-05-22000',
            'registration_form[roles]' => 'ROLE_LANDLORD',
        ]);

        $crawler = $this->client->submit($form);

        $error = $crawler->filter('.alert.alert-danger')->text();
        $this->assertEquals('Name can\'t be blankPlease enter a passwordPlease enter a valid date.', $error);
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
        $this->assertEquals('ROLE_TENANT', $tenant->getRoles()[0]);
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