<?php

namespace App\Tests\functional\Controller;

use App\Entity\Flat;
use App\Entity\User\Type\Landlord;
use App\Entity\User\Type\Tenant;
use App\Repository\LandlordRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class FlatControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private Landlord $landlord;
    private EntityManager $entityManager;
    private KernelInterface $appKernel;
    private LandlordRepository $landlordRepository;
    private Tenant $tenant;
    private Flat $flat;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $userPasswordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->appKernel = self::getContainer()->get(KernelInterface::class);
        $this->landlordRepository = self::getContainer()->get(LandlordRepository::class);

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

        $this->flat = new Flat();
        $this->flat->setArea(55);
        $this->flat->setNumberOfRooms(2);
        $this->flat->setAddress('Testowa 12');
        $this->flat->setFloor(3);
        $this->flat->setMaxFloor(5);
        $this->flat->setRent(2000);
        $this->flat->setLandlord($this->landlord);
        $this->flat->addTenant($this->tenant);

        $this->entityManager->persist($this->flat);
        $this->entityManager->persist($this->tenant);
        $this->entityManager->persist($this->landlord);
        $this->entityManager->flush();
    }

    public function testIfTenantCanAddNewPhotos(): void
    {
        $this->client->loginUser($this->tenant);

        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flat->getId());
        $this->assertEquals('http://localhost/panel/flats/' . $this->flat->getId(), $crawler->getUri());

        $this->assertCount(1, $crawler->filter('form[name="additional_photos_form"]'));

        // add new pictures
        $form = $crawler->filter('form[name="additional_photos_form"]')->form([
            'additional_photos_form[picturesForTenant]' => [$this->appKernel->getProjectDir() . '/tests/e2e/fixtures/screen.png'],
        ]);
        $crawler = $this->client->submit($form);

        // check if the image was added correctly
        $picture1 = $crawler->filter('.pictures-for-tenant div > img')->image()->getUri();
        $this->assertMatchesRegularExpression('/^(.*)\/uploads\/flats\/pictures_for_tenant\/user\d+\/screen-[a-z0-9]{13}\.png/', $picture1);

        // add next picture
        $form = $crawler->filter('form[name="additional_photos_form"]')->form([
            'additional_photos_form[picturesForTenant]' => [$this->appKernel->getProjectDir() . '/tests/e2e/fixtures/screen.png'],
        ]);
        $crawler = $this->client->submit($form);

        // check if the previous image is there, along with new picture
        $picture1 = $crawler->filter('.pictures-for-tenant div > img')->image()->getUri();
        $picture2 = $crawler->filter('.pictures-for-tenant div:nth-child(2) > img')->image()->getUri();
        $this->assertMatchesRegularExpression('/^(.*)\/uploads\/flats\/pictures_for_tenant\/user\d+\/screen-[a-z0-9]{13}\.png/', $picture1);
        $this->assertMatchesRegularExpression('/^(.*)\/uploads\/flats\/pictures_for_tenant\/user\d+\/screen-[a-z0-9]{13}\.png/', $picture2);
    }

    public function testIfLandlordCannotAddNewPhotosFromFlatView(): void
    {
        $this->client->loginUser($this->landlord);

        $crawler = $this->client->request('GET', '/panel/flats/' . $this->flat->getId());
        $this->assertEquals('http://localhost/panel/flats/' . $this->flat->getId(), $crawler->getUri());

        $this->assertCount(0, $crawler->filter('form[name="additional_photos_form"]'));
    }

}