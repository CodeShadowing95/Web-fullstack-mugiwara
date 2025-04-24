<?php
namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Farm;
use App\Entity\User;
use App\Entity\Product;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $faker;
    private $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->faker = Factory::create('fr_FR');
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $products = [];
        for ($i = 0; $i <= 10; $i++) {
            $product = new Product();
            $product->setName($this->faker->word);
            $product->setQuantity($this->faker->numberBetween(1, 50));
            $product->setUnitPrice($this->faker->randomFloat(2, 3,100));
            $product->setPrice($this->faker->randomFloat(2,3,100));
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

        $users = [];

        $user = new User();
        $password = $this->userPasswordHasher->hashPassword($user, "password");
        $user->setUuid(1)
            ->setPassword($password)
            ->setRoles(['ROLE_ADMIN']);
        $manager->persist($user);
        $users[] = $user;
        for($i=0; $i<=10; $i++) {
            $user = new User();
            $password = $this->userPasswordHasher->hashPassword($user, $this->faker->password(2, 6));
            $user->setUuid($this->faker->uuid)
                ->setPassword($password)
                ->setRoles(['ROLE_USER']);
            // $user->setEmail($this->faker->name() . '@' . $password);
            $users[] = $user;
            $manager->persist($user);
        }

        $manager->flush();
    }
}