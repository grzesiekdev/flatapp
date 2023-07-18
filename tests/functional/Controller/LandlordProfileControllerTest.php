<?php

namespace App\Tests\functional\Controller;

use App\Entity\User\Type\Landlord;
use App\Repository\LandlordRepository;
use App\Service\FilesUploader;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LandlordProfileControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private Landlord $landlord;
    private EntityManager $entityManager;
    private KernelInterface $appKernel;
    private LandlordRepository $landlordRepository;
    private array $profilePicturesToDelete;
    private FilesUploader $filesUploader;


    protected function setUp(): void
    {
        $this->client = static::createClient();
        $userPasswordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->appKernel = self::getContainer()->get(KernelInterface::class);
        $this->landlordRepository = self::getContainer()->get(LandlordRepository::class);
        $this->filesUploader = self::getContainer()->get(FilesUploader::class);

        $this->landlord = new Landlord();
        $this->landlord->setName('Jan Kowalski')
            ->setEmail('jkowalski@o2.pl')
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
        $this->entityManager->persist($this->landlord);
        $this->entityManager->flush();

        $this->client->loginUser($this->landlord);

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

    public function testIfLandlordCanAccessProfile(): void
    {
        $crawler = $this->client->request('GET', '/panel/profile/' . $this->landlord->getId());
        $this->assertEquals('http://localhost/panel/profile/' . $this->landlord->getId(), $crawler->getUri());

        $name = $crawler->filter('.profile-name')->text();
        $role = $crawler->filter('.profile-role')->text();
        $image = $crawler->filter('.profile-picture')->attr('src');
        $tableName = $crawler->filter('.profile-table-name')->text();
        $tableDateOfBirth = $crawler->filter('.profile-table-date-of-birth')->text();
        $tableEmail = $crawler->filter('.profile-table-email')->text();

        $this->assertEquals('Jan Kowalski', $name);
        $this->assertEquals('Landlord', $role);
        $this->assertEquals('/uploads/profile_pictures/default-profile-picture.png', $image);
        $this->assertEquals('Jan Kowalski', $tableName);
        $this->assertEquals('01-02-1922', $tableDateOfBirth);
        $this->assertEquals('jkowalski@o2.pl', $tableEmail);
    }

    public function testIfLandlordCanEditProfileAndIfDataFromProfileIsCorrectlyDisplayedInEditForm(): void
    {
        $crawler = $this->client->request('GET', '/panel/profile/' . $this->landlord->getId());
        $this->assertEquals('http://localhost/panel/profile/' . $this->landlord->getId(), $crawler->getUri());

        $link = $crawler->selectLink('Edit')->link();
        $crawler = $this->client->click($link);

        $this->assertEquals('http://localhost/panel/profile/' . $this->landlord->getId() . '/edit', $crawler->getUri());
        $this->assertCount(1, $crawler->filter('form[name="edit_profile_form"]'));

        $name = $crawler->filter('.profile-name')->text();
        $role = $crawler->filter('.profile-role')->text();
        $image = $crawler->filter('.profile-picture')->attr('src');
        $tableEmail = $crawler->filter('.profile-table-email')->text();
        $tableName = $crawler->filter('.profile-table-name input')->attr('value');
        $tableDateOfBirth = $crawler->filter('.profile-table-date-of-birth input')->attr('value');

        $this->assertEquals('Jan Kowalski', $name);
        $this->assertEquals('Landlord', $role);
        $this->assertEquals('/uploads/profile_pictures/default-profile-picture.png', $image);
        $this->assertEquals('Jan Kowalski', $tableName);
        $this->assertEquals('1922-02-01', $tableDateOfBirth);
        $this->assertEquals('jkowalski@o2.pl', $tableEmail);

        $form = $crawler->filter('form[name="edit_profile_form"]')->form([
            'edit_profile_form[name]' => 'Jan Nowak',
            'edit_profile_form[dateOfBirth]' => '2000-05-22',
            'edit_profile_form[phone]' => '123 456 789',
            'edit_profile_form[address]' => 'ul. Testowa, 12-123 Testowo',
            'edit_profile_form[image]' => $this->appKernel->getProjectDir() . '/tests/e2e/fixtures/screen.png',
        ]);
        $crawler = $this->client->submit($form);
        $crawler = $this->client->followRedirect();
        $this->assertEquals('http://localhost/panel/profile/' . $this->landlord->getId(), $crawler->getUri());

        $this->landlord = $this->landlordRepository->findOneBy(['id' => $this->landlord->getId()]);
        $this->profilePicturesToDelete[] = $this->landlord->getImage();

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
        $this->assertEquals('Landlord', $role);
        $this->assertMatchesRegularExpression('/\/uploads\/profile_pictures\/screen-[a-z0-9]{13}\.png/', $image);
        $this->assertEquals('ul. Testowa, 12-123 Testowo', $address);
        $this->assertEquals('Jan Nowak', $tableName);
        $this->assertEquals('22-05-2000', $tableDateOfBirth);
        $this->assertEquals('jkowalski@o2.pl', $tableEmail);
        $this->assertEquals('123 456 789', $tablePhone);
        $this->assertEquals('ul. Testowa, 12-123 Testowo', $tableAddress);
    }

    public function testIfUserCanDeleteProfilePicture(): void
    {
        $crawler = $this->client->request('GET', '/panel/profile/' . $this->landlord->getId());
        $this->assertEquals('http://localhost/panel/profile/' . $this->landlord->getId(), $crawler->getUri());

        $link = $crawler->selectLink('Edit')->link();
        $crawler = $this->client->click($link);

        $this->assertEquals('http://localhost/panel/profile/' . $this->landlord->getId() . '/edit', $crawler->getUri());
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
        $this->assertEquals('http://localhost/panel/profile/' . $this->landlord->getId(), $crawler->getUri());

        $link = $crawler->selectLink('Edit')->link();
        $crawler = $this->client->click($link);

        $crawler = $this->client->request('GET', '/panel/profile/' . $this->landlord->getId() . '/delete-picture');
        $this->assertResponseIsSuccessful();

        $this->landlord = $this->landlordRepository->findOneBy(['id' => $this->landlord->getId()]);
        $this->assertEquals('default-profile-picture.png', $this->landlord->getImage());

        $crawler = $this->client->request('GET', '/panel/profile/' . $this->landlord->getId());
        $this->assertEquals('http://localhost/panel/profile/' . $this->landlord->getId(), $crawler->getUri());

        $image = $crawler->filter('.profile-picture')->attr('src');
        $this->assertEquals('/uploads/profile_pictures/default-profile-picture.png', $image);
    }
}