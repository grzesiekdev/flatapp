<?php

namespace App\Tests\functional\Controller\Specialists;

use App\Entity\Flat;
use App\Entity\Specialist;
use App\Entity\User\Type\Landlord;
use App\Entity\User\Type\Tenant;
use App\Repository\FlatRepository;
use App\Repository\SpecialistRepository;
use App\Repository\TenantRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use App\Tests\Utils\TestDataProvider;

class SpecialistControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private TestDataProvider $testDataProvider;
    private Tenant $tenant;
    private Tenant $tenantTwo;
    private Landlord $landlord;
    private EntityManager $entityManager;
    private KernelInterface $appKernel;
    private TenantRepository $tenantRepository;
    private FlatRepository $flatRepository;
    private SpecialistRepository $specialistRepository;
    private Flat $flat;
    private Flat $flatTwo;
    private Specialist $specialist;

    /*
     * In this test we have the following relations:
     * Tenant 1 -> related with Landlord 1 by Flat 1
     * Tenant 2 -> related with Landlord 1 by Flat 2
     * Landlord 1 -> related with Tenant 1 Flat 1, and with Tenant 2 by Flat 2
     * Specialist 1 -> related with Landlord 1 and Tenant 1 by Flat 1
     */

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->testDataProvider = self::getContainer()->get('App\Tests\Utils\TestDataProvider');
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->appKernel = self::getContainer()->get(KernelInterface::class);
        $this->tenantRepository = self::getContainer()->get(TenantRepository::class);
        $this->flatRepository = self::getContainer()->get(FlatRepository::class);
        $this->specialistRepository = self::getContainer()->get(SpecialistRepository::class);

        $usersData = [
            'tenant1' => [
                'name' => 'Jan Kowalski',
                'email' => 'jkowalski@tenant.pl',
                'password' => 'test12',
                'dob' => '1922-02-01',
                'role' => 'ROLE_TENANT',
                'image' => 'default-profile-picture.png'
            ],
            'tenant2' => [
                'name' => 'Jan Kowalski',
                'email' => 'jkowalski2@tenant.pl',
                'password' => 'test12',
                'dob' => '1922-02-01',
                'role' => 'ROLE_TENANT',
                'image' => 'default-profile-picture.png'
            ],
            'landlord1' => [
                'name' => 'Jan Kowalski',
                'email' => 'jkowalski@landlord.pl',
                'password' => 'test12',
                'dob' => '1922-02-01',
                'role' => 'ROLE_LANDLORD',
                'image' => 'default-profile-picture.png'
            ],
        ];

        $users = $this->testDataProvider->provideUsers($usersData);

        $this->tenant = $users['tenant1'];
        $this->tenantTwo = $users['tenant2'];

        $this->landlord = $users['landlord1'];

        $this->flat = new Flat();
        $this->flat->setArea(55);
        $this->flat->setNumberOfRooms(2);
        $this->flat->setAddress('Testowa 12');
        $this->flat->setFloor(3);
        $this->flat->setMaxFloor(5);
        $this->flat->setRent(2000);
        $this->flat->setLandlord($this->landlord);
        $this->flat->addTenant($this->tenant);

        $this->flatTwo = new Flat();
        $this->flatTwo->setArea(25);
        $this->flatTwo->setNumberOfRooms(2);
        $this->flatTwo->setAddress('Słoneczna 32');
        $this->flatTwo->setFloor(3);
        $this->flatTwo->setMaxFloor(5);
        $this->flatTwo->setRent(3040);
        $this->flatTwo->setLandlord($this->landlord);

        $this->specialist = new Specialist();
        $this->specialist->setName("Test T.");
        $this->specialist->setProfession("Plumber");
        $this->specialist->addFlat($this->flat);

        $this->landlord->addFlat($this->flat);
        $this->landlord->addFlat($this->flatTwo);
        $this->flat->addSpecialist($this->specialist);
        $this->entityManager->persist($this->flat);
        $this->entityManager->persist($this->flatTwo);
        $this->entityManager->persist($this->landlord);
        $this->entityManager->persist($this->tenant);
        $this->entityManager->persist($this->tenantTwo);
        $this->entityManager->persist($this->specialist);
        $this->entityManager->flush();
    }

    public function testIfLandlordCanSeeSpecialists(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/specialists');
        $this->assertResponseStatusCodeSame(200);

        $flatCard = $crawler->filter('#flat-' . $this->flat->getId());
        $flatHeader = $flatCard->filter('.card-title')->text();

        $this->assertEquals(1, $flatCard->count());
        $this->assertEquals('Testowa 12, 55 m2, 2000zł  ', $flatHeader);

        $specialistCard = $crawler->filter('#specialist-' . $this->specialist->getId());
        $specialistHeader = $specialistCard->filter('.card-title')->text();

        $this->assertEquals(1, $specialistCard->count());
        $this->assertEquals('Test T. Plumber', $specialistHeader);
    }

    public function testIfLandlordCanAddSpecialistWithAllFieldsProvidedAndThenEdit(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/specialists');
        $this->assertResponseStatusCodeSame(200);

        $addNewButton = $crawler->selectLink('Add new')->link();
        $crawler = $this->client->click($addNewButton);

        $this->assertEquals('http://localhost/panel/specialists/new', $crawler->getUri());

        $availableFlatOne = $crawler->filter('.flats-list label')->text();
        $availableFlatTwo = $crawler->filter('.flats-list div:nth-child(2) label')->text();
        $this->assertEquals('Testowa 12, 55m2', $availableFlatOne);
        $this->assertEquals('Słoneczna 32, 25m2', $availableFlatTwo);

        $form = $crawler->filter('form[name="new_specialist_form"]')->form([
            'new_specialist_form[name]' => 'John Nowak',
            'new_specialist_form[profession]' => 'Electrician',
            'new_specialist_form[email]' => 'jnowak@o2.pl',
            'new_specialist_form[phone]' => '432134501',
            'new_specialist_form[address]' => 'Testowa 1, 12-123 Testowo',
            'new_specialist_form[gmb]' => '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d41429.08992279976!2d19.061186100000004!3d49.5351477!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47142559ac6b7865%3A0x40dea8e7a9599026!2sGospoda%20u%20Wandzi%20i%20J%C4%99drusia!5e0!3m2!1sen!2spl!4v1694521517098!5m2!1sen!2spl" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>',
            'new_specialist_form[note]' => 'Additional information',
            'new_specialist_form[flats]' => [
                '0'
            ],
        ]);
        $this->client->submit($form);

        $crawler = $this->client->followRedirect();
        $this->assertMatchesRegularExpression('/http:\/\/localhost\/panel\/specialists\/\d{1,5}/', $crawler->getUri());

        // Check if specialist view is displayed correctly
        $specialistHeader = $crawler->filter('.card-title')->text();
        $specialistContact = $crawler->filter('.specialist-contact')->text();
        $specialistNote = $crawler->filter('.card-text')->text();
        $gmb = $crawler->filter('iframe');
        $flatList = $crawler->filter('ul')->text();

        $this->assertEquals('John Nowak Electrician', $specialistHeader);
        $this->assertEquals('432134501 jnowak@o2.pl Testowa 1, 12-123 Testowo', $specialistContact);
        $this->assertEquals('Additional information', $specialistNote);
        $this->assertEquals('1', $gmb->count());
        $this->assertEquals('Testowa 12, 55m2  ', $flatList);

        $specialistsHubButton = $crawler->selectLink('Specialists hub')->link();
        $crawler = $this->client->click($specialistsHubButton);

        $this->assertEquals('http://localhost/panel/specialists', $crawler->getUri());

        // Check if specialists list is displayed correctly
        $specialistOne = $crawler->filter('#specialist-' . $this->specialist->getId());
        $specialistOneHeader = $specialistOne->filter('.card-title')->text();
        $specialistOneContact = $specialistOne->filter('.specialist-contact')->text();

        $specialistTwoObject = $this->specialistRepository->findOneBy(['email' => 'jnowak@o2.pl']);
        $specialistTwo = $crawler->filter('#specialist-' . $specialistTwoObject->getId());
        $specialistTwoHeader = $specialistTwo->filter('.card-title')->text();
        $specialistTwoContact = $specialistTwo->filter('.specialist-contact')->text();
        $specialistTwoNote = $specialistTwo->filter('.card-text')->text();
        $specialistTwoGmb = $specialistTwo->filter('iframe');

        $this->assertEquals('Test T. Plumber', $specialistOneHeader);
        $this->assertEquals('', $specialistOneContact);

        $this->assertEquals('John Nowak Electrician', $specialistTwoHeader);
        $this->assertEquals('432134501 jnowak@o2.pl Testowa 1, 12-123 Testowo', $specialistTwoContact);
        $this->assertEquals('Additional information', $specialistTwoNote);
        $this->assertEquals('1', $specialistTwoGmb->count());

        $seeMoreButton = $specialistTwo->selectLink('See more')->link();
        $crawler = $this->client->click($seeMoreButton);

        $this->assertEquals('http://localhost/panel/specialists/' . $specialistTwoObject->getId(), $crawler->getUri());

        // Check if edit form works correctly
        $editButton = $crawler->selectLink('Edit')->link();
        $crawler = $this->client->click($editButton);

        $this->assertEquals('http://localhost/panel/specialists/edit/' . $specialistTwoObject->getId(), $crawler->getUri());

        $specialist = $crawler->filter('form[name="new_specialist_form"]')->form();
        $specialistName = $specialist->get('new_specialist_form[name]')->getValue();
        $specialistProfession = $specialist->get('new_specialist_form[profession]')->getValue();
        $specialistEmail = $specialist->get('new_specialist_form[email]')->getValue();
        $specialistPhone = $specialist->get('new_specialist_form[phone]')->getValue();
        $specialistAddress = $specialist->get('new_specialist_form[address]')->getValue();
        $specialistGmb = $specialist->get('new_specialist_form[gmb]')->getValue();
        $specialistNote = $specialist->get('new_specialist_form[note]')->getValue();
        $specialistFlatOneLabel = $crawler->filter('#new_specialist_form_flats_0+label');
        $specialistFlatTwoLabel = $crawler->filter('#new_specialist_form_flats_1+label');
        $specialistFlatOne = $crawler->filter('#new_specialist_form_flats_0');
        $specialistFlatTwo = $crawler->filter('#new_specialist_form_flats_1');

        $this->assertEquals('John Nowak', $specialistName);
        $this->assertEquals('Electrician', $specialistProfession);
        $this->assertEquals('jnowak@o2.pl', $specialistEmail);
        $this->assertEquals('432134501', $specialistPhone);
        $this->assertEquals('Testowa 1, 12-123 Testowo', $specialistAddress);
        $this->assertEquals('https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d41429.08992279976!2d19.061186100000004!3d49.5351477!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47142559ac6b7865%3A0x40dea8e7a9599026!2sGospoda%20u%20Wandzi%20i%20J%C4%99drusia!5e0!3m2!1sen!2spl!4v1694521517098!5m2!1sen!2spl', $specialistGmb);
        $this->assertEquals('Additional information', $specialistNote);
        $this->assertEquals('Testowa 12, 55m2', $specialistFlatOneLabel->text());
        $this->assertEquals('Słoneczna 32, 25m2', $specialistFlatTwoLabel->text());
        $this->assertEquals('checked', $specialistFlatOne->attr('checked'));
        $this->assertEquals(null, $specialistFlatTwo->attr('checked'));

        $form = $crawler->filter('form[name="new_specialist_form"]')->form([
            'new_specialist_form[name]' => 'John Kowalski',
            'new_specialist_form[phone]' => '123123123',
        ]);
        $this->client->submit($form);

        $crawler = $this->client->followRedirect();
        $this->assertMatchesRegularExpression('/http:\/\/localhost\/panel\/specialists\/\d{1,5}/', $crawler->getUri());

        $specialistTwo = $crawler->filter('#specialist-' . $specialistTwoObject->getId());
        $specialistTwoHeader = $specialistTwo->filter('.card-title')->text();
        $specialistTwoContact = $specialistTwo->filter('.specialist-contact')->text();

        $this->assertEquals('John Kowalski Electrician', $specialistTwoHeader);
        $this->assertEquals('123123123 jnowak@o2.pl Testowa 1, 12-123 Testowo', $specialistTwoContact);
    }

    public function testIfLandlordCanNotAddSpecialistWithoutName(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/specialists/new');
        $this->assertResponseStatusCodeSame(200);

        $form = $crawler->filter('form[name="new_specialist_form"]')->form([
            'new_specialist_form[profession]' => 'Electrician',
            'new_specialist_form[flats]' => [
                '0'
            ],
        ]);
        $this->client->submit($form);

        $this->assertEquals('http://localhost/panel/specialists/new', $crawler->getUri());
    }

    public function testIfLandlordCanNotAddSpecialistWithoutProfession(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/specialists/new');
        $this->assertResponseStatusCodeSame(200);

        $form = $crawler->filter('form[name="new_specialist_form"]')->form([
            'new_specialist_form[name]' => 'John Nowak',
            'new_specialist_form[flats]' => [
                '0'
            ],
        ]);
        $this->client->submit($form);

        $this->assertEquals('http://localhost/panel/specialists/new', $crawler->getUri());
    }

    public function testIfLandlordCanNotAddSpecialistWithoutFlat(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/specialists/new');
        $this->assertResponseStatusCodeSame(200);

        $form = $crawler->filter('form[name="new_specialist_form"]')->form([
            'new_specialist_form[name]' => 'John Nowak',
            'new_specialist_form[profession]' => 'Electrician',
        ]);
        $this->client->submit($form);

        $this->assertEquals('http://localhost/panel/specialists/new', $crawler->getUri());
    }

    public function testIfLandlordCanDeleteSpecialist(): void
    {
        $this->client->loginUser($this->landlord);
        $crawler = $this->client->request('GET', '/panel/specialists');
        $this->assertResponseStatusCodeSame(200);

        $specialists = $crawler->filter('.specialist-card')->count();
        $this->assertEquals('1', $specialists);

        $editButton = $crawler->selectLink('See more')->link();
        $crawler = $this->client->click($editButton);

        $deleteButton = $crawler->selectLink('Delete')->link();
        $crawler = $this->client->click($deleteButton);

        $specialists = $crawler->filter('.specialist-card')->count();
        $this->assertEquals('0', $specialists);
    }
}