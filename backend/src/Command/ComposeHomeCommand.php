<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\{CmsDocument, Setting};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:home:compose', description: 'Resserre la présentation et place les réalisations au début de l’accueil, une seule fois.')]
class ComposeHomeCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->em->getRepository(Setting::class)->findOneBy(['settingKey' => '_home_rhythm_v1'])) {
            $output->writeln('Accueil déjà recomposé. Les modifications ultérieures du CMS sont conservées.');
            return Command::SUCCESS;
        }
        $home = $this->em->getRepository(CmsDocument::class)->findOneBy(['resource' => 'pages', 'slug' => '']);
        if (!$home) {
            $output->writeln('<error>Page d’accueil absente. Exécutez app:cms:install avant cette commande.</error>');
            return Command::FAILURE;
        }
        $data = $home->getData();
        $sections = $data['sections'] ?? [];
        $projects = null;
        $intro = null;
        foreach ($sections as $index => &$section) {
            if (($section['type'] ?? '') === 'realisations' && $projects === null && !($section['hidden'] ?? false)) {
                $projects = $index;
                $section['label'] = 'La preuve par les espaces';
            }
            if (($section['type'] ?? '') !== 'text') continue;
            if (preg_match('/^(?:\{experience_years\}|\d+) ans(?: de savoir-faire|, à vos côtés\.)$/u', $section['title'] ?? '')) {
                $intro = $index;
                $section['title'] = '{experience_years} ans, à vos côtés.';
                $section['label'] = 'Un projet, un interlocuteur';
                $section['body'] = 'De la première idée aux dernières finitions, nous coordonnons votre rénovation en région parisienne. Notre métier : transformer vos envies en espaces durables, fonctionnels et agréables à vivre.';
            }
            if (in_array($section['title'] ?? '', ['Rénovation intérieure complète', 'Rénovation extérieure moderne'], true)) {
                $section['hidden'] = true;
            }
        }
        unset($section);
        if ($intro === null || $projects === null) {
            $output->writeln('<error>Les blocs de présentation et de réalisations attendus sont absents. Aucun changement enregistré.</error>');
            return Command::FAILURE;
        }
        $projectSection = $sections[$projects];
        unset($sections[$projects]);
        $ordered = [];
        foreach ($sections as $index => $section) {
            $ordered[] = $section;
            if ($index === $intro) $ordered[] = $projectSection;
        }
        $data['sections'] = $ordered;
        $this->em->wrapInTransaction(function () use ($home, $data): void {
            $home->setData($data);
            $marker = new Setting();
            $marker->setSettingKey('_home_rhythm_v1');
            $marker->setSettingValue('1');
            $this->em->persist($marker);
        });
        $output->writeln('<info>Accueil recomposé : introduction courte, réalisations, prestations, méthode et contact. Les deux anciens blocs restent masqués dans le CMS.</info>');
        return Command::SUCCESS;
    }
}
