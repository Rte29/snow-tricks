<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserIsEnabledChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user)
    {
        if (!$user instanceof User) {
            return;
        }
        //me permet de verifier si l'utilisateur est active doc https://symfony.com/doc/current/security/user_checkers.html
        if (!$user->isActivated()) {

            throw new CustomUserMessageAccountStatusException('Votre compte n\'est pas activé, verifié votre mail');
        }
    }
    public function checkPostAuth(UserInterface $user)
    {

        // TODO: Implement checkPostAuth() method.
    }
}
