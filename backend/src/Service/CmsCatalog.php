<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\{Certification, CmsDocument, FaqItem, MediaAsset, Partner, PhotoSlot, RealisationCategory, Review, Service};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\String\Slugger\AsciiSlugger;

class CmsCatalog
{
    public const BLOCKS = [
        'hero' => 'Grande introduction', 'text' => 'Texte et image', 'gallery' => 'Galerie photo',
        'cards' => 'Cartes libres', 'services' => 'Services', 'realisations' => 'Réalisations',
        'reviews' => 'Avis clients', 'faq' => 'Questions fréquentes', 'partners' => 'Partenaires',
        'certifications' => 'Certifications', 'quote' => 'Formulaire de devis',
        'contact' => 'Coordonnées et carte', 'announcements' => 'Annonces',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TextNormalizer $normalizer,
    ) {
    }

    public function definitions(): array
    {
        $field = static fn (string $label, string $type = 'text', bool $required = false, array $extra = []) =>
            ['label' => $label, 'type' => $type, 'required' => $required, ...$extra];
        $title = $field('Titre', 'text', true, ['max' => 140]);
        $body = $field('Texte', 'textarea', true);
        $image = $field('Photo', 'media');
        $url = $field('Lien', 'url', false, ['max' => 255]);

        return [
            'pages' => ['label' => 'Pages du site', 'class' => CmsDocument::class, 'fields' => [
                'title' => $title,
                'slug' => $field('Adresse de la page (vide pour l’accueil)', 'slug', false, ['max' => 120]),
                'seoDescription' => $field('Description pour les moteurs de recherche', 'textarea', false, ['max' => 320]),
                'showInNavigation' => $field('Afficher dans les menus', 'boolean'),
                'showInFooter' => $field('Afficher dans les liens complémentaires du pied de page', 'boolean'),
                'sections' => $field('Sections de la page', 'blocks'),
            ]],
            'announcements' => ['label' => 'Annonces', 'class' => CmsDocument::class, 'fields' => [
                'title' => $title,
                'slug' => $field('Identifiant unique', 'slug', true, ['max' => 120]),
                'type' => $field('Type d’annonce', 'select', true, ['options' => [
                    'news' => 'Actualité', 'offer' => 'Offre commerciale', 'job' => 'Emploi', 'information' => 'Information',
                ]]),
                'body' => $body, 'mediaAssetId' => $image, 'linkUrl' => $url,
                'linkLabel' => $field('Texte du lien'),
                'startsAt' => $field('Début de diffusion (heure de Paris)', 'datetime'),
                'endsAt' => $field('Fin de diffusion (heure de Paris)', 'datetime'),
            ]],
            'services' => ['label' => 'Services', 'class' => Service::class, 'fields' => [
                'name' => $field('Nom', 'text', true, ['max' => 120]),
                'serviceGroup' => $field('Famille', 'select', true, ['options' => [
                    'interieur' => 'Intérieur', 'exterieur' => 'Extérieur', 'projet_global' => 'Projet global',
                ]]),
                'description' => $body, 'items' => $field('Prestations (une par ligne)', 'lines'),
                'mediaAssetId' => $image,
            ]],
            'realisations' => ['label' => 'Réalisations', 'class' => RealisationCategory::class, 'fields' => [
                'title' => $title, 'description' => $field('Description', 'textarea'),
                'serviceId' => $field('Service associé', 'service'),
                'gallery' => $field('Galerie (la première photo sert de couverture)', 'gallery'),
            ]],
            'partners' => ['label' => 'Partenaires', 'class' => Partner::class, 'fields' => [
                'name' => $title, 'category' => $field('Catégorie', 'text', false, ['max' => 80]),
                'websiteUrl' => $url, 'logoMediaId' => $image,
            ]],
            'certifications' => ['label' => 'Certifications', 'class' => Certification::class, 'fields' => [
                'name' => $title, 'description' => $field('Description', 'textarea'),
                'verificationUrl' => $url, 'logoMediaId' => $image,
            ]],
            'faq' => ['label' => 'Questions fréquentes', 'class' => FaqItem::class, 'fields' => [
                'question' => $field('Question', 'text', true, ['max' => 255]), 'answer' => $body,
            ]],
            'reviews' => ['label' => 'Avis clients', 'class' => Review::class, 'fields' => [
                'authorName' => $field('Auteur', 'text', true, ['max' => 120]),
                'city' => $field('Ville', 'text', true, ['max' => 120]),
                'projectType' => $field('Projet', 'text', true, ['max' => 120]),
                'rating' => $field('Note sur 5', 'number', true, ['min' => 1, 'max' => 5]),
                'message' => $body, 'source' => $field('Source', 'text', false, ['max' => 120]),
            ]],
            'photos' => ['label' => 'Emplacements photo', 'class' => PhotoSlot::class, 'fixed' => true, 'fields' => [
                'slotKey' => $field('Repère', 'text', true, ['max' => 120, 'readonly' => true]),
                'ratio' => $field('Proportions', 'text', true, ['max' => 30]),
                'minimumSize' => $field('Dimensions conseillées', 'text', true, ['max' => 100]),
                'idealDescription' => $field('Description', 'text', true, ['max' => 255]),
                'mediaAssetId' => $image,
            ]],
        ];
    }

