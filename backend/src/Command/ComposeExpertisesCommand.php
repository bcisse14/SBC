<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\{CmsDocument, RealisationCategory, Setting};
use App\Service\{CmsCatalog, ExpertiseContent};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:expertises:compose', description: 'Organise les réalisations en sept expertises avec pages de détail, une seule fois.')]
class ComposeExpertisesCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly CmsCatalog $catalog)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->em->getRepository(Setting::class)->findOneBy(['settingKey' => '_expertises_v1'])) {
            $output->writeln('Expertises déjà initialisées. Les modifications du Studio sont conservées.');
            return Command::SUCCESS;
        }
        $this->em->wrapInTransaction(function (): void {
            $repository = $this->em->getRepository(RealisationCategory::class);
            foreach (ExpertiseContent::all() as $order => $expertise) {
                $category = $repository->findOneBy(['slug' => $expertise['slug']])
                    ?? ($expertise['legacy'] ? $repository->findOneBy(['slug' => $expertise['legacy']]) : null);
                if (!$category) {
                    $category = new RealisationCategory();
                    $category->setGallery([]);
                    $this->em->persist($category);
                }
                $category->setSlug($expertise['slug']);
                $category->setTitle($expertise['title']);
                $category->setSummary($expertise['summary']);
                $category->setDescription($expertise['description']);
                $category->setSortOrder($order);
                $category->setStatus('published');
            }
            $page = $this->em->getRepository(CmsDocument::class)->findOneBy(['resource' => 'pages', 'slug' => 'realisations']);
            if ($page) {
                $sections = $page->getData()['sections'] ?? [];
                foreach ($sections as &$section) {
                    if (($section['type'] ?? '') === 'hero') {
                        $section['title'] = 'Nos expertises et réalisations';
                        $section['body'] = 'Sept métiers, une même exigence. Choisissez une expertise pour découvrir notre approche et nos chantiers.';
                        break;
                    }
                }
                unset($section);
                $this->catalog->apply('pages', $page, ['sections' => $sections, 'seoDescription' => 'Rénovation générale, intérieure, peinture, menuiserie, parquet, électricité et plomberie : découvrez les expertises de SBC.']);
            }
            $marker = new Setting();
            $marker->setSettingKey('_expertises_v1');
            $marker->setSettingValue('1');
            $this->em->persist($marker);
        });
        $output->writeln('<info>Sept expertises prêtes. Les photos existantes sont conservées ; les autres emplacements restent à renseigner dans le Studio.</info>');
        return Command::SUCCESS;
    }
}
