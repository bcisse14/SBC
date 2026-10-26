<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\{CmsDocument, MediaAsset, PhotoSlot, RealisationCategory, Service, Setting};
use App\Service\MediaStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'app:photos:sync', description: 'Corrige les photos de réalisations, leur classement et la photo principale sans purger les contenus.')]
class SyncProjectPhotosCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MediaStorage $storage,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $photos = [
            ['01-renovation-salle-de-bains/sdb-01.jpeg', 'sdb-01.jpeg', 'Salle de bains, douche vitrée et meuble vasque'],
            ['01-renovation-salle-de-bains/sdb-02.jpeg', 'sdb-02.jpeg', 'Salle de bains, douche et sèche-serviettes'],
            ['01-renovation-salle-de-bains/sdb-03.jpeg', 'sdb-03.jpeg', 'Salle de bains, double vasque et douche à l’italienne'],
            ['01-renovation-salle-de-bains/sdb-04.jpeg', 'sdb-04.jpeg', 'Salle de bains, carrelage sombre et miroirs lumineux'],
            ['02-renovation-chambre/chambre-01.jpeg', 'sdb-05.jpeg', 'Chambre rénovée, mur bleu, parquet et commode en bois'],
            ['03-amenagement-sur-mesure/amenagement-01.jpg', 'amenagement-01.jpg', 'Séjour avec bibliothèque et rangements intégrés sur mesure'],
            ['03-amenagement-sur-mesure/amenagement-02.jpg', 'chambre-01.jpg', 'Espace nuit avec mobilier et rangements intégrés sur mesure'],
        ];
        $hashes = [];
        foreach ($photos as [$relative]) {
            $path = $this->projectDir . '/fixtures/photos/' . $relative;
            if (!is_file($path)) {
                throw new \RuntimeException('Photo source manquante : ' . $relative);
            }
            $hashes[] = hash_file('sha256', $path);
        }
        $revision = hash('sha256', implode('', $hashes));
        $marker = $this->em->getRepository(Setting::class)->findOneBy(['settingKey' => '_project_photos_revision']);
        $refresh = $marker?->getSettingValue() !== $revision;
        $assets = [];

        $this->em->wrapInTransaction(function () use ($photos, $refresh, $revision, $marker, &$assets): void {
            foreach ($photos as [$relative, $legacyName, $alt]) {
                $name = basename($relative);
                $asset = $this->em->getRepository(MediaAsset::class)->findOneBy(['originalName' => $name])
                    ?? $this->em->getRepository(MediaAsset::class)->findOneBy(['originalName' => $legacyName]);
                if (!$asset || $refresh) {
                    $asset ??= new MediaAsset();
                    $stored = $this->storage->ingest($this->projectDir . '/fixtures/photos/' . $relative, $name, 'image/jpeg', $alt);
                    $asset->setPath($stored['path']);
                    $asset->setOriginalName($name);
                    $asset->setMimeType($stored['mimeType']);
                    $asset->setAltText($alt);
                    $asset->setWidth($stored['width']);
                    $asset->setHeight($stored['height']);
                    $asset->setVariants($stored['variants']);
                    $this->em->persist($asset);
                }
                $assets[$name] = $asset;
            }
            $this->em->flush();

            $assignments = [
                'renovation-salle-de-bains' => ['sdb-01.jpeg', 'sdb-02.jpeg', 'sdb-03.jpeg', 'sdb-04.jpeg'],
                'renovation-chambre' => ['chambre-01.jpeg'],
                'amenagement-sur-mesure' => ['amenagement-01.jpg', 'amenagement-02.jpg'],
            ];
            $knownIds = array_map(static fn (MediaAsset $asset) => $asset->getId(), $assets);
            foreach ($assignments as $slug => $names) {
                $category = $this->em->getRepository(RealisationCategory::class)->findOneBy(['slug' => $slug]);
                if (!$category) {
                    throw new \RuntimeException('Catégorie manquante : ' . $slug . '. Initialisez les réalisations avant la synchronisation.');
                }
                $extraIds = array_values(array_diff($category->getGallery(), $knownIds));
                $category->setGallery([...array_map(static fn (string $name) => $assets[$name]->getId(), $names), ...$extraIds]);
                if ($slug === 'renovation-chambre') {
                    if ($category->getDescription() === 'Aménagement d’une chambre au design moderne, avec rangements sur mesure, peinture décorative et parquet chaleureux.') {
                        $category->setDescription('Rénovation d’une chambre avec peinture bleu profond, parquet chaleureux et finitions soignées.');
                    }
                    if ($category->getTagService()?->getName() === 'Meubles de rangement sur mesure') {
                        $category->setTagService($this->em->getRepository(Service::class)->findOneBy(['name' => 'Peinture']));
                    }
                }
                if ($slug === 'amenagement-sur-mesure' && $category->getDescription() === 'Création d’un espace nuit avec mobilier intégré, rangements optimisés et décoration murale aux tons naturels.') {
                    $category->setDescription('Du séjour à l’espace nuit, des rangements et du mobilier intégrés pour optimiser chaque espace.');
                }
            }
            foreach ([
                'Salle de bain' => 'sdb-01.jpeg',
                'Meubles de rangement sur mesure' => 'amenagement-01.jpg',
            ] as $name => $photo) {
                $service = $this->em->getRepository(Service::class)->findOneBy(['name' => $name]);
                $service?->setMediaAssetId($assets[$photo]->getId());
            }
            $hero = $this->em->getRepository(PhotoSlot::class)->findOneBy(['slotKey' => 'PH-ACCUEIL-HERO']);
            if ($hero) {
                $hero->setMediaAssetId($assets['amenagement-01.jpg']->getId());
                $hero->setRatio('1:1');
                $hero->setMinimumSize('457x470, source fournie');
                $hero->setIdealDescription('Aménagement sur mesure, bibliothèque et rangements intégrés dans un séjour');
            }

            $home = $this->em->getRepository(CmsDocument::class)->findOneBy(['resource' => 'pages', 'slug' => '']);
            if ($home) {
                $data = $home->getData();
                foreach ($data['sections'] ?? [] as $index => $section) {
                    if (($section['type'] ?? '') === 'hero') {
                        $data['sections'][$index]['photoSlotKey'] = 'PH-ACCUEIL-HERO';
                        $data['sections'][$index]['mediaAssetId'] = $assets['amenagement-01.jpg']->getId();
                        if (($section['title'] ?? '') === 'Votre vision, notre expertise. Nous transformons vos espaces intérieurs et extérieurs avec audace et précision.') {
                            $data['sections'][$index]['title'] = 'Des espaces pensés pour votre vie.';
                        }
                        if (($section['body'] ?? '') === 'Que ce soit pour un coup de pinceau frais ou une métamorphose complète, nous donnons vie à vos rêves.') {
                            $data['sections'][$index]['body'] = 'Rénovation intérieure, extérieure et aménagement sur mesure en région parisienne. Un seul interlocuteur, de vos premières idées aux dernières finitions.';
                        }
                    }
                }
                $home->setData($data);
            }
            $projects = $this->em->getRepository(CmsDocument::class)->findOneBy(['resource' => 'pages', 'slug' => 'realisations']);
            if ($projects) {
                $data = $projects->getData();
                foreach ($data['sections'] ?? [] as $index => $section) {
                    if (($section['body'] ?? '') === 'Nos réalisations sont présentées par type d’aménagement. Chaque catégorie réunit les photos de travaux déjà réalisés ; les catégories à venir seront ajoutées au fil des mises à jour.') {
                        $data['sections'][$index]['body'] = 'Salles de bains, chambre et aménagements sur mesure : découvrez notre travail à travers ces espaces réalisés.';
                    }
                }
                $projects->setData($data);
            }
            $setting = $marker ?? new Setting();
            $setting->setSettingKey('_project_photos_revision');
            $setting->setSettingValue($revision);
            $this->em->persist($setting);
        });

        $output->writeln('<info>7 photos synchronisées : 4 salles de bains verticales, 1 chambre, 2 aménagements. Photo principale : amenagement-01.jpg.</info>');

        return Command::SUCCESS;
    }
}
