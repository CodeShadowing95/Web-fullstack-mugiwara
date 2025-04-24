<?php
namespace App\DataFixtures;

use App\Entity\Farm;
use App\Entity\Product;
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
        $products = [];
        for ($i = 0; $i <= 10; $i++) {
            $product = new Product();
            $product->setName($this->faker->word);
            $product->setQuantity($this->faker->numberBetween(1, 50));
            $product->setStatus("on");
            $manager->persist($product);
            $products[] = $product;
        }

        for ($i = 0; $i <= 10; $i++) {
            $farm = new Farm();
            $farm->setName($this->faker->company);
            $farm->setAddress($this->faker->address);
            $farm->setDescription($this->faker->text(20));
            $farm->setStatus("on");
            
            // Associer chaque ferme à un produit et vice versa
            $product = array_shift($products);
            $farm->addProduct($product);
            
            $manager->persist($farm);
        }

        $manager->flush();
    }
}