<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\ContentEntry;
use App\Entity\MediaAsset;
use App\Entity\RealisationCategory;
use App\Entity\FaqItem;
use App\Entity\PhotoSlot;
use App\Entity\Service;
use App\Entity\Setting;
use Doctrine\Bundle\FixturesBundle\Fixture;
use App\Service\MediaStorage;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function __construct(private readonly MediaStorage $mediaStorage)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $settings = [
            'company_name' => 'SBC',
            'company_phone' => 'À COMPLÉTER',
            'company_email' => 'À COMPLÉTER',
            'company_address' => 'À COMPLÉTER',
            'company_hours' => 'À COMPLÉTER',
            'company_intervention_area' => 'Région parisienne',
            'slogan_primary' => 'Avec SBC, vos projets se réalisent avec style et fiabilité',
            'slogan_secondary' => 'SBC, la qualité, la durabilité, le service sur mesure',
            'experience_years' => '25',
            'show_photo_markers' => '1',
            'announcement_top' => '',
            'notification_email' => 'sbc.nefzi@gmail.com',
            'mail_from' => 'onboarding@resend.dev',
        ];

        foreach ($settings as $key => $value) {
            $setting = new Setting();
            $setting->setSettingKey($key);
            $setting->setSettingValue($value);
            $manager->persist($setting);
        }

        $content = [
            'home.hero.title' => 'Des espaces pensés pour votre vie.',
            'home.hero.subtitle' => 'Rénovation intérieure, extérieure et aménagement sur mesure en région parisienne. Un seul interlocuteur, de vos premières idées aux dernières finitions.',
            'home.intro.p1' => 'Chez SBC, nous réalisons des projets de rénovation de petite et grande envergure depuis plus de {experience_years} ans. Aménagement ou agrandissement, notre équipe de professionnels est à votre disposition pour réaliser un projet sur mesure, répondant à vos besoins et préférences.',
            'home.intro.p2' => 'Puisque nous avons à cœur d’être votre entrepreneur général en rénovation de confiance dans toute la région parisienne, nous vous proposons un service clé en main allant de la gestion, la conception à la réalisation de votre projet.',
            'home.intro.p3' => 'Meubles de rangement, robinetterie, carrelage, doublage / isolation, revêtement, plomberie, électricité, peinture, nous sommes à votre écoute pour concrétiser votre projet de rénovation ou agrandissement.',
            'home.interior.title' => 'Rénovation intérieure complète',
            'home.interior.tagline' => 'Des murs qui parlent, des espaces qui respirent. Votre chez-vous réinventé.',
            'home.interior.body' => 'Offrez à votre espace intérieur une transformation élégante et fonctionnelle. Nos experts se chargent de rénover votre intérieur avec des matériaux de première qualité, adaptés à vos besoins et à vos goûts.',
            'home.exterior.title' => 'Rénovation extérieure moderne',
            'home.exterior.body' => 'Procédez à la rénovation de votre espace extérieur avec nos solutions sur mesure. Que ce soit pour une terrasse ou une façade, nous améliorons l’esthétique et la durabilité de vos extérieurs.',
            'home.exterior.focus' => 'Façades / toitures : donnez une nouvelle jeunesse à l’enveloppe de votre maison.',
            'home.consultation' => 'Découvrez nos options de rénovation avec une consultation sur place pour discuter de votre projet et obtenir un devis adapté à vos besoins et votre budget. Obtenez une estimation rapide et précise.',
            'about.company' => 'SBC accompagne les particuliers dans leurs projets de rénovation intérieure et extérieure en région parisienne. Nous coordonnons la gestion, la conception et la réalisation de travaux sur mesure.',
            'partners.intro' => 'Nous sélectionnons des fournisseurs, fabricants et artisans partenaires en fonction de la qualité des matériaux, de la fiabilité des délais et de la cohérence avec les besoins de chaque chantier.',
            'email.confirmation.subject' => 'SBC, nous avons bien reçu votre demande de devis',
            'email.confirmation.intro' => "Bonjour {prenom},\n\nNous avons bien reçu votre demande de devis et nous vous en remercions. Nous revenons vers vous rapidement pour échanger sur votre projet.",
            'email.confirmation.outro' => "À très bientôt,\nL’équipe SBC",
            'email.notification.subject' => 'Nouvelle demande de devis : {prenom} {nom}, {ville}',
            'email.notification.intro' => 'Une nouvelle demande de devis vient d’être enregistrée.',
            'realisations.intro' => 'Salles de bains, chambre et aménagements sur mesure : découvrez notre travail à travers ces espaces réalisés.',
        ];

        foreach ($content as $key => $value) {
            $entry = new ContentEntry();
            $entry->setContentKey($key);
            $entry->setDefaultValue($value);
            $entry->setStatus('published');
            $manager->persist($entry);
        }

        $services = [
            ['Cuisine', 'interieur'],
            ['Salle de bain', 'interieur'],
            ['Peinture', 'interieur'],
            ['Carrelage et revêtements', 'interieur'],
            ['Doublage et isolation', 'interieur'],
            ['Plomberie et robinetterie', 'interieur'],
            ['Électricité', 'interieur'],
            ['Meubles de rangement sur mesure', 'interieur'],
            ['Façades', 'exterieur'],
            ['Toitures', 'exterieur'],
            ['Terrasses', 'exterieur'],
            ['Aménagement et agrandissement', 'projet_global'],
            ['Gestion de projet clé en main', 'projet_global'],
        ];

        $serviceByName = [];
        foreach ($services as $index => [$name, $group]) {
            $service = new Service();
            $service->setName($name);
            $service->setServiceGroup($group);
            $service->setDescription('À COMPLÉTER');
            $service->setItems(['À COMPLÉTER']);
            $service->setSortOrder($index + 1);
            $service->setIsPublished(true);
            $manager->persist($service);
            $serviceByName[$name] = $service;
        }

        $faqDrafts = [
            'Quels délais pour démarrer un chantier ?',
            'Proposez-vous une visite sur place ?',
            'Comment se déroule le suivi de projet ?',
        ];

        foreach ($faqDrafts as $index => $question) {
            $faq = new FaqItem();
            $faq->setQuestion($question);
            $faq->setAnswer('À COMPLÉTER');
            $faq->setSortOrder($index + 1);
            $faq->setIsPublished(false);
            $manager->persist($faq);
        }

        $slots = [
            ['PH-ACCUEIL-HERO', '1:1', '457x470, source fournie', 'Aménagement sur mesure, bibliothèque et rangements intégrés dans un séjour'],
            ['PH-ACCUEIL-INTERIEUR', '4:5', 'min 1600x2000', 'Détail intérieur avec matériaux soignés'],
            ['PH-ACCUEIL-EXTERIEUR', '4:5', 'min 1600x2000', 'Façade rénovée ou terrasse aménagée'],
            ['PH-ACCUEIL-METIER-01', '1:1', 'min 1400x1400', 'Mise en peinture nette, outil en action'],
            ['PH-ACCUEIL-METIER-02', '1:1', 'min 1400x1400', 'Pose carrelage ou revetement'],
            ['PH-ACCUEIL-METIER-03', '1:1', 'min 1400x1400', 'Intervention plomberie ou electricite'],
            ['PH-ACCUEIL-BANDEAU-DEVIS', '16:9', 'min 2400x1350', 'Vue chantier propre et organise'],
            ['PH-CONTACT-AMBIANCE', '4:5', 'min 1600x2000', 'Photo atelier ou equipe en reunion'],
            ['PH-PARTENAIRE-LOGO-01', '3:2', 'min 900x600', 'Logo partenaire sur fond neutre'],
            ['PH-PARTENAIRE-LOGO-02', '3:2', 'min 900x600', 'Logo partenaire sur fond neutre'],
            ['PH-PARTENAIRE-LOGO-03', '3:2', 'min 900x600', 'Logo partenaire sur fond neutre'],
            ['PH-PARTENAIRE-LOGO-04', '3:2', 'min 900x600', 'Logo partenaire sur fond neutre'],
            ['PH-PARTENAIRE-LOGO-05', '3:2', 'min 900x600', 'Logo partenaire sur fond neutre'],
            ['PH-PARTENAIRE-LOGO-06', '3:2', 'min 900x600', 'Logo partenaire sur fond neutre'],
            ['PH-PARTENAIRE-LOGO-07', '3:2', 'min 900x600', 'Logo partenaire sur fond neutre'],
            ['PH-PARTENAIRE-LOGO-08', '3:2', 'min 900x600', 'Logo partenaire sur fond neutre'],
        ];

        foreach ($slots as [$key, $ratio, $min, $desc]) {
            $slot = new PhotoSlot();
            $slot->setSlotKey($key);
            $slot->setRatio($ratio);
            $slot->setMinimumSize($min);
            $slot->setIdealDescription($desc);
            $manager->persist($slot);
        }

        $this->loadRealisations($manager, $serviceByName);

        $manager->flush();
    }

    /** @param array<string, Service> $serviceByName */
    private function loadRealisations(ObjectManager $manager, array $serviceByName): void
    {
        $categories = [
            [
                'dir' => '01-renovation-salle-de-bains',
                'title' => 'Rénovation salle de bains',
                'slug' => 'renovation-salle-de-bains',
                'description' => 'Rénovation complète de salle de bains avec douche vitrée, carrelage contemporain et finitions soignées.',
                'service' => 'Salle de bain',
                'photos' => [
                    ['sdb-01.jpeg', 'Rénovation salle de bains, douche vitrée et carrelage contemporain'],
                    ['sdb-02.jpeg', 'Rénovation salle de bains, vue 2'],
                    ['sdb-03.jpeg', 'Rénovation salle de bains, vue 3'],
                    ['sdb-04.jpeg', 'Rénovation salle de bains, vue 4'],
                ],
            ],
            [
                'dir' => '02-renovation-chambre',
                'title' => 'Rénovation chambre',
                'slug' => 'renovation-chambre',
                'description' => 'Rénovation d’une chambre avec peinture bleu profond, parquet chaleureux et finitions soignées.',
                'service' => 'Peinture',
                'photos' => [['chambre-01.jpeg', 'Chambre rénovée, mur bleu, parquet et commode en bois']],
            ],
            [
                'dir' => '03-amenagement-sur-mesure',
                'title' => 'Aménagement sur mesure',
                'slug' => 'amenagement-sur-mesure',
                'description' => 'Du séjour à l’espace nuit, des rangements et du mobilier intégrés pour optimiser chaque espace.',
                'service' => 'Aménagement et agrandissement',
                'photos' => [
                    ['amenagement-01.jpg', 'Aménagement sur mesure, bibliothèque et rangements intégrés dans un séjour'],
                    ['amenagement-02.jpg', 'Aménagement sur mesure, espace nuit avec mobilier intégré'],
                ],
            ],
        ];

        $firstAssetByService = [];
        foreach ($categories as $order => $data) {
            $assets = [];
            foreach ($data['photos'] as [$file, $alt]) {
                $path = dirname(__DIR__, 2) . '/fixtures/photos/' . $data['dir'] . '/' . $file;
                $stored = $this->mediaStorage->ingest($path, $file, (string) mime_content_type($path), $alt);

                $asset = new MediaAsset();
                $asset->setPath($stored['path']);
                $asset->setOriginalName($stored['originalName']);
                $asset->setMimeType($stored['mimeType']);
                $asset->setAltText($stored['altText']);
                $asset->setWidth($stored['width']);
                $asset->setHeight($stored['height']);
                $asset->setVariants($stored['variants']);
                $manager->persist($asset);
                $assets[] = $asset;
            }
            $manager->flush();

            $category = new RealisationCategory();
            $category->setTitle($data['title']);
            $category->setSlug($data['slug']);
            $category->setDescription($data['description']);
            $category->setTagService($serviceByName[$data['service']] ?? null);
            $category->setGallery(array_map(static fn (MediaAsset $a) => (int) $a->getId(), $assets));
            $category->setSortOrder($order + 1);
            $category->setStatus('published');
            $manager->persist($category);

            $firstAssetByService[$data['service']] = $assets[0];
        }

        // Illustrations de services : même fichier que la galerie, pas de copie.
        foreach (['Salle de bain' => 'Salle de bain', 'Meubles de rangement sur mesure' => 'Aménagement et agrandissement'] as $name => $photoService) {
            if (isset($serviceByName[$name], $firstAssetByService[$photoService])) {
                $serviceByName[$name]->setMediaAssetId($firstAssetByService[$photoService]->getId());
            }
        }
        $hero = $manager->getRepository(PhotoSlot::class)->findOneBy(['slotKey' => 'PH-ACCUEIL-HERO']);
        $hero?->setMediaAssetId($firstAssetByService['Aménagement et agrandissement']->getId());
    }
}
