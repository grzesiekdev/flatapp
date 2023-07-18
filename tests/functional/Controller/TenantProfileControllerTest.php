<?php

namespace App\Tests\functional\Controller;

use App\Entity\Flat;
use App\Entity\User\Type\Landlord;
use App\Entity\User\Type\Tenant;
use App\Repository\FlatRepository;
use App\Repository\TenantRepository;
use App\Service\FilesUploader;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Ulid;

class TenantProfileControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private Tenant $tenant;
    private Landlord $landlord;
    private EntityManager $entityManager;
    private KernelInterface $appKernel;
    private TenantRepository $tenantRepository;
    private array $profilePicturesToDelete;
    private FilesUploader $filesUploader;
    private FlatRepository $flatRepository;
    private Flat $flat;
    private Ulid $invitationCode;


    protected function setUp(): void
    {
        $this->client = static::createClient();
        $userPasswordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->appKernel = self::getContainer()->get(KernelInterface::class);
        $this->tenantRepository = self::getContainer()->get(TenantRepository::class);
        $this->filesUploader = self::getContainer()->get(FilesUploader::class);
        $this->flatRepository = self::getContainer()->get(FlatRepository::class);

        $this->tenant = new Tenant();
        $this->tenant->setName('Jan Kowalski')
            ->setEmail('jkowalski@tenant.pl')
            ->setPassword(
                $userPasswordHasher->hashPassword(
                    $this->tenant,
                    'test12'
                )
            )
            ->setDateOfBirth(new \DateTime('1922-02-01'))
            ->setRoles(['ROLE_TENANT'])
            ->setImage('default-profile-picture.png')
        ;
        $this->landlord = new Landlord();
        $this->landlord->setName('Jan Kowalski')
            ->setEmail('jkowalski@landlord.pl')
            ->setPassword(
                $userPasswordHasher->hashPassword(
                    $this->landlord,
                    'test12'
                )
            )
            ->setDateOfBirth(new \DateTime('1922-02-01'))
            ->setRoles(['ROLE_LANDLORD'])
            ->setImage('default-profile-picture.png')
        ;

        $this->invitationCode = new Ulid();
        $this->flat = new Flat();
        $this->flat->setArea(55);
        $this->flat->setNumberOfRooms(2);
        $this->flat->setAddress('Testowa 12');
        $this->flat->setFloor(3);
        $this->flat->setMaxFloor(5);
        $this->flat->setRent(2000);
        $this->flat->setInvitationCode($this->invitationCode);
        $this->flat->setLandlord($this->landlord);

        $this->entityManager->persist($this->flat);
        $this->entityManager->persist($this->landlord);
        $this->entityManager->persist($this->tenant);
        $this->entityManager->flush();

        $this->client->loginUser($this->tenant);

        $this->profilePicturesToDelete = [];
    }

    public function tearDown(): void
    {
        $path = self::getContainer()->getParameter('profile_pictures');
        foreach ($this->profilePicturesToDelete as $picture) {
            $this->filesUploader->deleteFile($path . '/' . $picture);
        }
        parent::tearDown();
    }

    public function testIfTenantCanAccessProfile(): void
    {
        $crawler = $this->client->request('GET', '/panel/profile/' . $this->tenant->getId());
        $this->assertEquals('http://localhost/panel/profile/' . $this->tenant->getId(), $crawler->getUri());

        $name = $crawler->filter('.profile-name')->text();
        $role = $crawler->filter('.profile-role')->text();
        $image = $crawler->filter('.profile-picture')->attr('src');
        $tableName = $crawler->filter('.profile-table-name')->text();
        $tableDateOfBirth = $crawler->filter('.profile-table-date-of-birth')->text();
        $tableEmail = $crawler->filter('.profile-table-email')->text();

        $this->assertEquals('Jan Kowalski', $name);
        $this->assertEquals('Tenant', $role);
        $this->assertEquals('/uploads/profile_pictures/default-profile-picture.png', $image);
        $this->assertEquals('Jan Kowalski', $tableName);
        $this->assertEquals('01-02-1922', $tableDateOfBirth);
        $this->assertEquals('jkowalski@tenant.pl', $tableEmail);
    }

    public function testIfTenantCanEditProfileAndIfDataFromProfileIsCorrectlyDisplayedInEditForm(): void
    {
        $crawler = $this->client->request('GET', '/panel/profile/' . $this->tenant->getId());
        $this->assertEquals('http://localhost/panel/profile/' . $this->tenant->getId(), $crawler->getUri());

        $link = $crawler->selectLink('Edit')->link();
        $crawler = $this->client->click($link);

        $this->assertEquals('http://localhost/panel/profile/' . $this->tenant->getId() . '/edit', $crawler->getUri());
        $this->assertCount(1, $crawler->filter('form[name="edit_profile_form"]'));

        $name = $crawler->filter('.profile-name')->text();
        $role = $crawler->filter('.profile-role')->text();
        $image = $crawler->filter('.profile-picture')->attr('src');
        $tableEmail = $crawler->filter('.profile-table-email')->text();
        $tableName = $crawler->filter('.profile-table-name input')->attr('value');
        $tableDateOfBirth = $crawler->filter('.profile-table-date-of-birth input')->attr('value');

        $this->assertEquals('Jan Kowalski', $name);
        $this->assertEquals('Tenant', $role);
        $this->assertEquals('/uploads/profile_pictures/default-profile-picture.png', $image);
        $this->assertEquals('Jan Kowalski', $tableName);
        $this->assertEquals('1922-02-01', $tableDateOfBirth);
        $this->assertEquals('jkowalski@tenant.pl', $tableEmail);

        $form = $crawler->filter('form[name="edit_profile_form"]')->form([
            'edit_profile_form[name]' => 'Jan Nowak',
            'edit_profile_form[dateOfBirth]' => '2000-05-22',
            'edit_profile_form[phone]' => '123 456 789',
            'edit_profile_form[address]' => 'ul. Testowa, 12-123 Testowo',
            'edit_profile_form[image]' => $this->appKernel->getProjectDir() . '/tests/e2e/fixtures/screen.png',
        ]);
        $crawler = $this->client->submit($form);
        $crawler = $this->client->followRedirect();
        $this->assertEquals('http://localhost/panel/profile/' . $this->tenant->getId(), $crawler->getUri());

        $this->tenant = $this->tenantRepository->findOneBy(['id' => $this->tenant->getId()]);
        $this->profilePicturesToDelete[] = $this->tenant->getImage();

        $name = $crawler->filter('.profile-name')->text();
        $role = $crawler->filter('.profile-role')->text();
        $image = $crawler->filter('.profile-picture')->attr('src');
        $address = $crawler->filter('.profile-address')->text();
        $tableName = $crawler->filter('.profile-table-name')->text();
        $tableDateOfBirth = $crawler->filter('.profile-table-date-of-birth')->text();
        $tableEmail = $crawler->filter('.profile-table-email')->text();
        $tablePhone = $crawler->filter('.profile-table-phone')->text();
        $tableAddress = $crawler->filter('.profile-table-address')->text();

        $this->assertEquals('Jan Nowak', $name);
        $this->assertEquals('Tenant', $role);
        $this->assertMatchesRegularExpression('/\/uploads\/profile_pictures\/screen-[a-z0-9]{13}\.png/', $image);
        $this->assertEquals('ul. Testowa, 12-123 Testowo', $address);
        $this->assertEquals('Jan Nowak', $tableName);
        $this->assertEquals('22-05-2000', $tableDateOfBirth);
        $this->assertEquals('jkowalski@tenant.pl', $tableEmail);
        $this->assertEquals('123 456 789', $tablePhone);
        $this->assertEquals('ul. Testowa, 12-123 Testowo', $tableAddress);
    }

    public function testIfUserCanDeleteProfilePicture(): void
    {
        $crawler = $this->client->request('GET', '/panel/profile/' . $this->tenant->getId());
        $this->assertEquals('http://localhost/panel/profile/' . $this->tenant->getId(), $crawler->getUri());

        $link = $crawler->selectLink('Edit')->link();
        $crawler = $this->client->click($link);

        $this->assertEquals('http://localhost/panel/profile/' . $this->tenant->getId() . '/edit', $crawler->getUri());
        $this->assertCount(1, $crawler->filter('form[name="edit_profile_form"]'));

        $form = $crawler->filter('form[name="edit_profile_form"]')->form([
            'edit_profile_form[name]' => 'Jan Nowak',
            'edit_profile_form[dateOfBirth]' => '2000-05-22',
            'edit_profile_form[phone]' => '123 456 789',
            'edit_profile_form[address]' => 'ul. Testowa, 12-123 Testowo',
            'edit_profile_form[image]' => $this->appKernel->getProjectDir() . '/tests/e2e/fixtures/screen.png',
        ]);
        $crawler = $this->client->submit($form);
        $crawler = $this->client->followRedirect();
        $this->assertEquals('http://localhost/panel/profile/' . $this->tenant->getId(), $crawler->getUri());

        $link = $crawler->selectLink('Edit')->link();
        $crawler = $this->client->click($link);

        $crawler = $this->client->request('GET', '/panel/profile/' . $this->tenant->getId() . '/delete-picture');
        $this->assertResponseIsSuccessful();

        $this->tenant = $this->tenantRepository->findOneBy(['id' => $this->tenant->getId()]);
        $this->assertEquals('default-profile-picture.png', $this->tenant->getImage());

        $crawler = $this->client->request('GET', '/panel/profile/' . $this->tenant->getId());
        $this->assertEquals('http://localhost/panel/profile/' . $this->tenant->getId(), $crawler->getUri());

        $image = $crawler->filter('.profile-picture')->attr('src');
        $this->assertEquals('/uploads/profile_pictures/default-profile-picture.png', $image);
    }

    public function testIfTenantCanAddNewFlatWithInvitationCodeInProfile(): void
    {
        $crawler = $this->client->request('GET', '/panel/profile/' . $this->tenant->getId());
        $this->assertEquals('http://localhost/panel/profile/' . $this->tenant->getId(), $crawler->getUri());

        $form = $crawler->filter('form[name="invitation_code_form"]')->form([
            'invitation_code_form[code]' => $this->invitationCode->toBase32(),
        ]);
        $crawler = $this->client->submit($form);
        $this->assertEquals('http://localhost/panel/profile/' . $this->tenant->getId(), $crawler->getUri());

        $flatInfo = $crawler->filter('.flat-info-header')->text();
        $tenantSince = $crawler->filter('.tenant-since-info')->text();
        $alert = $crawler->filter('.alert-success')->text();
        $currentDate = new \DateTime('now');

        $this->assertEquals('Testowa 12, 55m2, 2000zł', $flatInfo);
        $this->assertEquals('Tenant since ' . $currentDate->format('d-m-Y'), $tenantSince);
        $this->assertEquals('Flat added successfully', $alert);
    }

    public function testIfTenantCanNotAddNewFlatWithCodeInInvalidFormat(): void
    {
        $crawler = $this->client->request('GET', '/panel/profile/' . $this->tenant->getId());
        $this->assertEquals('http://localhost/panel/profile/' . $this->tenant->getId(), $crawler->getUri());

        $form = $crawler->filter('form[name="invitation_code_form"]')->form([
            'invitation_code_form[code]' => 'invalid code',
        ]);
        $crawler = $this->client->submit($form);
        $this->assertEquals('http://localhost/panel/profile/' . $this->tenant->getId(), $crawler->getUri());

        $alert = $crawler->filter('.alert-danger')->text();

        $this->assertEquals('The code is not in a valid format.', $alert);
    }

    public function testIfTenantCanNotAddNewFlatWithIncorrectCode(): void
    {
        $crawler = $this->client->request('GET', '/panel/profile/' . $this->tenant->getId());
        $this->assertEquals('http://localhost/panel/profile/' . $this->tenant->getId(), $crawler->getUri());

        $form = $crawler->filter('form[name="invitation_code_form"]')->form([
            'invitation_code_form[code]' => '01H5MWJCY8VHH7W57W8VBNGJC9',
        ]);
        $crawler = $this->client->submit($form);
        $crawler = $this->client->followRedirect();

        $this->assertEquals('http://localhost/panel/profile/' . $this->tenant->getId(), $crawler->getUri());

        $alert = $crawler->filter('.alert-danger')->text();

        $this->assertEquals('Invalid invitation code.', $alert);
    }

    public function testIfTenantCanNotAddNewFlatWithEmptyCode(): void
    {
        $crawler = $this->client->request('GET', '/panel/profile/' . $this->tenant->getId());
        $this->assertEquals('http://localhost/panel/profile/' . $this->tenant->getId(), $crawler->getUri());

        $form = $crawler->filter('form[name="invitation_code_form"]')->form([
            'invitation_code_form[code]' => '',
        ]);
        $crawler = $this->client->submit($form);
        $crawler = $this->client->followRedirect();

        $this->assertEquals('http://localhost/panel/profile/' . $this->tenant->getId(), $crawler->getUri());

        $alert = $crawler->filter('.alert-danger')->text();

        $this->assertEquals('Please provide an invitation code.', $alert);
    }
}