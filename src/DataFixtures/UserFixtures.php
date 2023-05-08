<?php

namespace App\DataFixtures;

use App\Entity\User\User;
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
        $user = new User();
        $user->setEmail('test@test.pl');
        $user->setName('Test name');
        $user->setPassword(
            $this->userPasswordHasher->hashPassword(
                $user,
                'test12'
            )
        );
        $user->setDateOfBirth(new \DateTime('1922-02-01'));


        $manager->persist($user);
        $manager->flush();
    }
}
