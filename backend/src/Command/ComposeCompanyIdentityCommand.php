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

#[AsCommand(name: 'app:company:identity', description: 'Renseigne une seule fois la raison sociale, l’adresse et la carte de SBC.')]
class ComposeCompanyIdentityCommand extends Command
{
    private const VALUES = [
        'company_legal_name' => 'SARL SBC',
        'company_address' => '183-189 avenue de Choisy, 75013 Paris',
        'map_url' => 'https://www.openstreetmap.org/export/embed.html?bbox=2.3524%2C48.8265%2C2.3624%2C48.8315&layer=mapnik&marker=48.8290475%2C2.3574614',
    ];

    public function __construct(private readonly EntityManagerInterface $em, private readonly CmsCatalog $catalog)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $settings = $this->em->getRepository(Setting::class);
        if ($settings->findOneBy(['settingKey' => '_company_identity_v1'])) {
            $output->writeln('Identité déjà renseignée. Les modifications du Studio sont conservées.');
            return Command::SUCCESS;
        }
        $this->em->wrapInTransaction(function () use ($settings): void {
            foreach ([...self::VALUES, '_company_identity_v1' => '1'] as $key => $value) {
                $setting = $settings->findOneBy(['settingKey' => $key]) ?? new Setting();
                $setting->setSettingKey($key);
                $setting->setSettingValue($value);
                $this->em->persist($setting);
            }
            $page = $this->em->getRepository(CmsDocument::class)->findOneBy(['resource' => 'pages', 'slug' => 'mentions-legales']);
            if ($page) {
                $sections = $page->getData()['sections'] ?? [];
                foreach ($sections as &$section) {
                    if (($section['id'] ?? '') === 'legal-editor' && ($section['body'] ?? '') === LegalPageContent::PREVIOUS_EDITOR_BODY) {
                        $section['body'] = LegalPageContent::EDITOR_BODY;
                    }
                }
                unset($section);
                $this->catalog->apply('pages', $page, ['sections' => $sections, 'showInFooter' => true]);
            }
        });
        $output->writeln('<info>SARL SBC, 183-189 avenue de Choisy, 75013 Paris : coordonnées, mentions légales, devis et carte mis à jour.</info>');
        return Command::SUCCESS;
    }
}
