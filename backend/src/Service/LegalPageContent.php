<?php

declare(strict_types=1);

namespace App\Service;

final class LegalPageContent
{
    public static function sections(): array
    {
        $text = static fn (string $id, string $title, string $body, array $extra = []) => [
            'id' => $id, 'type' => 'text', 'title' => $title, 'body' => $body,
            'hidden' => false, 'spacing' => 'compact', ...$extra,
        ];

        return [
            ['id' => 'legal-hero', 'type' => 'hero', 'title' => 'Mentions légales', 'body' => '', 'hidden' => false],
            $text('legal-editor', 'Éditeur du site',
                'Le site présente les activités de {company_name}. Dénomination sociale complète, forme juridique, capital social, siège social, numéro SIREN/SIRET, immatriculation au RCS ou au RNE et numéro de TVA intracommunautaire, le cas échéant : à compléter par SBC. Coordonnées de l’entreprise : {company_address} ; e-mail : {company_email} ; téléphone : {company_phone}.'),
            $text('legal-publication', 'Responsable de la publication',
                'Le responsable de la publication est le représentant légal de SBC. Nom et prénom : à compléter. Karlsefni intervient comme créateur technique du site et n’est pas désigné, à ce titre, comme éditeur ou responsable de la publication.'),
            $text('legal-creator', 'Conception et réalisation',
                'Site conçu et réalisé par Karlsefni. Contact : cissebafode.pro@gmail.com. Portfolio : https://bafodecisse.fr/.',
                ['linkLabel' => 'Découvrir le portfolio', 'linkUrl' => 'https://bafodecisse.fr/']),
            $text('legal-host', 'Hébergement',
                'Les coordonnées de l’hébergeur du site de production restent à compléter : dénomination sociale, adresse postale et numéro de téléphone. La version de démonstration publiée sur Vercel est une présentation distincte ; elle ne permet pas l’administration ni l’envoi de demandes de devis.'),
            $text('legal-rights', 'Propriété intellectuelle',
                'Les textes, photographies, éléments graphiques et développements du site sont protégés par les droits de propriété intellectuelle applicables et restent la propriété de leurs titulaires respectifs. Sauf autorisation ou exception prévue par la loi, leur reproduction, adaptation ou réutilisation est interdite. Les marques et logos cités appartiennent à leurs propriétaires.'),
            $text('legal-data', 'Données personnelles et cookies',
                'Sur le site connecté, les informations transmises via le formulaire servent à traiter les demandes et à recontacter leurs auteurs. Aucun formulaire de demande n’est envoyé depuis la démonstration. La politique de confidentialité doit préciser le responsable du traitement, la base légale, les destinataires, les durées de conservation et les modalités d’exercice des droits. Le fonctionnement du site peut nécessiter des cookies techniques, notamment pour la connexion à l’atelier. Tout traceur non nécessaire soumis au consentement doit rester désactivé avant accord.',
                ['linkLabel' => 'Politique de confidentialité', 'linkUrl' => '/politique-confidentialite']),
            $text('legal-information', 'Informations et contact',
                'Les informations du site sont fournies à titre indicatif et peuvent évoluer. Une présentation de prestation ne constitue pas un devis ni un engagement contractuel. Malgré le soin apporté aux contenus, des erreurs ou interruptions peuvent survenir ; les obligations légales applicables restent inchangées. Pour une question sur les prestations, contactez SBC. Pour un problème technique lié à la création du site, contactez Karlsefni.',
                ['linkLabel' => 'Contacter SBC', 'linkUrl' => '/contact']),
            $text('legal-completion', 'Informations à compléter avant publication définitive',
                'Ce modèle générique ne constitue pas une validation juridique. SBC doit compléter et vérifier les informations d’identification, le responsable de publication, les coordonnées de l’hébergeur ainsi que les mentions propres à son activité, notamment l’assurance professionnelle, la médiation de la consommation et la confidentialité, selon sa situation.'),
        ];
    }
}
