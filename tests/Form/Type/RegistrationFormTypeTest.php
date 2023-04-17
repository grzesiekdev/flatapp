<?php

namespace App\Tests\Form\Type;

use App\Form\RegistrationFormType;
use App\Entity\User\Type\Landlord;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationFormTypeTest extends KernelTestCase
{

    private UserPasswordHasherInterface $userPasswordHasher;
    private EntityManager $entityManager;
    private array $formData = [];
    private ?object $formFactory;
    private Landlord $expected;
    private $projectDir;
    protected function setUp(): void
    {
        $kernel = self::bootKernel([
            'debug' => false
        ]);
        $container = static::getContainer();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        $this->userPasswordHasher = $container->get(UserPasswordHasherInterface::class);
        $this->projectDir = $container->getParameter('kernel.project_dir');

        # Data on which we will run tests and assertions
        $this->formData = [
            'name' => 'Jan Kowalski',
            'email' => 'jkowalski@o2.pl',
            'plainPassword' => [
                'first' => 'test12',
                'second' => 'test12',
            ],
            'dateOfBirth' => new \DateTime('1922-02-01'),
            'address' => 'testowa 12, 14-460 testowo',
            'image' => new UploadedFile(
                $this->projectDir . '/public/img/logo.png',
                'logo.png',
                'image/png',
                null,
                true
            ),
            'phone' => '123123123',
            'roles' => 'landlord'
        ];

        $this->formFactory = $container->get('form.factory');

        # Preparing expected Landlord model
        $this->expected = new Landlord();
        $this->expected->setName($this->formData['name'])
            ->setEmail($this->formData['email'])
            ->setPassword(
                $this->userPasswordHasher->hashPassword(
                    $this->expected,
                    $this->formData['plainPassword']['first']
                )
            )
            ->setDateOfBirth($this->formData['dateOfBirth'])
            ->setAddress($this->formData['address'])
            ->setImage($this->formData['image'])
            ->setPhone($this->formData['phone'])
            ->setRoles(['landlord']);
    }

    public function testSubmitValidData()
    {
        $model = new Landlord();
        $form = $this->formFactory->create(RegistrationFormType::class, $model, [
            'csrf_protection' => false,
        ]);

        # It is necessary to set these values manually due to form constrains
        $this->formData['dateOfBirth'] = $this->formData['dateOfBirth']->format('Y-m-d');
        $model->setPassword(
            $this->userPasswordHasher->hashPassword(
                $model,
                $this->formData['plainPassword']['first']
            )
        );

        $form->submit($this->formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($this->expected->getName(), $model->getName());
        $this->assertEquals($this->expected->getEmail(), $model->getEmail());
        $this->assertEquals($this->expected->getDateOfBirth(), $model->getDateOfBirth());
        $this->assertEquals($this->expected->getAddress(), $model->getAddress());
        $this->assertEquals($this->expected->getImage(), $model->getImage());
        $this->assertEquals($this->expected->getPhone(), $model->getPhone());
        $this->assertEquals($this->expected->getRoles(), $model->getRoles());

        # Because password hash is always different, we only check if submitted password is not null
        $this->assertNotNull($model->getPassword());
    }
}