<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\{CmsDocument, PhotoSlot, Setting};
use App\Service\{CmsCatalog, ContentResolver, LegalPageContent};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:cms:install', description: 'Initialise les pages composables sans écraser les pages déjà enregistrées.')]
class InstallCmsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ContentResolver $content,
        private readonly CmsCatalog $catalog,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ([
            ['PH-ABOUT-PORTRAIT', '4:5', 'Portrait du dirigeant'],
            ['PH-ABOUT-EQUIPE', '3:2', 'Équipe sur chantier'],
            ['PH-ABOUT-CHANTIER', '3:2', 'Chantier en cours'],
        ] as [$key, $ratio, $description]) {
            if (!$this->em->getRepository(PhotoSlot::class)->findOneBy(['slotKey' => $key])) {
                $slot = new PhotoSlot();
                $slot->setSlotKey($key);
                $slot->setRatio($ratio);
                $slot->setMinimumSize('min 1600x1600');
                $slot->setIdealDescription($description);
                $this->em->persist($slot);
            }
        }
        $this->em->flush();
        $texts = $this->content->mapByKey();
        $settings = [];
        foreach ($this->em->getRepository(Setting::class)->findAll() as $setting) {
            $settings[$setting->getSettingKey()] = $setting->getSettingValue();
        }
        foreach ([
            'cta_label' => 'Demander un devis', 'cta_url' => '/contact#devis',
            'footer_contact_title' => 'Coordonnées', 'footer_pages_title' => 'Pages',
            'footer_legal_title' => 'Informations', 'seo_description' => 'SBC, entrepreneur général en rénovation dans la région parisienne.',
        ] as $key => $value) {
            if (!array_key_exists($key, $settings)) {
                $setting = new Setting();
                $setting->setSettingKey($key);
                $setting->setSettingValue($value);
                $this->em->persist($setting);
            }
        }
        $text = static fn ($key, $default = '') => $texts[$key] ?? $default;
        $photo = fn ($key) => $this->em->getRepository(PhotoSlot::class)->findOneBy(['slotKey' => $key])?->getMediaAssetId();
        $block = static fn ($type, $title = '', $body = '', $extra = []) => [
            'id' => bin2hex(random_bytes(8)), 'type' => $type, 'title' => $title, 'body' => $body,
            'hidden' => false, ...$extra,
        ];
        $cta = ['linkLabel' => 'Demander un devis', 'linkUrl' => '/contact#devis'];
        $pages = [
            ['', 'Accueil', [
                $block('hero', $text('home.hero.title', 'Votre vision, notre expertise.'), $text('home.hero.subtitle'), [
                    'label' => 'SBC / Rénovation', 'photoSlotKey' => 'PH-ACCUEIL-HERO', ...$cta,
                ]),
                $block('text', '{experience_years} ans, à vos côtés.',
                    'De la première idée aux dernières finitions, nous coordonnons votre rénovation en région parisienne. Notre métier : transformer vos envies en espaces durables, fonctionnels et agréables à vivre.',
                    ['label' => 'Un projet, un interlocuteur']),
                $block('realisations', 'Des espaces transformés', '', ['label' => 'La preuve par les espaces']),
                $block('services', 'Nos métiers'),
                $block('cards', 'Méthode clé en main', '', ['cards' => [
                    ['title' => 'Gestion', 'body' => 'Planification et coordination du chantier'],
                    ['title' => 'Conception', 'body' => 'Validation des choix techniques et esthétiques'],
                    ['title' => 'Réalisation', 'body' => 'Exécution et contrôle qualité de chaque étape'],
                ]]),
                $block('reviews', 'Ils nous font confiance'),
                $block('announcements', 'Nos actualités'),
                $block('text', 'Un projet en tête ?', $text('home.consultation'), $cta),
                $block('partners', 'Nos partenaires', '', ['tone' => 'dark', 'spacing' => 'compact']),
            ]],
            ['services', 'Services', [
                $block('hero', 'Services', 'Des solutions adaptées à chaque projet.'),
                $block('services'), $block('faq', 'Questions fréquentes'),
            ]],
            ['realisations', 'Réalisations', [
                $block('hero', 'Des espaces transformés', $text('realisations.intro')),
                $block('realisations'),
                $block('text', 'Un projet en tête ?', 'Décrivez votre projet, nous revenons vers vous avec une proposition adaptée.', $cta),
            ]],
            ['a-propos', 'À propos', [
                $block('hero', '{slogan_primary}', $text('about.company'), ['photoSlotKey' => 'PH-ABOUT-PORTRAIT']),
                $block('gallery', 'Notre équipe', '', ['gallery' => array_values(array_filter([$photo('PH-ABOUT-EQUIPE'), $photo('PH-ABOUT-CHANTIER')]))]),
                $block('cards', 'Nos engagements', '', ['cards' => [
                    ['title' => 'Qualité', 'body' => 'Processus rigoureux, suivi à chaque étape.'],
                    ['title' => 'Durabilité', 'body' => 'Choix de solutions techniques adaptées au logement.'],
                    ['title' => 'Service sur mesure', 'body' => 'Coordination complète du projet selon vos priorités.'],
                ]]),
                $block('certifications', 'Nos certifications'),
                $block('text', 'Zones d’intervention', '{company_intervention_area}', $cta),
            ]],
            ['contact', 'Contact', [
                $block('hero', 'Parlons de votre projet', 'Décrivez vos besoins pour recevoir une estimation adaptée.'),
                $block('quote', 'Demande de devis'),
                $block('contact', 'Coordonnées'),
                $block('text', '', '', ['photoSlotKey' => 'PH-CONTACT-AMBIANCE']),
            ]],
            ['mentions-legales', 'Mentions légales', LegalPageContent::sections()],
            ['politique-confidentialite', 'Politique de confidentialité', [
                $block('hero', 'Politique de confidentialité'),
                $block('text', 'Traitement des données', 'Cette page présente le traitement des données dans le cadre des demandes de devis. Complétez ce texte avec les durées de conservation, les destinataires et les modalités d’exercice des droits.'),
            ]],
        ];
        $count = 0;
        foreach ($pages as $order => [$slug, $title, $sections]) {
            if (($settings['_cms_initialized'] ?? null) === '1') {
                break;
            }
            if ($this->em->getRepository(CmsDocument::class)->findOneBy(['resource' => 'pages', 'slug' => $slug])) {
                continue;
            }
            if (!isset($settings['_cms_initialized'])) {
                $marker = new Setting();
                $marker->setSettingKey('_cms_initialized');
                $marker->setSettingValue('1');
                $this->em->persist($marker);
            }
            $page = new CmsDocument();
            $this->catalog->apply('pages', $page, [
                'title' => $title, 'slug' => $slug, 'sections' => $sections, 'isPublished' => true,
                'showInNavigation' => $order < 6, 'showInFooter' => $order >= 6, 'seoDescription' => '',
            ]);
            $page->setSortOrder($order);
            $this->em->persist($page);
            ++$count;
        }
        $this->em->flush();
        $output->writeln("$count page(s) initialisée(s). Aucune page existante modifiée.");
        return Command::SUCCESS;
    }
}
