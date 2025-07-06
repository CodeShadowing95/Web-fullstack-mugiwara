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
use App\Entity\VilleFrance;
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
        // Charger les villes françaises depuis l'API
        $response = file_get_contents('https://geo.api.gouv.fr/communes');
        $villesFrance = json_decode($response, true);

        // Juste 100 villes
        $villesFrance = array_slice($villesFrance, 0, 100);

        foreach ($villesFrance as $villeData) {
            $ville = new \App\Entity\VilleFrance();
            $ville->setNom($villeData['nom']);
            $ville->setCode($villeData['code']);
            $ville->setCodeDepartement($villeData['codeDepartement']);
            $ville->setCodeRegion($villeData['codeRegion']);
            $ville->setCodesPostaux($villeData['codesPostaux']);
            $manager->persist($ville);
        }
        $manager->flush();


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

        $mediaTypesBySlug = [];
        foreach ($mediaTypes as $mediaType) {
            $mediaTypesBySlug[$mediaType->getSlug()] = $mediaType;
        }

        // Tableau associatif pour lier manuellement les catégories à leurs images
        $categoryImages = [
            // Parents
            "Fruits & Légumes de saison" => "legumes-fruits.png",
            "Viandes & Produits Animaux" => "fromage.png",
            "Produits Laitiers" => "lait.webp",
            "Boulangerie & Pâtisserie" => "brioche.webp",
            "Produits Transformés" => "confiture.png",
            "Plantes & Jardin" => "terraux.png",
            "Boissons Artisanales" => "jus-pommes.png",
            "Produits Artisanaux / Non-Alimentaires" => "savon.webp",
            "Produits de saison" => "legumes-fruits.png",
            "Légumes anciens" => "panais.png",
            "Herbes fraîches" => "herbes.png",
            "Fruits" => "fruits.webp",
            "Viande" => "saucissons.jpg",
            "Oeufs" => "oeufs.jpg",
            "Produits transformés" => "beurre.png",
            "Volaille prête à cuire" => "poulet.png",
            "Lait cru ou pasteurisé" => "lait.webp",
            "Fromages" => "fromage.png",
            "Yaourts artisanaux" => "yaourt.png",
            "Beurre, crème" => "beurre.png",
            "Pain au levain" => "pain.png",
            "Brioche" => "brioche.webp",
            "Cookies" => "cookies.webp",
            "Tarte rustique" => "tarte-pommes.avif",
            "Confiture" => "confiture.png",
            "Sauce tomate" => "sauce.png",
            "Farine" => "farine.png",
            "Basilic" => "basilic.png",
            "Fleurs séchées" => "fleurs.png",
            "Compost" => "terraux.png",
            "Tomates cerise (plant)" => "plant-tomates.png",
            "Jus de pomme" => "jus-pommes.png",
            "Cidre" => "cidre.png",
            "Bière blonde" => "bierres.png",
            "Tisane" => "tisanne.png",
            "Savon" => "savon.webp",
            "Baume" => "baume.png",
            "Bougie" => "bougie.png",
            "Tissu brodé" => "tissu.webp",
        ];

        foreach ($categoriesData as $categoryData) {
            $parentCategory = new ProductCategory();
            $parentCategory->setName($categoryData["categorie"]);
            $parentCategory->setDescription($this->faker->text(100));
            $manager->persist($parentCategory);
            $categories[] = $parentCategory;
            $manager->flush(); // pour avoir l'ID
            // Ajout image catégorie parent si existe (via tableau associatif)
            $catImgFile = $categoryImages[$categoryData["categorie"]] ?? null;
            if ($catImgFile) {
                $catImgPath = 'media/seeders/categories/' . $catImgFile;
                if (file_exists(__DIR__ . '/../../public/' . $catImgPath)) {
                    $mediaThumb = new \App\Entity\Media();
                    $mediaThumb->setRealName($catImgFile);
                    $mediaThumb->setRealPath($catImgPath);
                    $mediaThumb->setPublicPath($catImgPath);
                    $mediaThumb->setMime(mime_content_type(__DIR__ . '/../../public/' . $catImgPath));
                    $mediaThumb->setStatus('on');
                    $mediaThumb->setUploadedAt(new \DateTime());
                    $mediaThumb->setEntityType('category');
                    $mediaThumb->setEntityId($parentCategory->getId());
                    $mediaThumb->setMediaType(isset($mediaTypesBySlug['thumbnail']) ? $mediaTypesBySlug['thumbnail'] : null);
                    $manager->persist($mediaThumb);
                }
            }

            // Créer les catégories enfants
            foreach ($categoryData["children"] as $childName) {
                $childCategory = new ProductCategory();
                $childCategory->setName($childName);
                $childCategory->setDescription($this->faker->text(50));
                $childCategory->setCategoryParent($parentCategory);
                $manager->persist($childCategory);
                $categories[] = $childCategory;
                $manager->flush();
                // Ajout image catégorie enfant si existe (via tableau associatif)
                $childImgFile = $categoryImages[$childName] ?? null;
                if ($childImgFile) {
                    $childImgPath = 'media/seeders/categories/' . $childImgFile;
                    if (file_exists(__DIR__ . '/../../public/' . $childImgPath)) {
                        $mediaThumb = new \App\Entity\Media();
                        $mediaThumb->setRealName($childImgFile);
                        $mediaThumb->setRealPath($childImgPath);
                        $mediaThumb->setPublicPath($childImgPath);
                        $mediaThumb->setMime(mime_content_type(__DIR__ . '/../../public/' . $childImgPath));
                        $mediaThumb->setStatus('on');
                        $mediaThumb->setUploadedAt(new \DateTime());
                        $mediaThumb->setEntityType('category');
                        $mediaThumb->setEntityId($childCategory->getId());
                        $mediaThumb->setMediaType(isset($mediaTypesBySlug['thumbnail']) ? $mediaTypesBySlug['thumbnail'] : null);
                        $manager->persist($mediaThumb);
                    }
                }
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
        $farmTypesData = [
            ['id' => 1, 'name' => 'Maraîchage', 'description' => 'Culture de légumes'],
            ['id' => 2, 'name' => 'Arboriculture', 'description' => 'Culture de fruits'],
            ['id' => 3, 'name' => 'Élevage bovin', 'description' => 'Élevage de bovins'],
            ['id' => 4, 'name' => 'Élevage ovin/caprin', 'description' => 'Élevage de moutons et chèvres'],
            ['id' => 5, 'name' => 'Élevage porcin', 'description' => 'Élevage de porcs'],
            ['id' => 6, 'name' => 'Aviculture', 'description' => 'Élevage de volailles'],
            ['id' => 7, 'name' => 'Apiculture', 'description' => 'Production de miel'],
            ['id' => 8, 'name' => 'Céréaliculture', 'description' => 'Culture de céréales'],
            ['id' => 9, 'name' => 'Viticulture', 'description' => 'Culture de la vigne'],
            ['id' => 10, 'name' => 'Horticulture', 'description' => 'Culture de fleurs et plantes ornementales'],
            ['id' => 11, 'name' => 'Polyculture-élevage', 'description' => 'Mixte culture et élevage'],
            ['id' => 12, 'name' => 'Agroforesterie', 'description' => 'Association arbres et cultures'],
            ['id' => 13, 'name' => 'Permaculture', 'description' => 'Agriculture durable'],
            ['id' => 14, 'name' => 'Aquaculture', 'description' => 'Élevage aquatique'],
            ['id' => 15, 'name' => 'Transformation artisanale', 'description' => 'Production artisanale'],
            ['id' => 16, 'name' => 'Agriculture biologique', 'description' => 'Production bio certifiée'],
            ['id' => 17, 'name' => 'Agriculture urbaine', 'description' => 'Agriculture en ville'],
            ['id' => 18, 'name' => 'Ferme pédagogique', 'description' => 'Ferme éducative']
        ];

        foreach ($farmTypesData as $typeData) {
            $farmType = new FarmType();
            $farmType->setName($typeData['name']);
            $farmType->setDescription($typeData['description']);
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
            $farm->setAvatar($this->faker->imageUrl(200, 200, 'farm'));
            $farm->setCity($this->faker->city);
            // Générer des coordonnées GPS en France métropolitaine
            $lat = $this->faker->randomFloat(6, 41.3, 51.1);
            $lng = $this->faker->randomFloat(6, -5.2, 9.7);
            $farm->setCoordinates([
                'lat' => $lat,
                'lng' => $lng
            ]);
            $farm->setZipCode($this->faker->postcode);
            $farm->setRegion($this->faker->region);
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
            
            // Ajouter 1 à 3 types de ferme aléatoires
            $numTypes = rand(1, 3);
            $shuffledTypes = $farmTypes;
            shuffle($shuffledTypes);
            $selectedTypes = [];
            for ($j = 0; $j < $numTypes; $j++) {
                $farm->addType($shuffledTypes[$j]);
                $selectedTypes[] = $shuffledTypes[$j]->getName();
            }
            $farm->setFarmTypes(implode(', ', $selectedTypes));

            // Ajouter une note aléatoire entre 0 et 5
            $farm->setRating($this->faker->randomFloat(1, 3.5, 5.0));

            // Ajouter un nombre total de ventes aléatoire
            $farm->setTotalSales($this->faker->numberBetween(0, 1000));
            
            $manager->persist($farm);
            $farms[] = $farm;
        }

        // Créer ensuite les produits et les assigner aux fermes
        $products = [];
        $images = [
            ['file' => 'flocon-avoines.jpg', 'name' => "250g Flocons d'avoine"],
            ['file' => 'oeufs.jpg', 'name' => '6 Oeufs'],
            ['file' => 'saucissons.jpg', 'name' => '1 Saucisson'],
            ['file' => 'cornichons.jpg', 'name' => '100g Cornichons'],
            ['file' => 'aubergines.webp', 'name' => '3 Aubergines'],
            ['file' => 'peches.jpg', 'name' => '500g Pêches'],
            ['file' => 'tomates-2.jpg', 'name' => '200g Tomates cerises'],
            ['file' => 'tomates.jpeg', 'name' => '400g Tomates'],
            ['file' => 'pommes.jpeg', 'name' => '500g Pommes'],
            ['file' => 'carrottes-2.jpeg', 'name' => '600g Carottes'],
            ['file' => 'carrottes.jpeg', 'name' => '300g Carottes'],
            ['file' => 'fraises.jpg', 'name' => '400g Fraises'],
            ['file' => 'poires.webp', 'name' => '800g Poires'],
            ['file' => 'abricots.webp', 'name' => '400g Abricots'],
            ['file' => 'cerises.webp', 'name' => '200g Cerises'],
            ['file' => 'prunes.webp', 'name' => '700g Prunes'],
            ['file' => 'navets.jpg', 'name' => '100g Navets'],
            ['file' => 'poirreaux.jpeg', 'name' => '100g Poireaux'],
            ['file' => 'radis.webp', 'name' => '200g Radis'],
            ['file' => 'salades.webp', 'name' => '4 Salades'],
            ['file' => 'courgettes.jpeg', 'name' => '400g Courgettes'],
            ['file' => 'comcombres.jpg', 'name' => '300g Concombres'],
            ['file' => 'oignons.webp', 'name' => '200g Oignons'],
            ['file' => 'ails.png', 'name' => '100g Ail'],
            ['file' => 'echalottes.jpg', 'name' => '200g Échalotes'],
            ['file' => 'pommeterres.jpeg', 'name' => '800g Pommes de terre'],
            ['file' => 'champigons.webp', 'name' => '300g Champignons'],
            ['file' => 'epinards.jpeg', 'name' => '300g Épinards'],
            ['file' => 'blettes.webp', 'name' => '400g Blettes'],
            ['file' => 'haricots-verts.jpg', 'name' => '300g Haricots verts'],
            ['file' => 'petits-pois.jpg', 'name' => '300g Petits pois'],
            ['file' => 'lentilles.jpg', 'name' => '500g Lentilles'],
            ['file' => 'pois-chiches.jpg', 'name' => '500g Pois chiches'],
            ['file' => 'noisettes.jpg', 'name' => '200g Noisettes'],
            ['file' => 'noix.jpg', 'name' => '200g Noix'],
            ['file' => 'amandes.jpg', 'name' => '200g Amandes'],
            ['file' => 'pistaches.jpg', 'name' => '200g Pistaches'],
            ['file' => 'fromage-chèvre.jpg', 'name' => '200g Fromage de chèvre'],
            ['file' => 'fromage-brebis.jpg', 'name' => '200g Fromage de brebis'],
            ['file' => 'fromage-vache.jpg', 'name' => '200g Fromage de vache'],
            ['file' => 'yaourt.jpg', 'name' => '4 Yaourts'],
            ['file' => 'beurre.jpg', 'name' => '250g Beurre'],
            ['file' => 'creme-fraiche.jpg', 'name' => '200g Crème fraîche'],
            ['file' => 'pain.jpg', 'name' => '1 Pain (400g)'],
            ['file' => 'brioche.jpg', 'name' => '1 Brioche (350g)'],
            ['file' => 'cookies.jpg', 'name' => '4 Cookies (120g)'],
            ['file' => 'tarte.jpg', 'name' => '1 Tarte (500g)'],
            ['file' => 'confiture.jpg', 'name' => '250g Confiture'],
            ['file' => 'sauce-tomate.jpg', 'name' => '350g Sauce tomate'],
            ['file' => 'farine.jpg', 'name' => '1kg Farine'],
            ['file' => 'jus-pomme.jpg', 'name' => '1L Jus de pomme'],
            ['file' => 'cidre.jpg', 'name' => '75cl Cidre'],
            ['file' => 'biere.jpg', 'name' => '33cl Bière'],
            ['file' => 'tisane.jpg', 'name' => '20 sachets Tisane']
        ];
        $productDescriptions = [
            "250g Flocons d'avoine" => [
                'short' => "Flocons d'avoine riches en fibres, parfaits pour le petit-déjeuner.",
                'long' => "Ces flocons d'avoine sont idéaux pour préparer un porridge nourrissant ou agrémenter vos recettes de pâtisserie. Source naturelle de fibres et de protéines, ils sont issus d'une agriculture locale et respectueuse de l'environnement."
            ],
            "6 Oeufs" => [
                'short' => "Oeufs frais de poules élevées en plein air.",
                'long' => "Nos oeufs proviennent de poules élevées en plein air, nourries sans OGM. Parfaits pour toutes vos préparations culinaires, ils garantissent fraîcheur et qualité supérieure."
            ],
            "1 Saucisson" => [
                'short' => "Saucisson artisanal, recette traditionnelle.",
                'long' => "Ce saucisson est fabriqué selon une recette artisanale, avec des ingrédients locaux et naturels. Idéal pour l'apéritif ou en accompagnement d'un plateau de fromages."
            ],
            "100g Cornichons" => [
                'short' => "Cornichons croquants au vinaigre.",
                'long' => "Nos cornichons sont récoltés à maturité puis préparés dans un vinaigre doux. Ils apportent une touche de fraîcheur et de croquant à vos plats et sandwichs."
            ],
            "3 Aubergines" => [
                'short' => "Aubergines fraîches, parfaites pour la ratatouille.",
                'long' => "Ces aubergines sont cultivées localement et cueillies à la main. Leur chair fondante est idéale pour les gratins, ratatouilles ou grillades estivales."
            ],
            "500g Pêches" => [
                'short' => "Pêches juteuses et sucrées, cueillies à maturité.",
                'long' => "Nos pêches sont récoltées à la main pour garantir une fraîcheur et une saveur incomparables. Parfaites en dessert ou en salade de fruits."
            ],
            "200g Tomates cerises" => [
                'short' => "Tomates cerises croquantes et savoureuses.",
                'long' => "Idéales pour l'apéritif ou en salade, ces tomates cerises sont cultivées sans pesticides et cueillies à la main."
            ],
            "400g Tomates" => [
                'short' => "Tomates rouges mûres, goût authentique.",
                'long' => "Ces tomates sont parfaites pour vos sauces, salades ou tartes. Issues d'une agriculture raisonnée."
            ],
            "500g Pommes" => [
                'short' => "Pommes croquantes et sucrées.",
                'long' => "Nos pommes sont cultivées localement et sélectionnées pour leur goût et leur fraîcheur. À croquer ou à cuisiner."
            ],
            "600g Carottes" => [
                'short' => "Carottes fraîches, riches en vitamines.",
                'long' => "Idéales râpées, en jus ou en accompagnement, nos carottes sont issues de l'agriculture locale."
            ],
            "300g Carottes" => [
                'short' => "Carottes tendres et sucrées.",
                'long' => "Parfaites pour les soupes, purées ou à croquer en snack sain."
            ],
            "400g Fraises" => [
                'short' => "Fraises parfumées, cueillies à la main.",
                'long' => "Nos fraises sont cultivées sans pesticides et récoltées à maturité pour un goût sucré et acidulé."
            ],
            "800g Poires" => [
                'short' => "Poires juteuses et fondantes.",
                'long' => "Idéales en dessert ou en salade de fruits, nos poires sont issues de vergers locaux."
            ],
            "400g Abricots" => [
                'short' => "Abricots doux et parfumés.",
                'long' => "Ces abricots sont parfaits pour les confitures, tartes ou à déguster nature."
            ],
            "200g Cerises" => [
                'short' => "Cerises croquantes et sucrées.",
                'long' => "Récoltées à la main, nos cerises sont idéales pour les desserts ou à savourer telles quelles."
            ],
            "700g Prunes" => [
                'short' => "Prunes mûres et juteuses.",
                'long' => "Parfaites pour les tartes, confitures ou à déguster en collation."
            ],
            "100g Navets" => [
                'short' => "Navets tendres, parfaits pour les soupes.",
                'long' => "Nos navets sont cultivés localement et apportent douceur et saveur à vos plats mijotés."
            ],
            "100g Poireaux" => [
                'short' => "Poireaux frais, riches en fibres.",
                'long' => "Idéals pour les quiches, potages ou en fondue, nos poireaux sont récoltés à la main."
            ],
            "200g Radis" => [
                'short' => "Radis croquants et légèrement piquants.",
                'long' => "À déguster à la croque au sel ou en salade, nos radis sont cultivés sans pesticides."
            ],
            "4 Salades" => [
                'short' => "Salades fraîches et croquantes.",
                'long' => "Idéales pour accompagner tous vos plats, nos salades sont récoltées le matin même."
            ],
            "400g Courgettes" => [
                'short' => "Courgettes tendres, parfaites à griller.",
                'long' => "Nos courgettes sont idéales pour les gratins, poêlées ou en ratatouille."
            ],
            "300g Concombres" => [
                'short' => "Concombres frais, parfaits en salade.",
                'long' => "Cultivés localement, nos concombres sont croquants et désaltérants."
            ],
            "200g Oignons" => [
                'short' => "Oignons doux, parfaits pour la cuisine.",
                'long' => "Nos oignons sont sélectionnés pour leur saveur et leur douceur, idéals pour toutes vos recettes."
            ],
            "100g Ail" => [
                'short' => "Ail frais, goût puissant.",
                'long' => "Notre ail est récolté à la main et apporte du caractère à vos plats."
            ],
            "200g Échalotes" => [
                'short' => "Échalotes parfumées, goût subtil.",
                'long' => "Idéales pour les vinaigrettes, sauces ou à confire."
            ],
            "800g Pommes de terre" => [
                'short' => "Pommes de terre fondantes.",
                'long' => "Parfaites pour les purées, gratins ou frites maison."
            ],
            "300g Champignons" => [
                'short' => "Champignons frais, goût boisé.",
                'long' => "Idéals pour les poêlées, omelettes ou risottos."
            ],
            "300g Épinards" => [
                'short' => "Épinards tendres, riches en fer.",
                'long' => "À cuisiner en gratin, quiche ou simplement poêlés."
            ],
            "400g Blettes" => [
                'short' => "Blettes fraîches, parfaites en gratin.",
                'long' => "Nos blettes sont cultivées localement et idéales pour les tartes salées."
            ],
            "300g Haricots verts" => [
                'short' => "Haricots verts croquants.",
                'long' => "À cuire à la vapeur ou à la poêle, parfaits en accompagnement."
            ],
            "300g Petits pois" => [
                'short' => "Petits pois sucrés et tendres.",
                'long' => "Idéals pour les purées, risottos ou en salade."
            ],
            "500g Lentilles" => [
                'short' => "Lentilles vertes, riches en protéines.",
                'long' => "À cuisiner en salade, soupe ou dahl."
            ],
            "500g Pois chiches" => [
                'short' => "Pois chiches, parfaits pour le houmous.",
                'long' => "Riches en fibres et protéines, à utiliser en salade ou en curry."
            ],
            "200g Noisettes" => [
                'short' => "Noisettes croquantes, riches en goût.",
                'long' => "À grignoter ou à intégrer dans vos pâtisseries."
            ],
            "200g Noix" => [
                'short' => "Noix fraîches, riches en oméga-3.",
                'long' => "À consommer nature ou dans vos salades."
            ],
            "200g Amandes" => [
                'short' => "Amandes douces, riches en magnésium.",
                'long' => "À croquer ou à utiliser en pâtisserie."
            ],
            "200g Pistaches" => [
                'short' => "Pistaches grillées, riches en saveur.",
                'long' => "À déguster à l'apéritif ou à intégrer dans vos desserts."
            ],
            "200g Fromage de chèvre" => [
                'short' => "Fromage de chèvre artisanal.",
                'long' => "Parfait pour les salades, tartines ou gratins."
            ],
            "200g Fromage de brebis" => [
                'short' => "Fromage de brebis doux et crémeux.",
                'long' => "Idéal pour les plateaux de fromages ou en salade."
            ],
            "200g Fromage de vache" => [
                'short' => "Fromage de vache fondant.",
                'long' => "À savourer en raclette ou en sandwich."
            ],
            "4 Yaourts" => [
                'short' => "Yaourts nature onctueux.",
                'long' => "Fabriqués à partir de lait local, parfaits pour le petit-déjeuner."
            ],
            "250g Beurre" => [
                'short' => "Beurre doux artisanal.",
                'long' => "Idéal pour tartiner ou cuisiner."
            ],
            "200g Crème fraîche" => [
                'short' => "Crème fraîche épaisse.",
                'long' => "Parfaite pour les sauces et desserts."
            ],
            "1 Pain (400g)" => [
                'short' => "Pain au levain croustillant.",
                'long' => "Cuit au feu de bois, idéal pour accompagner vos repas."
            ],
            "1 Brioche (350g)" => [
                'short' => "Brioche moelleuse et dorée.",
                'long' => "Parfaite pour le petit-déjeuner ou le goûter."
            ],
            "4 Cookies (120g)" => [
                'short' => "Cookies gourmands aux pépites de chocolat.",
                'long' => "À savourer au goûter ou en dessert."
            ],
            "1 Tarte (500g)" => [
                'short' => "Tarte rustique aux fruits de saison.",
                'long' => "Pâte croustillante et garniture généreuse."
            ],
            "250g Confiture" => [
                'short' => "Confiture artisanale, fruits locaux.",
                'long' => "Idéale pour les tartines ou les desserts."
            ],
            "350g Sauce tomate" => [
                'short' => "Sauce tomate maison, riche en goût.",
                'long' => "Préparée avec des tomates fraîches et des herbes du jardin."
            ],
            "1kg Farine" => [
                'short' => "Farine de blé locale.",
                'long' => "Parfaite pour pains, gâteaux et pâtes fraîches."
            ],
            "1L Jus de pomme" => [
                'short' => "Jus de pomme artisanal, sans sucre ajouté.",
                'long' => "Pressé à froid, goût fruité et rafraîchissant."
            ],
            "75cl Cidre" => [
                'short' => "Cidre brut, bulles fines.",
                'long' => "Élaboré à partir de pommes locales, parfait à l'apéritif."
            ],
            "33cl Bière" => [
                'short' => "Bière blonde artisanale.",
                'long' => "Brassée localement, légère et rafraîchissante."
            ],
            "20 sachets Tisane" => [
                'short' => "Tisane aux plantes bio.",
                'long' => "Mélange relaxant de plantes locales pour une pause douceur."
            ]
        ];

        $conservationValues = [
            "À conserver au réfrigérateur entre 2°C et 4°C.",
            "À conserver dans un endroit frais et sec, à l'abri de la lumière.",
            "À consommer rapidement après ouverture.",
            "Se conserve 3 jours au réfrigérateur.",
            "À conserver dans le bac à légumes du réfrigérateur.",
            "À conserver dans un pot hermétique.",
            "À conserver à température ambiante.",
            "À congeler si non consommé sous 48h.",
            "À conserver dans un endroit sec, à l'abri de l'humidité.",
            "À consommer de préférence avant la date indiquée sur l'emballage."
        ];
        $adviceValues = [
            "Idéal en salade ou en accompagnement.",
            "Parfait pour vos recettes de pâtisserie.",
            "À déguster nature ou avec un peu de sel.",
            "À cuire à la vapeur ou à la poêle.",
            "À consommer frais pour profiter de toutes ses saveurs.",
            "À utiliser dans vos gratins ou quiches.",
            "À tartiner sur du pain frais.",
            "À mélanger dans un yaourt ou un smoothie.",
            "À faire revenir avec un filet d'huile d'olive.",
            "À savourer en dessert ou en collation."
        ];

        $productToCategoryChild = [
            "250g Flocons d'avoine" => "Produits transformés",
            "6 Oeufs" => "Oeufs",
            "1 Saucisson" => "Viande",
            "100g Cornichons" => "Produits transformés",
            "3 Aubergines" => "Produits de saison",
            "500g Pêches" => "Fruits",
            "200g Tomates cerises" => "Produits de saison",
            "400g Tomates" => "Produits de saison",
            "500g Pommes" => "Fruits",
            "600g Carottes" => "Produits de saison",
            "300g Carottes" => "Produits de saison",
            "400g Fraises" => "Fruits",
            "800g Poires" => "Fruits",
            "400g Abricots" => "Fruits",
            "200g Cerises" => "Fruits",
            "700g Prunes" => "Fruits",
            "100g Navets" => "Légumes anciens",
            "100g Poireaux" => "Produits de saison",
            "200g Radis" => "Produits de saison",
            "4 Salades" => "Produits de saison",
            "400g Courgettes" => "Produits de saison",
            "300g Concombres" => "Produits de saison",
            "200g Oignons" => "Produits de saison",
            "100g Ail" => "Herbes fraîches",
            "200g Échalotes" => "Produits de saison",
            "800g Pommes de terre" => "Légumes anciens",
            "300g Champignons" => "Produits de saison",
            "300g Épinards" => "Produits de saison",
            "400g Blettes" => "Produits de saison",
            "300g Haricots verts" => "Produits de saison",
            "300g Petits pois" => "Produits de saison",
            "500g Lentilles" => "Produits transformés",
            "500g Pois chiches" => "Produits transformés",
            "200g Noisettes" => "Produits transformés",
            "200g Noix" => "Produits transformés",
            "200g Amandes" => "Produits transformés",
            "200g Pistaches" => "Produits transformés",
            "200g Fromage de chèvre" => "Fromages",
            "200g Fromage de brebis" => "Fromages",
            "200g Fromage de vache" => "Fromages",
            "4 Yaourts" => "Yaourts artisanaux",
            "250g Beurre" => "Beurre, crème",
            "200g Crème fraîche" => "Beurre, crème",
            "1 Pain (400g)" => "Pain au levain",
            "1 Brioche (350g)" => "Brioche",
            "4 Cookies (120g)" => "Cookies",
            "1 Tarte (500g)" => "Tarte rustique",
            "250g Confiture" => "Confiture",
            "350g Sauce tomate" => "Sauce tomate",
            "1kg Farine" => "Farine",
            "1L Jus de pomme" => "Jus de pomme",
            "75cl Cidre" => "Cidre",
            "33cl Bière" => "Bière blonde",
            "20 sachets Tisane" => "Tisane",
        ];

        for ($i = 0; $i < 50; $i++) {
            $img = $images[$i % count($images)];
            $product = new Product();
            $product->setName($img['name']);
            // Ajout des descriptions personnalisées si elles existent, sinon fallback faker
            if (isset($productDescriptions[$img['name']])) {
                $product->setShortDescription($productDescriptions[$img['name']]['short']);
                $product->setLongDescription($productDescriptions[$img['name']]['long']);
            } else {
                $product->setShortDescription($this->faker->realText(100));
                $product->setLongDescription($this->faker->realText(400));
            }
            $product->setQuantity($this->faker->numberBetween(1, 50));
            $product->setUnitPrice($this->faker->randomFloat(2, 3, 100));
            $product->setPrice($this->faker->randomFloat(2, 3, 100));
            $product->setOldPrice($this->faker->optional(0.5)->randomFloat(2, 3, 150));
            $product->setFeatured($this->faker->boolean);
            // Associer la bonne catégorie enfant
            $categoryName = $productToCategoryChild[$img['name']] ?? null;
            if ($categoryName) {
                $category = null;
                foreach ($categories as $cat) {
                    if ($cat->getName() === $categoryName) {
                        $category = $cat;
                        break;
                    }
                }
                if ($category) {
                    $product->addCategory($category);
                }
            }
            foreach ($this->faker->randomElements($tags, rand(1, 3)) as $tag) {
                $product->addTag($tag);
            }
            $product->setUnity($this->faker->randomElement($units));
            $product->setStock($this->faker->numberBetween(1, 30));
            $product->setOrigin($this->faker->country());
            $product->setConservation($conservationValues[array_rand($conservationValues)]);
            $product->setPreparationAdvice($adviceValues[array_rand($adviceValues)]);
            $product->setStatus("on");
            $randomFarm = $farms[array_rand($farms)];
            $product->setFarm($randomFarm);
            $manager->persist($product);
            $products[] = $product;
        }

        $manager->flush();

        foreach ($products as $idx => $product) {
            $img = $images[$idx % count($images)];
            $filePath = __DIR__ . '/../../public/media/seeders/products/' . $img['file'];
            if (file_exists($filePath)) {
                // THUMBNAIL
                $mediaThumb = new \App\Entity\Media();
                $mediaThumb->setRealName($img['file']);
                $mediaThumb->setRealPath('media/seeders/products/' . $img['file']);
                $mediaThumb->setPublicPath('media/seeders/products/' . $img['file']);
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
                $mediaImg->setRealPath('media/seeders/products/' . $img['file']);
                $mediaImg->setPublicPath('media/seeders/products/' . $img['file']);
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
