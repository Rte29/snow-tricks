<?php

namespace App\DataFixtures;

use Faker;
use App\Entity\Media;
use App\Entity\Figure;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;


class FigureFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr_FR');

        for ($nbFigures = 1; $nbFigures < 15; $nbFigures++) {
            $user = $this->getReference('user_' . $faker->numberBetween(1, 29));
            $category = $this->getReference(('category_' . $faker->numberBetween(1, 5)));
            $figure = new Figure();
            $figure->setUser($user);
            $figure->setCategory($category);
            $figure->setTitle("Titre de l'article n°$nbFigures");
            $figure->setCreatedAt(new \DateTimeImmutable());
            $title = $figure->getTitle();
            $slugger = new AsciiSlugger();
            $slug = strtolower($slugger->slug($title, '-'));
            $figure->setSlug($slug);
            $figure->setDescription($faker->realText(400));

            $img = [
                1 => [
                    'url' => '0e1c1ae17f16746d2c6d5883d59c96e1.jpg',
                ],
                2 => [
                    'url' => '1ac6889382262fe7f77f49cce9497880.jpg',
                ],
                3 => [
                    'url' => '1e20ab5d967028d479b64cedb1d9c247.jpg',
                ],
                4 => [
                    'url' => '4fb31170d03c422ecf5b399a22c6be50.jpg',
                ],
                5 => [
                    'url' => '5ab4e4304bfdb4a7ca03e8f540dabcfe.jpg',
                ],
                6 => [
                    'url' => '5e5a4d5c8f8f4e93bf09b250f67a046e.jpg',
                ],
                7 => [
                    'url' => '7aaa3ce23d78c4858d4b9be0577d5115.jpg',
                ],
                8 => [
                    'url' => '7d0dc0c6aaafa07a01adf23be1b887e4.jpg',
                ],
                9 => [
                    'url' => '7f33247d72fcd77855e2e938f4f29aaa.jpg',
                ],
                10 => [
                    'url' => '8b7fe8713a6352636dcebe10918be11c.jpg',
                ],


            ];

            for ($nbImage = 1; $nbImage < 4; $nbImage++) {
                $imgKey = array_rand($img, 1);
                $image = new Media();
                $image->setUrl($img[$imgKey]['url']);
                $randomNb = $faker->numberBetween(0, 1);
                $image->setMain($randomNb);
                $image->setImage(true);
                $figure->addMedium(($image));
            }

            $vdo = [
                1 => ['url' => 'e7SKudIEvU0',],
                2 => ['url' => '6yA3XqjTh_w',],
                3 => ['url' => 'cuaJlr1DTMk',],
            ];

            $videoKey = array_rand($vdo, 1);
            $video = new Media();
            $video->setUrl($vdo[$videoKey]['url']);
            $video->setMain(false);
            $video->setImage(false);
            $figure->addMedium(($video));


            $manager->persist($figure);
            $this->addReference('figure_' . $nbFigures, $figure);

            $manager->flush();
        }
    }

    public function getDependencies()
    {
        return [
            CategoryFixtures::class,
            UserFixtures::class
        ];
    }
}
