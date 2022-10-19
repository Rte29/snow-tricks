<?php

namespace App\DataFixtures;

use Faker;
use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {

        $faker = Faker\Factory::create('fr_FR');

        for ($nbUsers = 1; $nbUsers < 30; $nbUsers++) {
            $user = new User();
            $user->setEmail($faker->email);
            $user->setPassword('azerty');
            $user->setUserName($faker->username);
            $user->setActivated(false);
            $token = bin2hex(random_bytes(16));
            $user->setToken($token);
            $manager->persist($user);

            $this->addReference('user_' . $nbUsers, $user);
        }
        $manager->flush($user);
    }
}
