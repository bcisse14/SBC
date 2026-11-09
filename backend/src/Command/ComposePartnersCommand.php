<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\{CmsDocument, Partner, Setting};
use App\Service\CmsCatalog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:home:partners', description: 'Déplace les partenaires sur l’accueil et initialise les carrousels, une seule fois.')]
class ComposePartnersCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly CmsCatalog $catalog)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->em->getRepository(Setting::class)->findOneBy(['settingKey' => '_home_partners_v2'])) {
            $output->writeln('Partenaires déjà intégrés. Les modifications du Studio sont conservées.');
            return Command::SUCCESS;
        }
        $home = $this->em->getRepository(CmsDocument::class)->findOneBy(['resource' => 'pages', 'slug' => '']);
        if (!$home) {
            $output->writeln('<error>Accueil absent. Exécutez app:cms:install avant cette commande.</error>');
            return Command::FAILURE;
        }
        $this->em->wrapInTransaction(function () use ($home): void {
            $data = $home->getData();
            $sections = $data['sections'] ?? [];
            if (!array_filter($sections, static fn ($section) => ($section['type'] ?? '') === 'partners')) {
                $sections[] = [
                    'id' => bin2hex(random_bytes(8)), 'type' => 'partners', 'title' => 'Nos partenaires',
                    'body' => '', 'tone' => 'dark', 'spacing' => 'compact', 'hidden' => false,
                ];
            }
            $this->catalog->apply('pages', $home, ['sections' => $sections]);
            $page = $this->em->getRepository(CmsDocument::class)->findOneBy(['resource' => 'pages', 'slug' => 'partenaires']);
            if ($page) $this->em->remove($page);
            $legacyPartners = $this->em->getRepository(Setting::class)->findOneBy(['settingKey' => '_home_partners_v1']);
            if ($legacyPartners) {
                foreach ($this->em->getRepository(Partner::class)->findAll() as $partner) {
                    $this->em->remove($partner);
                }
            }
            if ($this->em->getRepository(Partner::class)->count([]) === 0 || $legacyPartners) {
                $partners = [
                    'Peinture' => ['Farrow & Ball', 'Tollens', 'Heulin Color', 'Mercadier'],
                    'Plâtrerie / isolation' => ['BATIMATOS', 'SOGEFI', 'La Plateforme du bâtiment'],
                    'Plomberie / sanitaires' => ['Cedeo', 'Richardson'],
                    'Matériaux & gros œuvres' => ['Point P', 'Leroy Merlin', 'Gedimat', 'La Plateforme du Bâtiment'],
                    'Carrelage' => ['Espace Aubade', 'Point P', 'Raboni'],
                ];
                $sortOrder = 0;
                foreach ($partners as $category => $names) {
                    foreach ($names as $name) {
                        $partner = new Partner();
                        $partner->setName($name);
                        $partner->setCategory($category);
                        $partner->setIsPublished(true);
                        $partner->setSortOrder($sortOrder++);
                        $this->em->persist($partner);
                    }
                }
            }
            $marker = new Setting();
            $marker->setSettingKey('_home_partners_v2');
            $marker->setSettingValue('1');
            $this->em->persist($marker);
        });
        $output->writeln('<info>Carrousels ajoutés en bas de l’accueil ; page partenaires supprimée. Contenus modifiables dans le Studio.</info>');
        return Command::SUCCESS;
    }
}
