<?php

namespace App\DataFixtures;

use Faker;
use App\Entity\Comment;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;


class CommentFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr_FR');

        for ($nbComments = 1; $nbComments < 15; $nbComments++) {
            $user = $this->getReference('user_' . $faker->numberBetween(1, 29));
            $category = $this->getReference('category_' . $faker->numberBetween(1, 5));
            $figure = $this->getReference('figure_' . $faker->numberBetween(1, 14));
            $comment = new Comment();
            $comment->setUser($user);
            $comment->setFigure($figure);
            $comment->setCreatedAt(new \DateTimeImmutable());
            $comment->setContent($faker->realText(50));
            $manager->persist($comment);
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            CategoryFixtures::class,
            UserFixtures::class,
            FigureFixtures::class
        ];
    }
}
