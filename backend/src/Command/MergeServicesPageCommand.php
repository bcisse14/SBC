<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\{CmsDocument, Setting};
use App\Service\CmsCatalog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:services:merge', description: 'Fusionne les pages Services et Réalisations en une seule page Services, une seule fois.')]
class MergeServicesPageCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly CmsCatalog $catalog)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->em->getRepository(Setting::class)->findOneBy(['settingKey' => '_services_merge_v1'])) {
            $output->writeln('Pages déjà fusionnées. Les modifications du Studio sont conservées.');
            return Command::SUCCESS;
        }
        $repository = $this->em->getRepository(CmsDocument::class);
        $services = $repository->findOneBy(['resource' => 'pages', 'slug' => 'services']);
        $realisations = $repository->findOneBy(['resource' => 'pages', 'slug' => 'realisations']);
        if (!$services) {
            $output->writeln('<error>Page Services introuvable.</error>');
            return Command::FAILURE;
        }
        $this->em->wrapInTransaction(function () use ($services, $realisations): void {
            $sections = $services->getData()['sections'] ?? [];
            $grid = null;
            $heroBody = 'Sept métiers, une même exigence. Choisissez une expertise pour découvrir notre approche et nos chantiers.';
            foreach ($realisations?->getData()['sections'] ?? [] as $section) {
                if (($section['type'] ?? '') === 'realisations') {
                    $grid = $section;
                    break;
                }
            }
            $grid ??= ['id' => bin2hex(random_bytes(8)), 'type' => 'realisations', 'title' => '', 'body' => '', 'cards' => [], 'gallery' => []];
            $hasGrid = false;
            foreach ($sections as $section) {
                $hasGrid = $hasGrid || ($section['type'] ?? '') === 'realisations';
            }
            $merged = [];
            foreach ($sections as $section) {
                $type = $section['type'] ?? '';
                if ($type === 'hero') {
                    $section['title'] = 'Nos services et expertises';
                    $section['body'] = $heroBody;
                }
                if ($type === 'services' && ($section['title'] ?? '') === '') {
                    $section['title'] = 'Toutes nos prestations';
                }
                $merged[] = $section;
                if ($type === 'hero' && !$hasGrid) {
                    $merged[] = $grid;
                }
            }
            $this->catalog->apply('pages', $services, [
                'sections' => $merged,
                'seoDescription' => 'Rénovation générale, intérieure, peinture, menuiserie, parquet, électricité et plomberie : découvrez les services et expertises de SBC.',
            ]);
            if ($realisations) {
                $this->em->remove($realisations);
            }
            $label = $this->em->getRepository(Setting::class)->findOneBy(['settingKey' => 'hero_projects']);
            if ($label && $label->getSettingValue() === 'Découvrir nos réalisations') {
                $label->setSettingValue('Découvrir nos expertises');
            }
            $marker = new Setting();
            $marker->setSettingKey('_services_merge_v1');
            $marker->setSettingValue('1');
            $this->em->persist($marker);
        });
        $output->writeln('<info>Page Services fusionnée avec les expertises ; la page Réalisations est supprimée (redirection automatique).</info>');
        return Command::SUCCESS;
    }
}
