<?php

namespace App\Tests\functional\Controller\Security;

use App\Entity\Task;
use App\Entity\User\Type\Tenant;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use App\Tests\Utils\TestDataProvider;

class TaskSecurityControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private TestDataProvider $testDataProvider;
    private Tenant $tenant;
    private Tenant $tenantTwo;
    private Task $taskOne;
    private Task $taskTwo;
    private EntityManager $entityManager;
    private KernelInterface $appKernel;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->testDataProvider = self::getContainer()->get('App\Tests\Utils\TestDataProvider');
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->appKernel = self::getContainer()->get(KernelInterface::class);

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
        ];

        $users = $this->testDataProvider->provideUsers($usersData);

        $this->tenant = $users['tenant1'];
        $this->tenantTwo = $users['tenant2'];

        $this->taskOne = new Task();
        $this->taskTwo = new Task();

        $this->taskOne->setDescription("Task 1");
        $this->taskTwo->setDescription("Task 2");

        $this->taskOne->setUser($this->tenant);
        $this->taskTwo->setUser($this->tenantTwo);

        $this->taskOne->setPosition(1);
        $this->taskTwo->setPosition(2);

        $this->tenant->addTask($this->taskOne);
        $this->tenantTwo->addTask($this->taskTwo);

        $this->entityManager->persist($this->tenant);
        $this->entityManager->persist($this->tenantTwo);
        $this->entityManager->persist($this->taskOne);
        $this->entityManager->persist($this->taskTwo);
        $this->entityManager->flush();
    }

    public function testIfTenantCanCheckOwnTask(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/tasks/mark-as-done/' . $this->taskOne->getId());

        $this->assertEquals('http://localhost/panel/tasks/mark-as-done/' . $this->taskOne->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testIfTenantCanNotCheckOtherTenantTask(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/tasks/mark-as-done/' . $this->taskTwo->getId());

        $this->assertEquals('http://localhost/panel/tasks/mark-as-done/' . $this->taskTwo->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

    public function testIfTenantCanDeleteOwnTask(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/tasks/mark-as-done/' . $this->taskOne->getId());

        $this->assertEquals('http://localhost/panel/tasks/mark-as-done/' . $this->taskOne->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testIfTenantCanNotDeleteOtherTenantTask(): void
    {
        $this->client->loginUser($this->tenant);
        $crawler = $this->client->request('GET', '/panel/tasks/delete-task/' . $this->taskTwo->getId());

        $this->assertEquals('http://localhost/panel/tasks/delete-task/' . $this->taskTwo->getId(), $crawler->getUri());
        $this->assertResponseStatusCodeSame(403);
    }

}