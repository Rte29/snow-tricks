<?php

namespace App\DataFixtures;

use Faker;
use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {

        $faker = Faker\Factory::create('fr_FR');

        for ($nbUsers = 1; $nbUsers < 30; $nbUsers++) {
            $user = new User();
            $user->setEmail($faker->email);
            $user->setPassword('$2y$13$UzyoWlNnEP27zZYJCGO6Y.TiHYa4Ye4chC69ALx/hj.WBpDFW4Xma');
            $user->setUserName($faker->username);
            $randNb = $faker->numberBetween(0, 1);
            $user->setActivated($randNb);
            $token = bin2hex(random_bytes(16));
            $user->setToken($token);
            $manager->persist($user);

            $this->addReference('user_' . $nbUsers, $user);
        }
        $manager->flush($user);
    }
}
