<?php

namespace App\Security;

use App\Entity\Task;
use App\Entity\User\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TasksVoter extends Voter
{
    const CHECK = 'check';
    const DELETE = 'delete';

    private UserRepository $userRepository;
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {

        if (!in_array($attribute, [self::CHECK, self::DELETE])) {
            return false;
        }

        if (!$subject instanceof Task) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }

        /** @var User $user */
        $task = $subject;

        return match($attribute) {
            self::CHECK => $this->canCheck($task, $user),
            self::DELETE => $this->canDelete($task, $user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    private function canCheck(Task $task, User $loggedInUser): bool
    {
        // If user can delete task, he can also check it
        return $this->canDelete($task, $loggedInUser);
    }

    private function canDelete(Task $task, User $loggedInUser): bool
    {
        if (in_array($task, $loggedInUser->getTasks()->toArray()))
        {
            return true;
        }
        return false;
    }

}