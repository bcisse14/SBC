<?php

declare(strict_types=1);

namespace App\Service;

final class ExpertiseContent
{
    /** @return list<array{slug:string,legacy:?string,title:string,summary:string,description:string}> */
    public static function all(): array
    {
        return [
            [
                'slug' => 'renovation-generale', 'legacy' => null, 'title' => 'Rénovation générale',
                'summary' => 'Une rénovation complète de maison ou d’appartement, coordonnée par un seul interlocuteur, du diagnostic aux finitions.',
                'description' => <<<'TXT'
La rénovation générale regroupe l’ensemble des travaux nécessaires pour remettre à neuf un logement, qu’il s’agisse d’un appartement ancien, d’une maison à moderniser ou d’un bien à réorganiser avant emménagement.

Nous prenons en charge la coordination des différents corps de métier : vous n’avez qu’un seul interlocuteur, un planning clair et un suivi régulier du chantier.

## Ce que nous réalisons
- Démolition, dépose et préparation des supports
- Création ou déplacement de cloisons, doublage et isolation
- Reprise de l’électricité et de la plomberie
- Pose de sols, de carrelage et de revêtements
- Peinture, menuiseries intérieures et finitions
- Aménagement de cuisine et de salle de bains

## Notre façon de travailler
Chaque projet commence par une visite et l’écoute de vos besoins, puis par un devis détaillé. Nous organisons ensuite les interventions dans le bon ordre, contrôlons chaque étape et vous accompagnons jusqu’à la réception des travaux.
TXT,
            ],
            [
                'slug' => 'renovation-interieure', 'legacy' => 'renovation-chambre', 'title' => 'Rénovation intérieure et aménagement',
                'summary' => 'Repenser, optimiser et moderniser vos pièces de vie sans toucher à la structure du bâtiment.',
                'description' => <<<'TXT'
La rénovation intérieure permet de repenser les volumes, la circulation et l’ambiance de chaque pièce. Chambre, séjour, entrée, bureau : nous adaptons l’espace à votre façon de vivre.

Nous intervenons pièce par pièce ou sur l’ensemble du logement, avec des finitions soignées et des matériaux choisis avec vous.

## Ce que nous réalisons
- Réaménagement et optimisation des espaces
- Rénovation de chambres, séjours et pièces à vivre
- Rangements, dressings et mobilier intégré
- Revêtements de sols et de murs
- Éclairage et ambiance décorative

## Notre façon de travailler
Nous partons de l’usage de la pièce, de sa lumière et de vos contraintes pour proposer des solutions durables et agréables à vivre, dans le respect du budget et des délais annoncés.
TXT,
            ],
            [
                'slug' => 'peinture-finitions', 'legacy' => null, 'title' => 'Peinture et finitions',
                'summary' => 'Préparation des supports, peintures, enduits et revêtements muraux pour un rendu net et durable.',
                'description' => <<<'TXT'
La peinture et les finitions donnent le caractère final d’une rénovation. Un beau résultat dépend autant de la préparation des supports que du choix des teintes.

Nous réalisons des travaux soignés, dans des logements vides comme occupés, en protégeant soigneusement les sols et le mobilier.

## Ce que nous réalisons
- Rebouchage, ponçage et enduit des murs et plafonds
- Peinture intérieure, murs, plafonds et boiseries
- Peinture décorative, aplats et effets de couleur
- Pose de papier peint et de revêtements muraux
- Reprise de fissures et remise en état avant peinture

## Notre façon de travailler
Nous vous conseillons sur les teintes, les finis et les produits adaptés à chaque pièce, puis nous veillons à la propreté du chantier jusqu’aux dernières retouches.
TXT,
            ],
            [
                'slug' => 'menuiserie', 'legacy' => 'amenagement-sur-mesure', 'title' => 'Menuiserie',
                'summary' => 'Portes, rangements et mobilier sur mesure : des menuiseries intégrées qui optimisent chaque espace.',
                'description' => <<<'TXT'
La menuiserie améliore à la fois le confort, l’esthétique et l’usage d’un logement. Placards, bibliothèques, têtes de lit, bureaux intégrés ou portes : chaque réalisation est dessinée pour son emplacement.

Le sur-mesure permet d’exploiter les recoins, de gagner du rangement et d’obtenir des finitions parfaitement ajustées.

## Ce que nous réalisons
- Rangements, placards et dressings sur mesure
- Bibliothèques et bureaux intégrés
- Meubles de salle de bains et agencements
- Habillages muraux et têtes de lit
- Pose et remplacement de portes intérieures
- Fenêtres et menuiseries extérieures

## Notre façon de travailler
Nous relevons les dimensions, étudions les contraintes et vous proposons un agencement adapté, puis nous fabriquons ou posons les éléments avec précision.
TXT,
            ],
            [
                'slug' => 'parquet', 'legacy' => null, 'title' => 'Parquet',
                'summary' => 'Fourniture et pose de parquets, rénovation et finitions pour des sols chaleureux et durables.',
                'description' => <<<'TXT'
Le parquet apporte chaleur et caractère à une pièce. Neuf ou ancien, il demande un support préparé avec soin et une pose adaptée à l’usage du logement.

Nous vous aidons à choisir le revêtement en fonction de la pièce, de l’exposition et de l’entretien souhaité.

## Ce que nous réalisons
- Pose de parquet massif, contrecollé ou stratifié
- Préparation et ragréage des supports
- Plinthes et finitions de rives
- Rénovation de parquets existants
- Raccords de seuils et transitions entre pièces

## Notre façon de travailler
Le sol est contrôlé avant la pose, les produits sont choisis avec vous et la mise en œuvre respecte les temps de séchage et d’adaptation du matériau.
TXT,
            ],
            [
                'slug' => 'electricite', 'legacy' => null, 'title' => 'Électricité',
                'summary' => 'Installation, rénovation et mise en conformité électrique pour un logement sûr et confortable.',
                'description' => <<<'TXT'
Une installation électrique sûre et adaptée aux usages actuels est indispensable dans un logement rénové. Nous intervenons aussi bien sur une réfection complète que sur des travaux ciblés.

## Ce que nous réalisons
- Remplacement ou création de tableaux électriques
- Mise en conformité et sécurisation de l’installation
- Prises, interrupteurs et circuits dédiés
- Éclairage intérieur et extérieur
- Préparation de la domotique et des réseaux de communication

## Notre façon de travailler
Nous étudions vos besoins pièce par pièce, planifions les passages de câbles avant les finitions et vérifions l’installation avant remise du chantier.
TXT,
            ],
            [
                'slug' => 'plomberie-sanitaires', 'legacy' => 'renovation-salle-de-bains', 'title' => 'Plomberie et sanitaires',
                'summary' => 'Plomberie, salles de bains et sanitaires : création, rénovation et remplacement d’équipements.',
                'description' => <<<'TXT'
La plomberie et les sanitaires touchent au confort du quotidien. Qu’il s’agisse de rénover une salle de bains complète ou de remplacer un équipement, nous soignons l’étanchéité, l’évacuation et les finitions.

## Ce que nous réalisons
- Rénovation complète de salles de bains
- Douches à l’italienne, baignoires et receveurs
- Meubles vasques, robinetterie et sèche-serviettes
- WC, lave-mains et sanitaires suspendus
- Reprise d’alimentations et d’évacuations
- Carrelage, faïence et étanchéité

## Notre façon de travailler
Nous définissons avec vous l’implantation, les matériaux et les équipements, puis nous coordonnons les interventions pour limiter la durée du chantier.
TXT,
            ],
        ];
    }
}
