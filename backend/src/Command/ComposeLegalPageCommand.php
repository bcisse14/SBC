<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\{CmsDocument, Setting};
use App\Service\{CmsCatalog, LegalPageContent};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:legal:compose', description: 'Complète une seule fois les mentions légales avec les coordonnées du créateur.')]
class ComposeLegalPageCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly CmsCatalog $catalog)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->em->getRepository(Setting::class)->findOneBy(['settingKey' => '_legal_karlsefni_v1'])) {
            $output->writeln('Mentions légales déjà initialisées. Les modifications du Studio sont conservées.');
            return Command::SUCCESS;
        }
        $page = $this->em->getRepository(CmsDocument::class)->findOneBy(['resource' => 'pages', 'slug' => 'mentions-legales']);
        if (!$page) {
            $output->writeln('<error>Page absente. Exécutez app:cms:install avant cette commande.</error>');
            return Command::FAILURE;
        }
        $this->em->wrapInTransaction(function () use ($page): void {
            $this->catalog->apply('pages', $page, ['sections' => LegalPageContent::sections()]);
            $marker = new Setting();
            $marker->setSettingKey('_legal_karlsefni_v1');
            $marker->setSettingValue('1');
            $this->em->persist($marker);
        });
        $output->writeln('<info>Mentions légales complétées. Les informations propres à SBC restent à vérifier dans le Studio.</info>');
        return Command::SUCCESS;
    }
}
