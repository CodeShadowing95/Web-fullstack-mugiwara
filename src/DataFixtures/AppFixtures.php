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
use App\Entity\Unity;

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
        $categoriesData = [
            [
                "categorie" => "Fruits & Légumes de saison",
                "children" => [
                    "Produits de saison",
                    "Légumes anciens",
                    "Herbes fraîches",
                    "Fruits"
                ]
            ],
            [
                "categorie" => "Viandes & Produits Animaux",
                "children" => [
                    "Viande",
                    "Oeufs",
                    "Produits transformés",
                    "Volaille prête à cuire"
                ]
            ],
            [
                "categorie" => "Produits Laitiers",
                "children" => [
                    "Lait cru ou pasteurisé",
                    "Fromages",
                    "Yaourts artisanaux",
                    "Beurre, crème"
                ]
            ],
            [
                "categorie" => "Boulangerie & Pâtisserie",
                "children" => [
                    "Pain au levain",
                    "Brioche",
                    "Cookies",
                    "Tarte rustique"
                ]
            ],
            [
                "categorie" => "Produits Transformés",
                "children" => [
                    "Confiture",
                    "Sauce tomate",
                    "Farine"
                ]
            ],
            [
                "categorie" => "Plantes & Jardin",
                "children" => [
                    "Basilic",
                    "Fleurs séchées",
                    "Compost",
                    "Tomates cerise (plant)"
                ]
            ],
            [
                "categorie" => "Boissons Artisanales",
                "children" => [
                    "Jus de pomme",
                    "Cidre",
                    "Bière blonde",
                    "Tisane"
                ]
            ],
            [
                "categorie" => "Produits Artisanaux / Non-Alimentaires",
                "children" => [
                    "Savon",
                    "Baume",
                    "Bougie",
                    "Tissu brodé"
                ]
            ]
        ];

        foreach ($categoriesData as $categoryData) {
            $parentCategory = new ProductCategory();
            $parentCategory->setName($categoryData["categorie"]);
            $parentCategory->setDescription($this->faker->text(100));
            $manager->persist($parentCategory);
            $categories[] = $parentCategory;

            // Créer les catégories enfants
            foreach ($categoryData["children"] as $childName) {
                $childCategory = new ProductCategory();
                $childCategory->setName($childName);
                $childCategory->setDescription($this->faker->text(50));
                $childCategory->setCategoryParent($parentCategory);
                $manager->persist($childCategory);
                $categories[] = $childCategory;
            }
        }

        $tags = [];
        $tagsData = [
            [
                'name' => 'bio',
                'bgColor' => '#2F4F4F', // Dark Slate Gray
                'textColor' => '#E8F5E9'
            ],
            [
                'name' => 'local',
                'bgColor' => '#1B4F72', // Dark Blue
                'textColor' => '#EBF5FB'
            ],
            [
                'name' => 'fermier',
                'bgColor' => '#1B5E20', // Dark Green
                'textColor' => '#E8F5E9'
            ],
            [
                'name' => 'saison',
                'bgColor' => '#4A235A', // Dark Purple
                'textColor' => '#F4ECF7'
            ],
            [
                'name' => 'artisan',
                'bgColor' => '#641E16', // Dark Red
                'textColor' => '#FDEDEC'
            ],
            [
                'name' => 'éthique',
                'bgColor' => '#154360', // Navy Blue
                'textColor' => '#EBF5FB'
            ],
            [
                'name' => 'durable',
                'bgColor' => '#186A3B', // Forest Green
                'textColor' => '#E8F5E9'
            ],
            [
                'name' => 'naturel',
                'bgColor' => '#784212', // Dark Brown
                'textColor' => '#FDEBD0'
            ],
            [
                'name' => 'fait maison',
                'bgColor' => '#17202A', // Dark Gray
                'textColor' => '#F8F9F9'
            ]
        ];

        foreach ($tagsData as $tagData) {
            $tag = new \App\Entity\Tag();
            $tag->setName($tagData["name"]);
            $tag->setSlug(strtolower(str_replace(' ', '-', $tagData["name"])));
            $tag->setBgColor($tagData["bgColor"]);
            $tag->setTextColor($tagData["textColor"]);
            $manager->persist($tag);
            $tags[] = $tag;
        }

        $units = [];
        $unitsData = [
            [
                'name' => 'kg',
                'symbol' => 'kg'
            ],
            [
                'name' => 'L',
                'symbol' => 'L'
            ],
            [
                'name' => 'unite',
                'symbol' => 'unite'
            ]
        ];

        foreach ($unitsData as $unitData) {
            $unit = new Unity();
            $unit->setName($unitData["name"]);
            $unit->setSymbol($unitData["symbol"]);
            $manager->persist($unit);
            $units[] = $unit;
        }

        // Créer d'abord les types de fermes
        $farmTypes = [];
        for ($i = 0; $i <= 5; $i++) {
            $farmType = new FarmType();
            $farmType->setName($this->faker->word);
            $farmType->setDescription($this->faker->text(20));
            $manager->persist($farmType);
            $farmTypes[] = $farmType;
        }

        // Créer ensuite les fermes
        $farms = [];
        for ($i = 0; $i <= 10; $i++) {
            $farm = new Farm();
            $farm->setName($this->faker->company);
            $farm->setDescription($this->faker->text(100));
            $farm->setAddress($this->faker->address);
            $farm->setCity($this->faker->city);
            $farm->setZipCode($this->faker->postcode);
            $farm->setRegion($this->faker->region);
            $farm->setCoordinates([
                'lat' => $this->faker->latitude,
                'lng' => $this->faker->longitude
            ]);
            $farm->setPhone($this->faker->phoneNumber);
            $farm->setEmail($this->faker->companyEmail);
            $farm->setWebsite($this->faker->url);
            $farm->setFarmSize($this->faker->numberBetween(1, 1000) . ' hectares');
            $farm->setMainProducts($this->faker->words(3));
            $farm->setSeasonality($this->faker->randomElement(['Printemps', 'Été', 'Automne', 'Hiver', 'Toute l\'année']));
            $farm->setDeliveryZones($this->faker->words(2));
            $farm->setDeliveryMethods(['Livraison à domicile', 'Click & Collect', 'Sur place']);
            $farm->setMinimumOrder($this->faker->numberBetween(10, 50) . '€');
            $farm->setProfileImage('https://picsum.photos/200');
            $farm->setGalleryImages([
                'https://picsum.photos/800/600',
                'https://picsum.photos/800/600',
                'https://picsum.photos/800/600'
            ]);
            $farm->setStatus("on");
            $farm->addType($farmTypes[array_rand($farmTypes)]);
            $manager->persist($farm);
            $farms[] = $farm;
        }

        // Ensuite créer les produits et les assigner aux fermes
        $products = [];
        for ($i = 0; $i <= 30; $i++) {
            $product = new Product();
            $product->setName($this->faker->word);
            $product->setQuantity($this->faker->numberBetween(1, 50));
            $product->setUnitPrice($this->faker->randomFloat(2, 3, 100));
            $product->setPrice($this->faker->randomFloat(2, 3, 100));
            $product->setFeatured($this->faker->boolean);
            $randomCategory = $categories[array_rand($categories)];
            $product->addCategory($randomCategory);
            foreach ($this->faker->randomElements($tags, rand(1, 3)) as $tag) {
                $product->addTag($tag);
            }
            $product->setUnity($this->faker->randomElement($units));
            $product->setOrigin($this->faker->country());
            $product->setLongDescription($this->faker->realText(400));
            $product->setConservation($this->faker->sentence(6));
            $product->setPreparationAdvice($this->faker->sentence(8));
            $product->setStatus("on");

            // Assigner le produit à une ferme aléatoire
            $randomFarm = $farms[array_rand($farms)];
            $product->setFarm($randomFarm);
            // $randomFarm->addProduct($product);

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
        for ($i = 0; $i <= 10; $i++) {
            $user = new User();
            $password = $this->userPasswordHasher->hashPassword($user, $this->faker->password(2, 6));
            $user->setUuid($this->faker->uuid)
                ->setPassword($password)
                ->setRoles(['ROLE_USER']);
            $users[] = $user;
            $usersForFarm[] = $user;
            $manager->persist($user);
        }


        $farmIndex = 0;
        foreach ($farms as $farm) {
            $farm->addType($farmTypes[array_rand($farmTypes)]);
            $farmUser = new FarmUser();
            $farmUser->setUser($usersForFarm[$farmIndex]);
            $farmUser->setFarm($farm);
            $farmUser->setRole($this->faker->randomElement(['owner', 'manager', 'employee']));
            $manager->persist($farmUser);
            $farmIndex++;

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
