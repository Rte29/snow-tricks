<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;



class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $category = [
            1 => [
                'figureCategory' => 'Les grabs',
            ],
            2 => [
                'figureCategory' => 'Les rotations',
            ],
            3 => [
                'figureCategory' => 'Les flips',
            ],
            4 => [
                'figureCategory' => 'Les slides',
            ],
            5 => [
                'figureCategory' => 'Old School',
            ],

        ];
        foreach ($category as $key => $value) {
            $category = new Category();
            $category->setFigureCategory($value['figureCategory']);

            $manager->persist($category);

            $this->addReference('category_' . $key, $category);
        }
        $manager->flush();
    }
}
