<?php

namespace App\Tests\e2e\Form;


use App\Entity\Flat;
use Doctrine\ORM\EntityManagerInterface;
use Facebook\WebDriver\WebDriverDimension;
use PHPUnit\Exception;
use PHPUnit\Framework\Constraint\ExceptionMessage;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;

class TasksControllerTest extends PantherTestCase
{
    private Client $client;
    private KernelInterface $appKernel;
    private EntityManagerInterface $entityManager;

    public function setUp(): void
    {
        $container = static::getContainer();
        $size = new WebDriverDimension(1920, 2980);

        $this->appKernel = $container->get(KernelInterface::class);
        $this->entityManager = $this->appKernel->getContainer()->get('doctrine')->getManager();
        $this->client = static::createPantherClient(['browser' => static::FIREFOX]);
        $this->client->manage()->window()->setSize($size);
    }

    public function testAddingAndDeletingAndCheckingTasks()
    {
        // authenticating user
        $crawler = $this->client->request('GET', '/login');
        $this->client->waitForInvisibility('#spinner');

        $crawler = $this->client->submitForm('Login', [
            '_username' => 'test_env_user@test.pl',
            '_password' => 'test12'
        ]);

        // check if user was logged in correctly and have access to /panel
        $crawler = $this->client->request('GET', '/panel');
        $this->client->waitForInvisibility('#spinner');

        $this->assertSame(self::$baseUri . '/panel', $crawler->getUri());

        $crawler = $this->client->submitForm('Add', [
            'tasks_form[description]' => 'Task 1',
        ]);

        $task = $crawler->filter('.todo-list > div');
        self::assertEquals('Task 1', $task->text());

        $crawler = $this->client->submitForm('Add', [
            'tasks_form[description]' => 'Task 2',
        ]);

        $crawler = $this->client->submitForm('Add', [
            'tasks_form[description]' => 'Task 3',
        ]);

        $task = $crawler->filter('.todo-list > div');
        $taskTwo = $crawler->filter('.todo-list > div:nth-child(2)');
        $taskThree = $crawler->filter('.todo-list > div:nth-child(3)');

        self::assertEquals('Task 1', $task->text());
        self::assertEquals('Task 2', $taskTwo->text());
        self::assertEquals('Task 3', $taskThree->text());

        $deleteOne = $crawler->filter('.todo-list > div a')->link();
        $deleteTwo = $crawler->filter('.todo-list > div:nth-child(2) a')->link();
        $deleteThree = $crawler->filter('.todo-list > div:nth-child(3) a')->link();

        $crawler = $this->client->click($deleteOne);
        $crawler = $this->client->click($deleteTwo);
        $crawler = $this->client->click($deleteThree);

        $task = $crawler->filter('.todo-list > div')->attr('id');
        $this->assertEquals('task-template', $task);

        $crawler = $this->client->submitForm('Add', [
            'tasks_form[description]' => 'Task 1',
        ]);

        $crawler = $this->client->submitForm('Add', [
            'tasks_form[description]' => 'Task 2',
        ]);

        $this->client->executeScript("document.querySelector('.form-check-input').click()");
        $this->client->waitFor('.task');
        $this->client->takeScreenshot('screen.png');

        $isDone = $crawler->filter('.todo-list > div')->attr('class');
        self::assertStringContainsString('crossed-out', $isDone);

        $deleteOne = $crawler->filter('.todo-list > div a')->link();
        $deleteTwo = $crawler->filter('.todo-list > div:nth-child(2) a')->link();
        $crawler = $this->client->click($deleteOne);
        $crawler = $this->client->click($deleteTwo);
    }
}