    public function definition(string $resource): array
    {
        return $this->definitions()[$resource] ?? throw new \InvalidArgumentException('Module inconnu.');
    }

    public function rows(string $resource, bool $published = false): array
    {
        $def = $this->definition($resource);
        $criteria = $def['class'] === CmsDocument::class ? ['resource' => $resource] : [];
        if ($published && !($def['fixed'] ?? false)) {
            $criteria[$resource === 'realisations' ? 'status' : 'isPublished'] = $resource === 'realisations' ? 'published' : true;
        }
        return $this->em->getRepository($def['class'])->findBy(
            $criteria, ($def['fixed'] ?? false) ? ['slotKey' => 'ASC'] : ['sortOrder' => 'ASC', 'id' => 'ASC'],
        );
    }

    public function find(string $resource, int $id): ?object
    {
        $row = $this->em->getRepository($this->definition($resource)['class'])->find($id);
        return $row instanceof CmsDocument && $row->getResource() !== $resource ? null : $row;
    }

    public function serialize(string $resource, object $row): array
    {
        if ($row instanceof CmsDocument) {
            return [...$row->getData(), 'slug' => $row->getSlug(), 'id' => $row->getId(),
                'sortOrder' => $row->getSortOrder(), 'isPublished' => $row->isPublished()];
        }
        $accessor = PropertyAccess::createPropertyAccessor();
        $data = ['id' => $row->getId()];
        foreach ($this->definition($resource)['fields'] as $name => $field) {
            $data[$name] = $row instanceof RealisationCategory && $name === 'serviceId'
                ? $row->getTagService()?->getId() : $accessor->getValue($row, $name);
        }
        if (!($this->definition($resource)['fixed'] ?? false)) {
            $data['sortOrder'] = $row->getSortOrder();
            $data['isPublished'] = $row instanceof RealisationCategory ? $row->getStatus() === 'published' : $row->isPublished();
        }
        return $data;
    }

    public function apply(string $resource, object $row, array $incoming): void
    {
        $def = $this->definition($resource);
        $existing = $row->getId() ? $this->serialize($resource, $row) : [];
        $data = [];
        foreach ($def['fields'] as $name => $field) {
            $value = array_key_exists($name, $incoming) ? $incoming[$name] : ($existing[$name] ?? match ($field['type']) {
                'boolean' => false, 'blocks', 'lines', 'gallery' => [], 'media', 'service' => null, default => '',
            });
            $data[$name] = $this->validateValue($value, $field);
            if (($field['readonly'] ?? false) && $row->getId() && $data[$name] !== $existing[$name]) {
                throw new \InvalidArgumentException('Le repère d’un emplacement partagé ne peut pas être renommé.');
            }
        }
        $published = array_key_exists('isPublished', $incoming) ? $incoming['isPublished'] : ($existing['isPublished'] ?? false);
        if (!is_bool($published)) {
            throw new \InvalidArgumentException('Le statut de publication est invalide.');
        }
        if ($row instanceof CmsDocument) {
            $slug = $data['slug'];
            if ($resource === 'pages') {
                $adminSlug = trim((string) ($_ENV['ADMIN_PATH'] ?? '/atelier-admin'), '/');
                if (in_array($slug, ['api', 'internal', 'private-atelier', 'login', 'sitemap.xml', 'robots.txt', $adminSlug], true)) {
                    throw new \InvalidArgumentException('Cette adresse est réservée.');
                }
                if ($published && $data['sections'] === []) {
                    throw new \InvalidArgumentException('Ajoutez au moins une section avant de publier.');
                }
            }
            $duplicate = $this->em->getRepository(CmsDocument::class)->findOneBy(['resource' => $resource, 'slug' => $slug]);
            if ($duplicate && $duplicate->getId() !== $row->getId()) {
                throw new \InvalidArgumentException('Cette adresse est déjà utilisée.');
            }
            if ($resource === 'announcements' && $data['startsAt'] && $data['endsAt'] && $data['startsAt'] >= $data['endsAt']) {
                throw new \InvalidArgumentException('La fin de diffusion doit être postérieure au début.');
            }
            $row->setResource($resource);
            $row->setSlug($slug);
            $row->setData($data);
        } else {
            $accessor = PropertyAccess::createPropertyAccessor();
            foreach ($data as $name => $value) {
                if ($row instanceof RealisationCategory && $name === 'serviceId') {
                    $row->setTagService($value ? $this->em->find(Service::class, $value) : null);
                } else {
                    $accessor->setValue($row, $name, $value);
                }
            }
            if ($row instanceof RealisationCategory && !$row->getId()) {
                $base = (new AsciiSlugger('fr'))->slug($data['title'])->lower()->toString() ?: 'categorie';
                $slug = $base;
                for ($i = 2; $this->em->getRepository(RealisationCategory::class)->findOneBy(['slug' => $slug]); ++$i) {
                    $slug = $base . '-' . $i;
                }
                $row->setSlug($slug);
            }
        }
        if (!($def['fixed'] ?? false)) {
            if ($row instanceof RealisationCategory) {
                $row->setStatus($published ? 'published' : 'draft');
            } else {
                $row->setIsPublished($published);
            }
        }
    }

