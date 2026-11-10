<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\{CmsDocument, MediaAsset, Partner, PhotoSlot, RealisationCategory, Service, Setting};
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
            ['04-photos-accueil/Photo_Accueil_1.jpg', 'Photo_Accueil_1.jpg', 'Projet de rénovation SBC'],
            ['05-renovation-generale/renovation_generale_1.jpg', 'renovation_generale_1.jpg', 'Rénovation générale, vue du projet'],
            ['05-renovation-generale/renovation_generale_2.jpg', 'renovation_generale_2.jpg', 'Rénovation générale, détail des finitions'],
            ['05-renovation-generale/renovation_generale_3.jpg', 'renovation_generale_3.jpg', 'Rénovation générale, espace transformé'],
            ['06-parquet/parquet-1.jpg', 'parquet-1.jpg', 'Pose de parquet, détail du sol'],
            ['06-parquet/parquet-2.jpg', 'parquet-2.jpg', 'Pose de parquet, finition'],
            ['06-parquet/parquet-3.jpg', 'parquet-3.jpg', 'Pose de parquet, sol rénové'],
            ['07-peinture-finition/peinture-finition_1.png', 'peinture-finition_1.png', 'Peinture et finitions, préparation du chantier'],
            ['07-peinture-finition/peinture-finition_2.jpg', 'peinture-finition_2.jpg', 'Peinture et finitions, mur rénové'],
            ['07-peinture-finition/peinture-finition_3.png', 'peinture-finition_3.png', 'Peinture et finitions, détail décoratif'],
            ['08-electricite/electricite_1.jpg', 'electricite_1.jpg', 'Électricité, installation et raccordement'],
            ['08-electricite/electricite_2.jpg', 'electricite_2.jpg', 'Électricité, détail de l’installation'],
        ];
        $hashes = [];
        foreach ($photos as [$relative]) {
            $path = $this->projectDir . '/fixtures/photos/' . $relative;
            if (!is_file($path)) {
                throw new \RuntimeException('Photo source manquante : ' . $relative);
            }
            $hashes[] = hash_file('sha256', $path);
        }
        $logos = [
            ['09-logos/Farrow-Ball-Logo-1.webp', 'Farrow & Ball'],
            ['09-logos/logo_tollens.webp', 'Tollens'],
            ['09-logos/logo_heulin_color.webp', 'Heulin Color'],
            ['09-logos/logo-mercadier-partenaire.jpg.webp', 'Mercadier'],
            ['09-logos/BATIMATOS.png', 'BATIMATOS'],
            ['09-logos/sogefi_logo.png', 'SOGEFI'],
            ['09-logos/plateforme-du-batiment.svg', 'La Plateforme du bâtiment'],
            ['09-logos/cedeo_2.webp', 'Cedeo'],
            ['09-logos/richardson.svg', 'Richardson'],
            ['09-logos/point_p.webp', 'Point P'],
            ['09-logos/leroy-merlin-logo.png', 'Leroy Merlin'],
            ['09-logos/gedimat.png', 'Gedimat'],
            ['09-logos/espace_aubade_2.webp', 'Espace Aubade'],
            ['09-logos/raboni.png', 'Raboni'],
        ];
        foreach ($logos as [$relative]) {
            $path = $this->projectDir . '/fixtures/photos/' . $relative;
            if (!is_file($path)) {
                throw new \RuntimeException('Logo source manquant : ' . $relative);
            }
            $hashes[] = hash_file('sha256', $path);
        }
        $revision = hash('sha256', implode('', $hashes));
        $marker = $this->em->getRepository(Setting::class)->findOneBy(['settingKey' => '_project_photos_revision']);
        $refresh = $marker?->getSettingValue() !== $revision;
        $assets = [];

        $this->em->wrapInTransaction(function () use ($photos, $logos, $refresh, $revision, $marker, &$assets): void {
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
            foreach ($logos as [$relative, $partnerName]) {
                $name = basename($relative);
                $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($this->projectDir . '/fixtures/photos/' . $relative);
                $asset = $this->em->getRepository(MediaAsset::class)->findOneBy(['originalName' => $name]);
                if (!$asset || $refresh) {
                    $asset ??= new MediaAsset();
                    $source = $this->projectDir . '/fixtures/photos/' . $relative;
                    $ingestMime = $mime ?: 'image/png';
                    $temporaryPng = null;
                    if ($ingestMime === 'image/svg+xml') {
                        $temporaryPng = tempnam(sys_get_temp_dir(), 'sbc-logo-') . '.png';
                        $svg = new \Imagick($source);
                        $svg->setImageFormat('png');
                        $svg->writeImage($temporaryPng);
                        $svg->clear();
                        $source = $temporaryPng;
                        $ingestMime = 'image/png';
                    }
                    $stored = $this->storage->ingest($source, $name, $ingestMime, 'Logo ' . $partnerName);
                    if ($temporaryPng && is_file($temporaryPng)) {
                        unlink($temporaryPng);
                    }
                    $asset->setPath($stored['path']);
                    $asset->setOriginalName($name);
                    $asset->setMimeType($stored['mimeType']);
                    $asset->setAltText('Logo ' . $partnerName);
                    $asset->setWidth($stored['width']);
                    $asset->setHeight($stored['height']);
                    $asset->setVariants($stored['variants']);
                    $this->em->persist($asset);
                }
                $assets[$name] = $asset;
            }
            $this->em->flush();
            foreach ($logos as [$relative, $partnerName]) {
                $normalizedName = mb_strtolower($partnerName);
                foreach ($this->em->getRepository(Partner::class)->findAll() as $partner) {
                    if (mb_strtolower($partner->getName()) !== $normalizedName) {
                        continue;
                    }
                    $partner->setLogoMediaId($assets[basename($relative)]->getId());
                }
            }

            $assignments = [
                'plomberie-sanitaires' => ['sdb-01.jpeg', 'sdb-02.jpeg', 'sdb-03.jpeg', 'sdb-04.jpeg'],
                'renovation-interieure' => ['chambre-01.jpeg'],
                'menuiserie' => ['amenagement-01.jpg', 'amenagement-02.jpg'],
                'renovation-generale' => ['renovation_generale_1.jpg', 'renovation_generale_2.jpg', 'renovation_generale_3.jpg'],
                'parquet' => ['parquet-1.jpg', 'parquet-2.jpg', 'parquet-3.jpg'],
                'peinture-finitions' => ['peinture-finition_1.png', 'peinture-finition_2.jpg', 'peinture-finition_3.png'],
                'electricite' => ['electricite_1.jpg', 'electricite_2.jpg'],
            ];
            $knownIds = array_map(static fn (MediaAsset $asset) => $asset->getId(), $assets);
            foreach ($assignments as $slug => $names) {
                $category = $this->em->getRepository(RealisationCategory::class)->findOneBy(['slug' => $slug]);
                if (!$category) {
                    continue;
                }
                $extraIds = array_values(array_diff($category->getGallery(), $knownIds));
                $category->setGallery([...array_map(static fn (string $name) => $assets[$name]->getId(), $names), ...$extraIds]);
                if ($slug === 'renovation-interieure') {
                    if ($category->getDescription() === 'Aménagement d’une chambre au design moderne, avec rangements sur mesure, peinture décorative et parquet chaleureux.') {
                        $category->setDescription('Rénovation d’une chambre avec peinture bleu profond, parquet chaleureux et finitions soignées.');
                    }
                    if ($category->getTagService()?->getName() === 'Meubles de rangement sur mesure') {
                        $category->setTagService($this->em->getRepository(Service::class)->findOneBy(['name' => 'Peinture']));
                    }
                }
                if ($slug === 'menuiserie' && $category->getDescription() === 'Création d’un espace nuit avec mobilier intégré, rangements optimisés et décoration murale aux tons naturels.') {
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
                $hero->setMediaAssetId($assets['Photo_Accueil_1.jpg']->getId());
                $hero->setRatio('3:2');
                $hero->setMinimumSize('605x403, source fournie');
                $hero->setIdealDescription('Photo d’accueil du projet SBC');
            }

            $home = $this->em->getRepository(CmsDocument::class)->findOneBy(['resource' => 'pages', 'slug' => '']);
            if ($home) {
                $data = $home->getData();
                foreach ($data['sections'] ?? [] as $index => $section) {
                    if (($section['type'] ?? '') === 'hero') {
                        $data['sections'][$index]['photoSlotKey'] = 'PH-ACCUEIL-HERO';
                        $data['sections'][$index]['mediaAssetId'] = $assets['Photo_Accueil_1.jpg']->getId();
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

        $output->writeln('<info>Photos synchronisées pour les expertises et l’accueil. Photo principale : Photo_Accueil_1.jpg.</info>');

        return Command::SUCCESS;
    }
}
