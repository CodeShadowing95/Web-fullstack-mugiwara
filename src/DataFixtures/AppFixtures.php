<?php
namespace App\DataFixtures;

use App\Entity\FarmType;
use Faker\Factory;
use App\Entity\Farm;
use App\Entity\User;
use App\Entity\Product;
use App\Entity\Persona;
use App\Entity\MediaType;
use App\Entity\ProductCategory;
use App\Entity\FarmUser;
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
        // Créer les catégories de produits
        $categories = [];
        $categoryNames = ['Fruits', 'Légumes', 'Viandes', 'Produits laitiers', 'Céréales'];
        foreach ($categoryNames as $name) {
            $category = new ProductCategory();
            $category->setName($name);
            $category->setDescription($this->faker->text(100));
            $manager->persist($category);
            $categories[] = $category;
        }

        $products = [];
        for ($i = 0; $i <= 10; $i++) {
            $product = new Product();
            $product->setName($this->faker->word);
            $product->setQuantity($this->faker->numberBetween(1, 50));
            $product->setUnitPrice($this->faker->randomFloat(2, 3,100));
            $product->setPrice($this->faker->randomFloat(2,3,100));
            $randomCategory = $categories[array_rand($categories)];
            $product->addCategory($randomCategory);
            $product->setStatus("on");
            // Assigner une catégorie aléatoire au produit
            $manager->persist($product);
            $products[] = $product;
        }

        $users = [];
        $usersForFarm = [];

        $user = new User();
        $password = $this->userPasswordHasher->hashPassword($user, "password");
        $user->setUuid(1)
            ->setPassword($password)
            ->setRoles(['ROLE_ADMIN']);
        $manager->persist($user);
        $users[] = $user;
        $usersForFarm[] = $user;
        for($i=0; $i<=10; $i++) {
            $user = new User();
            $password = $this->userPasswordHasher->hashPassword($user, $this->faker->password(2, 6));
            $user->setUuid($this->faker->uuid)
                ->setPassword($password)
                ->setRoles(['ROLE_USER']);
            $users[] = $user;
            $usersForFarm[] = $user;
            $manager->persist($user);
        }

        // farm types
        $farmTypes = [];
        for ($i = 0; $i <= 5; $i++) {
            $farmType = new FarmType();
            $farmType->setName($this->faker->word);
            $farmType->setDescription($this->faker->text(20));
            $manager->persist($farmType);
            $farmTypes[] = $farmType;
        }

        for ($i = 0; $i <= 10; $i++) {
            $farm = new Farm();
            $farm->setName($this->faker->company);
            $farm->setAddress($this->faker->address);
            $farm->setZipCode($this->faker->postcode);
            $farm->setCity($this->faker->city);
            $farm->setDescription($this->faker->text(20));
            $farm->setStatus("on");

            // Associer chaque ferme à un produit et vice versa
            $product = array_shift($products);
            $farm->addProduct($product);
            $farm->addType($farmTypes[array_rand($farmTypes)]);
            $farmUser = new FarmUser();
            $farmUser->setUser($usersForFarm[$i]);
            $farmUser->setFarm($farm);
            $farmUser->setRole($this->faker->randomElement(['owner', 'manager', 'employee']));
            $manager->persist($farmUser);

            $manager->persist($farm);
        }

        // Fixtures pour MediaType
        $mediaTypes = [];
        for ($i = 0; $i <= 5; $i++) {
            $mediaType = new MediaType();
            $mediaType->setName($this->faker->word);
            $mediaType->setSlug($this->faker->slug);
            $manager->persist($mediaType);
            $mediaTypes[] = $mediaType;
        }

        // Fixtures pour Persona
        $personaIndex = 0;
        foreach ($users as $user) {
            $persona = new Persona();
            $persona->setFirstName($this->faker->firstName);
            $persona->setLastName($this->faker->lastName);
            $persona->setPhoneNumber($this->faker->phoneNumber);
            $persona->setAddress($this->faker->address);
            $persona->setZipCode($this->faker->postcode);
            $persona->setCity($this->faker->city);
            if ($personaIndex === 0) {
                $persona->setEmail('test@test.com');
            } else {
                $persona->setEmail($this->faker->email);
            }
            $persona->setBirthDate($this->faker->dateTimeBetween('-60 years', '-18 years'));
            $persona->setGender($this->faker->randomElement(['male', 'female']));
            $persona->setUser($user);
            $manager->persist($persona);
            $personaIndex++;
        }

        $manager->flush();
    }
}
