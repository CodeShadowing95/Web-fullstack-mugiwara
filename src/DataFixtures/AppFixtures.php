<?php
namespace App\DataFixtures;

use App\Entity\Farm;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AppFixtures extends Fixture
{
    private $faker;

    public function __construct()
    {
        $this->faker = Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $farm = new Farm();
            $farm->setName($this->faker->company);
            $farm->setAddress($this->faker->address);
            $farm->setDescription($this->faker->text(20));
            $farm->setStatus("on");
            
            $manager->persist($farm);
        }

        $manager->flush();
    }
}