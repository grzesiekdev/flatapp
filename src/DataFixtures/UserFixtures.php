<?php

namespace App\DataFixtures;

use App\Entity\Flat;
use App\Entity\User\Type\Landlord;
use App\Entity\User\Type\Tenant;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $userPasswordHasher;
    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $landlord = new Landlord();
        $landlord->setEmail('test_env_landlord@test.pl');
        $landlord->setName('Test name');
        $landlord->setPassword(
            $this->userPasswordHasher->hashPassword(
                $landlord,
                'test12'
            )
        );
        $landlord->setRoles(['ROLE_LANDLORD']);
        $landlord->setDateOfBirth(new \DateTime('1922-02-01'));

        $tenant = new Tenant();
        $tenant->setEmail('test_env_tenant@test.pl');
        $tenant->setName('Test tenant');
        $tenant->setPassword(
            $this->userPasswordHasher->hashPassword(
                $tenant,
                'test12'
            )
        );
        $tenant->setRoles(['ROLE_TENANT']);
        $tenant->setDateOfBirth(new \DateTime('1922-02-01'));

        $flat = new Flat();
        $flat->setArea(55);
        $flat->setNumberOfRooms(2);
        $flat->setAddress('Testowa 12');
        $flat->setFloor(3);
        $flat->setMaxFloor(5);
        $flat->setRent(2000);
        $flat->setLandlord($landlord);
        $flat->addTenant($tenant);

        $manager->persist($landlord);
        $manager->persist($tenant);
        $manager->persist($flat);
        $manager->flush();
    }
}