    private function validateValue(mixed $value, array $field): mixed
    {
        $label = $field['label'];
        $type = $field['type'];
        if ($type === 'blocks') {
            return $this->validateBlocks($value);
        }
        if ($type === 'gallery') {
            if (!is_array($value) || !array_is_list($value) || count($value) > 100 || count(array_unique($value, SORT_REGULAR)) !== count($value)) {
                throw new \InvalidArgumentException("$label : galerie invalide.");
            }
            return array_map(fn ($id) => $this->validateValue($id, ['label' => $label, 'type' => 'media', 'required' => true]), $value);
        }
        if ($type === 'service') {
            if ($value === null || $value === '') {
                return null;
            }
            if (!is_int($value) || !$this->em->find(Service::class, $value)) {
                throw new \InvalidArgumentException("$label : service introuvable.");
            }
            return $value;
        }
        if ($type === 'boolean') {
            if (!is_bool($value)) {
                throw new \InvalidArgumentException("$label : valeur invalide.");
            }
            return $value;
        }
        if ($type === 'media') {
            if ($value === null || $value === '') {
                if ($field['required'] ?? false) {
                    throw new \InvalidArgumentException("$label : image obligatoire.");
                }
                return null;
            }
            if (!is_int($value) || !$this->em->find(MediaAsset::class, $value)) {
                throw new \InvalidArgumentException("$label : média introuvable.");
            }
            $asset = $this->em->find(MediaAsset::class, $value);
            if (!str_starts_with($asset->getMimeType(), 'image/')) {
                throw new \InvalidArgumentException("$label : choisissez une image.");
            }
            return $value;
        }
        if ($type === 'lines') {
            if (!is_array($value) || !array_is_list($value) || count($value) > 100) {
                throw new \InvalidArgumentException("$label : liste invalide.");
            }
            $lines = array_map(fn ($line) => $this->validateValue($line, ['label' => $label, 'type' => 'text', 'max' => 500]), $value);
            return array_values(array_filter($lines, static fn ($line) => $line !== ''));
        }
        if ($type === 'number') {
            if (!is_int($value) || $value < ($field['min'] ?? 0) || $value > ($field['max'] ?? 100000)) {
                throw new \InvalidArgumentException("$label : nombre hors limites.");
            }
            return $value;
        }
        if (!is_string($value)) {
            throw new \InvalidArgumentException("$label : texte attendu.");
        }
        $value = trim($this->normalizer->normalize($value)['value']);
        if (($field['required'] ?? false) && $value === '') {
            throw new \InvalidArgumentException("$label : champ obligatoire.");
        }
        if (mb_strlen($value) > ($field['max'] ?? 30000)) {
            throw new \InvalidArgumentException("$label : texte trop long.");
        }
        if ($type === 'slug' && $value !== '' && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $value)) {
            throw new \InvalidArgumentException("$label : utilisez des lettres minuscules, chiffres et traits d’union.");
        }
        if ($type === 'url' && $value !== '' && !$this->isSafeUrl($value)) {
            throw new \InvalidArgumentException("$label : utilisez une adresse https, http, un chemin local ou une ancre.");
        }
        if ($type === 'select' && !array_key_exists($value, $field['options'])) {
            throw new \InvalidArgumentException("$label : choix invalide.");
        }
        if ($type === 'datetime' && $value !== '') {
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, new \DateTimeZone('Europe/Paris'));
            if (!$date || $date->format('Y-m-d\TH:i') !== $value) {
                throw new \InvalidArgumentException("$label : date invalide.");
            }
        }
        return $value;
    }

    private function isSafeUrl(string $value): bool
    {
        return !preg_match('/[\x00-\x20\\\\]/', $value) && (
            (str_starts_with($value, '/') && !str_starts_with($value, '//'))
            || str_starts_with($value, '#')
            || (filter_var($value, FILTER_VALIDATE_URL) && in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true))
        );
    }

    public function normalizeSettingValue(string $key, string $value): string
    {
        if ($key === 'cta_url') {
            return $this->validateValue($value, ['label' => 'Destination du bouton de contact', 'type' => 'url']);
        }
        return $this->normalizer->normalize($value)['value'];
    }

    public function validateBlocks(mixed $blocks): array
    {
        if (!is_array($blocks) || !array_is_list($blocks) || count($blocks) > 60) {
            throw new \InvalidArgumentException('Une page peut contenir jusqu’à 60 sections.');
        }
        $out = [];
        $ids = [];
        foreach ($blocks as $block) {
            if (!is_array($block) || !is_string($block['type'] ?? null) || !isset(self::BLOCKS[$block['type']])) {
                throw new \InvalidArgumentException('Type de section inconnu.');
            }
            $id = $block['id'] ?? '';
            if (!is_string($id) || !preg_match('/^[a-zA-Z0-9-]{1,80}$/D', $id) || in_array($id, $ids, true)) {
                throw new \InvalidArgumentException('Identifiant de section invalide ou dupliqué.');
            }
            $ids[] = $id;
            $item = ['id' => $id, 'type' => $block['type']];
            $presentations = match ($block['type']) {
                'cards' => ['auto', 'cards', 'steps'],
                'services' => ['auto', 'families', 'catalog'],
                'realisations' => ['auto', 'galleries'],
                default => ['auto'],
            };
            $item['presentation'] = $this->validateValue($block['presentation'] ?? 'auto', [
                'label' => 'Présentation de la collection', 'type' => 'select', 'options' => array_fill_keys($presentations, ''),
            ]);
            foreach ([
                'layout' => ['auto', 'split', 'stacked', 'image-left'],
                'tone' => ['default', 'soft', 'dark'],
                'spacing' => ['default', 'compact', 'airy'],
                'alignment' => ['left', 'center'],
                'width' => ['full', 'narrow'],
                'columns' => ['auto', '1', '2', '3', '4'],
                'imageFit' => ['cover', 'contain'],
            ] as $name => $options) {
                $item[$name] = $this->validateValue($block[$name] ?? $options[0], [
                    'label' => "Présentation : $name", 'type' => 'select', 'options' => array_fill_keys($options, ''),
                ]);
            }
            foreach ([
                'title' => 'text', 'body' => 'textarea', 'label' => 'text',
                'linkLabel' => 'text', 'linkUrl' => 'url', 'mediaAssetId' => 'media', 'hidden' => 'boolean', 'photoSlotKey' => 'text', 'hideImage' => 'boolean',
            ] as $name => $type) {
                $item[$name] = $this->validateValue(array_key_exists($name, $block) ? $block[$name] : match ($type) {
                    'media' => null, 'boolean' => false, default => '',
                }, ['label' => "Section : $name", 'type' => $type]);
            }
            if ($item['photoSlotKey'] !== '' && !$this->em->getRepository(PhotoSlot::class)->findOneBy(['slotKey' => $item['photoSlotKey']])) {
                throw new \InvalidArgumentException('Emplacement photo introuvable.');
            }
            $gallery = $block['gallery'] ?? [];
            if (!is_array($gallery) || !array_is_list($gallery) || count($gallery) > 100 || count(array_unique($gallery, SORT_REGULAR)) !== count($gallery)) {
                throw new \InvalidArgumentException('Galerie invalide (100 photos maximum).');
            }
            $item['gallery'] = array_map(fn ($id) => $this->validateValue($id, ['label' => 'Galerie', 'type' => 'media', 'required' => true]), $gallery);
            if (in_array(null, $item['gallery'], true)) {
                throw new \InvalidArgumentException('Une photo de la galerie est manquante.');
            }
            $cards = $block['cards'] ?? [];
            if (!is_array($cards) || !array_is_list($cards) || count($cards) > 30) {
                throw new \InvalidArgumentException('Liste de cartes invalide (30 maximum).');
            }
            $item['cards'] = [];
            foreach ($cards as $card) {
                if (!is_array($card)) {
                    throw new \InvalidArgumentException('Carte invalide.');
                }
                $item['cards'][] = [
                    'title' => $this->validateValue($card['title'] ?? '', ['label' => 'Titre de carte', 'type' => 'text']),
                    'body' => $this->validateValue($card['body'] ?? '', ['label' => 'Texte de carte', 'type' => 'textarea']),
                ];
            }
            $out[] = $item;
        }
        return $out;
    }

    public function activeAnnouncements(): array
    {
        $now = (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')))->format('Y-m-d\TH:i');
        return array_values(array_filter(array_map(fn ($row) => $this->serialize('announcements', $row), $this->rows('announcements', true)),
            static fn ($row) => (!$row['startsAt'] || $row['startsAt'] <= $now) && (!$row['endsAt'] || $row['endsAt'] > $now)));
    }
}
