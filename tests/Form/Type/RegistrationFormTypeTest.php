<?php

namespace App\Tests\Form\Type;

use App\Entity\User\User;
use App\Form\RegistrationFormType;
use App\Entity\User\Type\Landlord;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationFormTypeTest extends KernelTestCase
{

    private UserPasswordHasherInterface $userPasswordHasher;
    private EntityManager $entityManager;
    private \Symfony\Component\DependencyInjection\ContainerInterface|\Symfony\Component\DependencyInjection\Container $container;
    protected function setUp(): void
    {
        $kernel = self::bootKernel([
            'debug' => false
        ]);
        $this->container = static::getContainer();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        $this->userPasswordHasher = $this->container->get(UserPasswordHasherInterface::class);

    }

    public function testSubmitValidData()
    {
        $formData = [
            'name' => 'Jan Kowalski',
            'email' => 'jkowalski@o2.pl',
            'plainPassword' => 'test12',
            'dateOfBirth' => new \DateTime('1922-02-01'),
            'address' => 'testowa 12, 14-460 testowo',
            'image' => '/example/path/img.jpg',
            'phone' => '123123123',
            'roles' => 'landlord'
        ];

        $model = new Landlord();

        $formFactory = $this->container->get('form.factory');
        $form = $formFactory->create(RegistrationFormType::class, $model);

        $expected = new Landlord();
        $expected->setName($formData['name'])
            ->setEmail($formData['email'])
            ->setPassword(
                $this->userPasswordHasher->hashPassword(
                    $expected,
                    $formData['plainPassword']
                )
            )
            ->setDateOfBirth($formData['dateOfBirth'])
            ->setAddress($formData['address'])
            ->setImage($formData['image'])
            ->setPhone($formData['phone'])
            ->setRoles(['landlord']);

        $form->submit($formData);


        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $model);

    }
}