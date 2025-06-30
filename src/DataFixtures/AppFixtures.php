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

        $mediaTypes = [];
        $mediaTypesData = [
            [
                'name' => 'image',
                'slug' => 'image'
            ],
            [
                'name' => 'thumbnail',
                'slug' => 'thumbnail'
            ],
            [
                'name' => 'banner',
                'slug' => 'banner'
            ],
            [
                'name' => 'logo',
                'slug' => 'logo'
            ],
            [
                'name' => 'document',
                'slug' => 'document'
            ]
        ];

        foreach ($mediaTypesData as $mediaTypeData) {
            $mediaType = new MediaType();
            $mediaType->setName($mediaTypeData["name"]);
            $mediaType->setSlug($mediaTypeData["slug"]);
            $manager->persist($mediaType);
            $mediaTypes[] = $mediaType;
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

        // Créer ensuite les produits et les assigner aux fermes
        $products = [];
        $images = [
            ['file' => 'flocon-avoines.jpg', 'name' => "Flocons d'avoine"],
            ['file' => 'oeufs.jpg', 'name' => 'Oeufs'],
            ['file' => 'saucissons.jpg', 'name' => 'Saucissons'],
            ['file' => 'cornichons.jpg', 'name' => 'Cornichons'],
            ['file' => 'aubergines.webp', 'name' => 'Aubergines'],
            ['file' => 'peches.jpg', 'name' => 'Pêches'],
            ['file' => 'tomates-2.jpg', 'name' => 'Tomates'],
            ['file' => 'tomates.jpeg', 'name' => 'Tomates'],
            ['file' => 'pommes.jpeg', 'name' => 'Pommes'],
            ['file' => 'carrottes-2.jpeg', 'name' => 'Carottes'],
            ['file' => 'carrottes.jpeg', 'name' => 'Carottes'],
            ['file' => 'fraises.jpg', 'name' => 'Fraises'],
            ['file' => 'poires.webp', 'name' => 'Poires'],
            ['file' => 'abricots.webp', 'name' => 'Abricots'],
            ['file' => 'cerises.webp', 'name' => 'Cerises'],
            ['file' => 'prunes.webp', 'name' => 'Prunes'],
            ['file' => 'navets.jpg', 'name' => 'Navets'],
            ['file' => 'poirreaux.jpeg', 'name' => 'Poireaux'],
            ['file' => 'radis.webp', 'name' => 'Radis'],
            ['file' => 'salades.webp', 'name' => 'Salade'],
            ['file' => 'courgettes.jpeg', 'name' => 'Courgettes'],
            ['file' => 'comcombres.jpg', 'name' => 'Concombres'],
            ['file' => 'oignons.webp', 'name' => 'Oignons'],
            ['file' => 'ails.png', 'name' => 'Ail'],
            ['file' => 'echalottes.jpg', 'name' => 'Échalotes'],
            ['file' => 'pommeterres.jpeg', 'name' => 'Pommes de terre'],
            ['file' => 'champigons.webp', 'name' => 'Champignons'],
            ['file' => 'epinards.jpeg', 'name' => 'Épinards'],
            ['file' => 'blettes.webp', 'name' => 'Blettes'],
            ['file' => 'haricots-verts.jpg', 'name' => 'Haricots verts'],
            ['file' => 'petits-pois.jpg', 'name' => 'Petits pois'],
            ['file' => 'lentilles.jpg', 'name' => 'Lentilles'],
            ['file' => 'pois-chiches.jpg', 'name' => 'Pois chiches'],
            ['file' => 'noisettes.jpg', 'name' => 'Noisettes'],
            ['file' => 'noix.jpg', 'name' => 'Noix'],
            ['file' => 'amandes.jpg', 'name' => 'Amandes'],
            ['file' => 'pistaches.jpg', 'name' => 'Pistaches'],
            ['file' => 'fromage-chèvre.jpg', 'name' => 'Fromage de chèvre'],
            ['file' => 'fromage-brebis.jpg', 'name' => 'Fromage de brebis'],
            ['file' => 'fromage-vache.jpg', 'name' => 'Fromage de vache'],
            ['file' => 'yaourt.jpg', 'name' => 'Yaourt'],
            ['file' => 'beurre.jpg', 'name' => 'Beurre'],
            ['file' => 'creme-fraiche.jpg', 'name' => 'Crème fraîche'],
            ['file' => 'pain.jpg', 'name' => 'Pain'],
            ['file' => 'brioche.jpg', 'name' => 'Brioche'],
            ['file' => 'cookies.jpg', 'name' => 'Cookies'],
            ['file' => 'tarte.jpg', 'name' => 'Tarte'],
            ['file' => 'confiture.jpg', 'name' => 'Confiture'],
            ['file' => 'sauce-tomate.jpg', 'name' => 'Sauce tomate'],
            ['file' => 'farine.jpg', 'name' => 'Farine'],
            ['file' => 'jus-pomme.jpg', 'name' => 'Jus de pomme'],
            ['file' => 'cidre.jpg', 'name' => 'Cidre'],
            ['file' => 'biere.jpg', 'name' => 'Bière'],
            ['file' => 'tisane.jpg', 'name' => 'Tisane']
        ];

        $mediaTypesBySlug = [];
        foreach ($mediaTypes as $mediaType) {
            $mediaTypesBySlug[$mediaType->getSlug()] = $mediaType;
        }

        // Créer un produit pour chaque image et l'assigner à une catégorie principale de façon cyclique
        $mainCategories = array_values(array_filter($categories, function($cat) {
            return $cat->getCategoryParent() === null;
        }));
        
        if (empty($mainCategories)) {
            throw new \Exception("Aucune catégorie principale trouvée. Assurez-vous d'avoir des catégories sans parent.");
        }

        for ($i = 0; $i < 50; $i++) {
            $img = $images[$i % count($images)];
            $product = new Product();
            $product->setName($img['name']);
            $product->setQuantity($this->faker->numberBetween(1, 50));
            $product->setUnitPrice($this->faker->randomFloat(2, 3, 100));
            $product->setPrice($this->faker->randomFloat(2, 3, 100));
            $product->setFeatured($this->faker->boolean);
            $product->addCategory($mainCategories[$i % count($mainCategories)]);
            foreach ($this->faker->randomElements($tags, rand(1, 3)) as $tag) {
                $product->addTag($tag);
            }
            $product->setUnity($this->faker->randomElement($units));
            $product->setOrigin($this->faker->country());
            $product->setLongDescription($this->faker->realText(400));
            $product->setConservation($this->faker->sentence(6));
            $product->setPreparationAdvice($this->faker->sentence(8));
            $product->setStatus("on");
            $randomFarm = $farms[array_rand($farms)];
            $product->setFarm($randomFarm);
            $manager->persist($product);
            $products[] = $product;
        }
        
        $manager->flush();

        // Créer les médias pour chaque produit (seulement si le fichier existe)
        foreach ($products as $idx => $product) {
            $img = $images[$idx % count($images)];
            $filePath = __DIR__ . '/../../public/media/' . $img['file'];
            if (file_exists($filePath)) {
                // THUMBNAIL
                $mediaThumb = new \App\Entity\Media();
                $mediaThumb->setRealName($img['file']);
                $mediaThumb->setRealPath('media/' . $img['file']);
                $mediaThumb->setPublicPath('media/' . $img['file']);
                $mediaThumb->setMime(mime_content_type($filePath));
                $mediaThumb->setStatus('on');
                $mediaThumb->setUploadedAt(new \DateTime());
                $mediaThumb->setEntityType('product');
                $mediaThumb->setEntityId($product->getId());
                $mediaThumb->setMediaType($mediaTypesBySlug['thumbnail'] ?? null);
                $manager->persist($mediaThumb);

                // IMAGE
                $mediaImg = new \App\Entity\Media();
                $mediaImg->setRealName($img['file']);
                $mediaImg->setRealPath('media/' . $img['file']);
                $mediaImg->setPublicPath('media/' . $img['file']);
                $mediaImg->setMime(mime_content_type($filePath));
                $mediaImg->setStatus('on');
                $mediaImg->setUploadedAt(new \DateTime());
                $mediaImg->setEntityType('product');
                $mediaImg->setEntityId($product->getId());
                $mediaImg->setMediaType($mediaTypesBySlug['image'] ?? null);
                $manager->persist($mediaImg);
            }
        }
        $manager->flush();

        // Créer les utilisateurs avant les avis
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
        $manager->flush();

        // Ajouter des avis à chaque produit
        $reviewComments = [
            "Excellent produit, très frais et de qualité !",
            "Très satisfait de mon achat, je recommande vivement.",
            "Produit conforme à mes attentes, livraison rapide.",
            "Qualité exceptionnelle, prix très correct.",
            "Un peu déçu par la taille, mais le goût est au rendez-vous.",
            "Parfait pour mes recettes, je rachèterai sans hésiter.",
            "Produit bio de qualité, exactement ce que je cherchais.",
            "Très bon rapport qualité-prix, je recommande.",
            "Ferme sérieuse, produits frais et de saison.",
            "Service client au top, produits délicieux."
        ];
        foreach ($products as $product) {
            $nbReviews = rand(1, 3);
            for ($i = 0; $i < $nbReviews; $i++) {
                $review = new \App\Entity\Review();
                $review->setProduct($product);
                $review->setUser($this->faker->randomElement($users));
                $review->setComment($reviewComments[array_rand($reviewComments)]);
                $review->setRating(rand(3, 5));
                $manager->persist($review);
            }
        }
        $manager->flush();

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
