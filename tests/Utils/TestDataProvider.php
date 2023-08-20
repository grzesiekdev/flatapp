<?php

namespace App\Tests\Utils;

use App\Entity\User\Type\Landlord;
use App\Entity\User\Type\Tenant;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TestDataProvider
{
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function provideUsers(array $tenantsData): array
    {
        $users = [];
        foreach ($tenantsData as $key => $data) {
            if ($data['role'] == 'ROLE_TENANT') {
                $user = new Tenant();
            } else {
                $user = new Landlord();
            }
            $user->setName($data['name'])
                ->setEmail($data['email'])
                ->setPassword($this->userPasswordHasher->hashPassword($user, $data['password']))
                ->setDateOfBirth(new \DateTime($data['dob']))
                ->setRoles([$data['role']])
                ->setImage($data['image']);

            $users[$key] = $user;
        }

        return $users;
    }
}