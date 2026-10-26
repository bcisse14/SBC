<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\{Setting, MediaAsset};
use Doctrine\ORM\EntityManagerInterface;

class SiteEditor
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly CmsCatalog $catalog)
    {
    }

    public function fields(): array
    {
        $fields = [];
        $add = static function (string $group, string $key, string $label, string $value, string $type = 'text', array $extra = []) use (&$fields): void {
            $fields[$key] = ['group' => $group, 'label' => $label, 'default' => $value, 'type' => $type, ...$extra];
        };
        foreach ([
            'company_name' => ['Nom de l’entreprise', 'SBC'], 'company_phone' => ['Téléphone', ''],
            'company_email' => ['E-mail public', ''], 'company_address' => ['Adresse', ''],
            'company_hours' => ['Horaires', ''], 'company_intervention_area' => ['Zone d’intervention', 'Région parisienne'],
            'experience_years' => ['Années d’expérience', '25'], 'slogan_primary' => ['Signature principale', ''],
            'slogan_secondary' => ['Signature secondaire', ''], 'seo_description' => ['Description générale pour Google', ''],
        ] as $key => [$label, $value]) {
            $add('company', $key, $label, $value);
        }
        $add('company', 'brand_logo_id', 'Logo (vide pour utiliser le nom de l’entreprise)', '', 'media');
        $add('company', 'brand_icon_id', 'Icône du navigateur', '', 'media');
        foreach ([
            'cta_label' => ['Bouton principal', 'Demander un devis'],
            'cta_url' => ['Destination du bouton principal', '/contact#devis'],
            'announcement_top' => ['Bandeau d’information (vide pour masquer)', ''],
            'footer_contact_title' => ['Titre des coordonnées', 'Coordonnées'],
            'footer_pages_title' => ['Titre du menu des pages', 'Pages'],
            'footer_legal_title' => ['Titre des liens complémentaires', 'Informations'],
            'footer_kicker' => ['Introduction du pied de page', 'Parlons de votre projet'],
            'footer_signature' => ['Signature du pied de page', 'Rénovation, avec justesse.'],
        ] as $key => [$label, $value]) {
            $add('navigation', $key, $label, $value, $key === 'cta_url' ? 'url' : 'text');
        }
        foreach ([
            'notification_email' => ['Réception des demandes de devis', ''],
            'mail_from' => ['Expéditeur des e-mails', ''],
        ] as $key => [$label, $value]) {
            $add('delivery', $key, $label, $value);
        }
        foreach ([
            'paper' => ['Fond principal', '#faf9f5'], 'surface' => ['Fond secondaire', '#eeeede'],
            'ink' => ['Texte principal', '#26382e'], 'accent' => ['Boutons et accents', '#425b46'],
            'accent_dark' => ['Survol des boutons', '#2b4430'], 'border' => ['Bordures', '#d9dacf'],
            'hero' => ['Fond du hero', '#26382e'], 'hero_text' => ['Texte du hero', '#faf9f5'],
        ] as $key => [$label, $value]) {
            $add('palette', "theme_$key", $label, $value, 'color');
        }
        $fonts = ['serif' => 'Élégante / Instrument Serif', 'sans' => 'Contemporaine / Hanken Grotesk', 'classic' => 'Classique / Georgia'];
        $add('typography', 'theme_heading_font', 'Police des titres', 'serif', 'select', ['options' => $fonts]);
        $add('typography', 'theme_body_font', 'Police du texte', 'sans', 'select', ['options' => $fonts]);
        foreach ([
            'width' => ['Largeur du contenu (px)', '1240', 960, 1600],
            'radius' => ['Arrondi des blocs (px)', '12', 0, 40],
            'button_radius' => ['Arrondi des boutons (px)', '999', 0, 999],
            'text_size' => ['Taille de base du texte (px)', '16', 14, 20],
            'hero_height' => ['Hauteur minimale du hero sur ordinateur (px)', '640', 400, 850],
            'hero_split' => ['Largeur du texte dans le hero (%)', '45', 35, 65],
        ] as $key => [$label, $value, $min, $max]) {
            $add('layout', "theme_$key", $label, $value, 'number', ['min' => $min, 'max' => $max]);
        }
        $add('layout', 'theme_density', 'Espacement des sections', 'comfortable', 'select', ['options' => ['compact' => 'Compact', 'comfortable' => 'Confortable', 'airy' => 'Aéré']]);
        foreach (['show_header' => 'Afficher l’en-tête', 'sticky_header' => 'Garder le menu en haut de l’écran', 'show_footer' => 'Afficher le pied de page', 'show_mobile_contact' => 'Afficher la barre de contact sur mobile', 'show_hero_proof' => 'Afficher les repères sous le hero', 'show_service_photos' => 'Afficher les photos sous les prestations'] as $key => $label) {
            $add('layout', $key, $label, '1', 'boolean');
        }
        foreach ([
            'hero_kicker' => ['Repère au-dessus du titre', 'Rénovation & aménagement'],
            'hero_projects' => ['Lien vers les réalisations', 'Découvrir nos réalisations'],
            'hero_caption' => ['Légende de la photo', 'Une réalisation {company_name}'],
            'hero_photo_label' => ['Type de réalisation en légende', 'Aménagement sur mesure'],
            'proof_experience' => ['Repère d’expérience', '{experience_years} ans'],
            'proof_experience_detail' => ['Précision de l’expérience', 'd’expérience'],
            'proof_contact' => ['Deuxième repère', 'Un seul interlocuteur'],
            'proof_contact_detail' => ['Précision du deuxième repère', 'de l’idée au chantier'],
            'section_kicker' => ['Repère des sections sans libellé', 'Le sens du détail'],
        ] as $key => [$label, $value]) {
            $add('home', $key, $label, $value);
        }
        foreach ([
            'interieur' => ['Intérieur', 'Intérieurs', 'Des pièces dans lesquelles on se sent bien.'],
            'exterieur' => ['Extérieur', 'Extérieurs', 'Prendre soin de votre maison, au-dehors aussi.'],
            'projet_global' => ['Projet global', 'Projets sur mesure', 'Une vision d’ensemble, un accompagnement complet.'],
        ] as $key => [$label, $title, $description]) {
            $add('services', "family_{$key}_label", "Filtre : $label", $label);
            $add('services', "family_{$key}_title", "Titre : $label", $title);
            $add('services', "family_{$key}_description", "Présentation : $label", $description, 'textarea');
        }
        foreach ([
            'filter_all' => ['Filtre général', 'Tous'], 'services_discover' => ['Lien des familles', 'Découvrir les prestations'],
            'services_unit' => ['Unité du compteur', 'prestation'], 'services_photo_kicker' => ['Repère du bloc photo', 'Du savoir-faire au résultat'],
            'services_photo_title' => ['Titre du bloc photo', 'Des prestations qui prennent vie.'],
            'services_empty' => ['Aucun résultat', 'Aucune prestation ne correspond à ce filtre. Sélectionnez « Tous » pour retrouver nos services.'],
            'project_view' => ['Lien vers une réalisation', 'Voir la réalisation'], 'project_discover' => ['Lien des couvertures de projets', 'Voir le projet'],
            'photo_unit' => ['Unité photo', 'photo'], 'project_unit' => ['Repère des projets', 'Projet'],
            'learn_more' => ['Lien complémentaire', 'En savoir plus'],
        ] as $key => [$label, $value]) {
            $add('services', $key, $label, $value);
        }
        foreach ([
            'gallery_zoom' => ['Agrandissement', 'Agrandir'], 'gallery_close' => ['Fermeture', 'Fermer'],
            'gallery_scroll' => ['Aide au défilement', 'Faites défiler les {count} photos'],
            'gallery_previous' => ['Photo précédente', 'Photo précédente'], 'gallery_next' => ['Photo suivante', 'Photo suivante'],
            'menu_open' => ['Menu mobile', 'Menu'], 'menu_close' => ['Fermer le menu', 'Fermer'],
            'back_top' => ['Retour en haut', 'Retour en haut'], 'call_label' => ['Bouton téléphone mobile', 'Appeler'],
            'skip_link' => ['Accès direct au contenu', 'Aller au contenu'],
            'map_notice' => ['Information avant chargement de la carte', 'La carte se charge uniquement à votre demande.'],
            'map_button' => ['Bouton de la carte', 'Afficher la carte'], 'map_title' => ['Titre accessible de la carte', 'Carte de la région parisienne'],
            'map_url' => ['Adresse de la carte OpenStreetMap', 'https://www.openstreetmap.org/export/embed.html?bbox=1.45%2C48.55%2C3.05%2C49.15&layer=mapnik'],
            'announcement_news' => ['Type : actualité', 'Actualité'], 'announcement_offer' => ['Type : offre', 'Offre'],
            'announcement_job' => ['Type : emploi', 'Emploi'], 'announcement_information' => ['Type : information', 'Information'],
        ] as $key => [$label, $value]) {
            $add('interface', $key, $label, $value, $key === 'map_url' ? 'map' : 'text');
        }
        foreach ([
            'progress' => 'Progression', 'step_project' => 'Projet', 'step_details' => 'Détails', 'step_contact' => 'Coordonnées',
            'property_type' => 'Type de bien', 'house' => 'Maison', 'apartment' => 'Appartement', 'commercial' => 'Local',
            'services' => 'Prestations souhaitées', 'description' => 'Description du projet', 'city' => 'Ville',
            'postal_code' => 'Code postal', 'budget' => 'Budget indicatif', 'select' => 'Sélectionnez',
            'budget_low' => 'Moins de 15 000 EUR', 'budget_medium' => '15 000 EUR à 40 000 EUR',
            'budget_high' => '40 000 EUR à 80 000 EUR', 'budget_large' => 'Plus de 80 000 EUR',
            'timeline' => 'Délai souhaité', 'timeline_fast' => 'Dans 1 à 2 mois', 'timeline_medium' => 'Dans 3 à 6 mois',
            'timeline_flexible' => 'Calendrier souple', 'visit' => 'Je souhaite une visite sur place',
            'photos' => 'Photos du projet, 5 maximum', 'photos_help' => 'JPG, PNG ou WebP, 8 Mo maximum par fichier.',
            'first_name' => 'Prénom', 'last_name' => 'Nom', 'email' => 'E-mail', 'phone' => 'Téléphone',
            'callback' => 'Moment préféré pour être rappelé',
            'consent' => 'J’accepte que mes données soient utilisées pour traiter ma demande de devis.',
            'required' => 'Merci de vérifier les champs obligatoires.', 'previous' => 'Étape précédente', 'next' => 'Étape suivante',
            'sending' => 'Envoi en cours...', 'send' => 'Envoyer la demande',
            'error_property' => 'Choisissez un type de bien.', 'error_services' => 'Choisissez au moins une prestation.',
            'error_description' => 'Décrivez votre projet en 20 caractères minimum.',
            'error_city' => 'Indiquez une ville.', 'error_postal' => 'Indiquez un code postal à 5 chiffres.',
            'unavailable' => 'Le formulaire de devis est momentanément indisponible. Vous pouvez nous joindre directement aux coordonnées indiquées sur le site.',
        ] as $key => $value) {
            $add('form', "form_$key", $value, $value, 'textarea');
        }
        return $fields;
    }

    public function values(): array
    {
        $values = array_map(static fn ($field) => $field['default'], $this->fields());
        foreach ($this->em->getRepository(Setting::class)->findAll() as $row) {
            if (!str_starts_with($row->getSettingKey(), '_')) {
                $values[$row->getSettingKey()] = $row->getSettingValue();
            }
        }
        return $values;
    }

    public function save(array $incoming): void
    {
        $fields = $this->fields();
        $validated = [];
        foreach ($incoming as $key => $value) {
            if (!isset($fields[$key]) || !is_string($value) || mb_strlen($value) > 30000) {
                throw new \InvalidArgumentException('Réglage inconnu ou valeur invalide.');
            }
            $field = $fields[$key];
            $valid = match ($field['type']) {
                'color' => (bool) preg_match('/^#[0-9a-fA-F]{6}$/D', $value),
                'number' => ctype_digit($value) && (int) $value >= $field['min'] && (int) $value <= $field['max'],
                'boolean' => in_array($value, ['0', '1'], true),
                'select' => isset($field['options'][$value]),
                'map' => $value === '' || (str_starts_with($value, 'https://www.openstreetmap.org/export/embed.html?') && filter_var($value, FILTER_VALIDATE_URL)),
                'media' => $value === '' || (ctype_digit($value) && str_starts_with($this->em->find(MediaAsset::class, (int) $value)?->getMimeType() ?? '', 'image/')),
                default => true,
            };
            if (!$valid) {
                throw new \InvalidArgumentException($field['label'].' : valeur invalide.');
            }
            $validated[$key] = $this->catalog->normalizeSettingValue($key, $value);
        }
        foreach ($validated as $key => $value) {
            $row = $this->em->getRepository(Setting::class)->findOneBy(['settingKey' => $key]);
            if (!$row) {
                $row = new Setting();
                $row->setSettingKey($key);
                $this->em->persist($row);
            }
            $row->setSettingValue($value);
        }
        $this->em->flush();
    }
}
