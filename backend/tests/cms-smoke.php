<?php

declare(strict_types=1);

use App\Entity\{CmsDocument, MediaAsset, User, Setting, ContentEntry, ContentRevision, PhotoSlot, QuoteRequest, QuoteAttachment};
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;

require dirname(__DIR__) . '/vendor/autoload.php';
(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');

$kernel = new Kernel('dev', true);
$kernel->boot();
$em = $kernel->getContainer()->get('doctrine')->getManager();
$user = new User();
$suffix = bin2hex(random_bytes(6));
$password = bin2hex(random_bytes(24));
$user->setEmail("cms-smoke-$suffix@example.invalid");
$user->setRoles(['ROLE_ADMIN']);
$user->setPassword(password_hash($password, PASSWORD_BCRYPT));
$em->persist($user);
$em->flush();

$client = HttpClient::create(['base_uri' => getenv('CMS_TEST_BASE_URL') ?: 'http://nginx', 'max_redirects' => 0]);
$cookies = [];
$csrf = '';
$created = [];
$mediaIds = [];
$checks = 0;
$settingRestore = [];
$contentRestore = [];
$photoRestore = [];
$testQuoteId = null;
$attachmentPath = null;
$request = function (string $method, string $path, ?array $data = null, int $expected = 200, bool $withCsrf = true, ?FormDataPart $multipart = null, bool $binary = false) use ($client, &$cookies, &$csrf, &$checks): array {
    $headers = ['Accept' => 'application/json'];
    if ($cookies !== []) $headers['Cookie'] = implode('; ', $cookies);
    if ($csrf && $withCsrf) $headers['X-CSRF-TOKEN'] = $csrf;
    $options = ['headers' => $headers];
    if ($data !== null) $options['json'] = $data;
    if ($multipart) {
        $options['headers'] = [...$headers, ...$multipart->getPreparedHeaders()->toArray()];
        $options['body'] = $multipart->bodyToIterable();
    }
    $response = $client->request($method, $path, $options);
    foreach ($response->getHeaders(false)['set-cookie'] ?? [] as $cookie) {
        $pair = explode(';', $cookie, 2)[0];
        $cookies[explode('=', $pair, 2)[0]] = $pair;
    }
    $body = $response->getContent(false);
    if ($response->getStatusCode() !== $expected) {
        throw new RuntimeException("$method $path : attendu $expected, reçu {$response->getStatusCode()} : " . substr($body, 0, 500));
    }
    ++$checks;
    if ($binary) return ['body' => $body, 'headers' => $response->getHeaders(false)];
    return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
};
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) throw new RuntimeException($message);
    ++$checks;
};
$create = function (string $resource, array $data) use ($request, &$created): array {
    $row = $request('POST', "/api/admin/cms/$resource", $data, 201);
    $created[] = [$resource, $row['id']];
    return $row;
};

try {
    $request('GET', '/api/admin/cms/schema', null, 401);
    $request('GET', '/api/admin/site-editor', null, 401);
    $request('POST', '/api/admin/login', ['email' => $user->getEmail(), 'password' => $password]);
    $csrf = $request('GET', '/api/admin/csrf')['tokens']['admin'];
    $schema = $request('GET', '/api/admin/cms/schema');
    $assert(count($schema['resources']) === 9 && count($schema['blocks']) === 13, 'Schéma incomplet.');
    foreach (array_keys($schema['resources']) as $resource) $request('GET', "/api/admin/cms/$resource");
    $editor = $request('GET', '/api/admin/site-editor');
    $assert(count($editor['fields']) >= 120, 'Réglages du constructeur incomplets.');
    foreach (array_keys($editor['fields']) as $key) {
        $setting = $em->getRepository(Setting::class)->findOneBy(['settingKey' => $key]);
        $settingRestore[$key] = ['exists' => $setting !== null, 'value' => $setting?->getSettingValue()];
    }
    $effective = array_intersect_key($editor['values'], $editor['fields']);
    $request('PUT', '/api/admin/site-editor', $effective);
    $roundTrip = $request('GET', '/api/admin/site-editor')['values'];
    foreach ($effective as $key => $value) $assert($roundTrip[$key] === $value, "Réglage non conservé : $key");
    $request('PUT', '/api/admin/site-editor', ['theme_width' => '1100'], 419, false);
    foreach ([
        ['theme_paper' => 'red'], ['theme_width' => '9000'], ['theme_width' => '1100px'],
        ['theme_heading_font' => 'arbitrary-font'], ['show_header' => 'true'],
        ['map_url' => 'https://example.com'], ['brand_logo_id' => '99999999'],
        ['unknown_setting' => 'value'], ['cta_url' => 'javascript:alert(1)'],
    ] as $invalid) {
        $request('PUT', '/api/admin/site-editor', $invalid, 422);
    }
    $request('PUT', '/api/admin/site-editor', ['theme_paper' => '#abcdef', 'theme_width' => '9000'], 422);
    $assert($request('GET', '/api/admin/site-editor')['values']['theme_paper'] === $editor['values']['theme_paper'], 'Enregistrement partiel après erreur.');
    $request('PUT', '/api/admin/site-editor', ['theme_paper' => '#f0f4ee', 'theme_width' => '1100', 'services_photo_title' => 'Contrôle du constructeur']);
    $customized = $request('GET', '/api/site/bootstrap')['settings'];
    $assert($customized['theme_width'] === '1100' && $customized['theme_paper'] === '#f0f4ee' && $customized['services_photo_title'] === 'Contrôle du constructeur', 'Réglages absents du site public.');
    $request('PUT', '/api/admin/site-editor', array_intersect_key($editor['values'], $settingRestore));
    $request('POST', '/api/admin/cms/pages', ['title' => 'Sans jeton'], 419, false);
    $request('POST', '/api/admin/cms/pages', ['title' => 'Adresse admin réservée', 'slug' => trim(getenv('ADMIN_PATH') ?: '/atelier-admin', '/')], 422);

    $pixel = new Imagick();
    $pixel->newImage(16, 16, new ImagickPixel('white'));
    $pixel->setImageFormat('png');
    foreach ([30 * 1024 * 1024, 30 * 1024 * 1024 + 1] as $size) {
        $temporary = tmpfile();
        if ($temporary === false || fwrite($temporary, $pixel->getImageBlob()) === false || !ftruncate($temporary, $size)) {
            throw new RuntimeException('Impossible de préparer le fichier de test de taille limite.');
        }
        rewind($temporary);
        $largeImage = new FormDataPart([
            'file' => new DataPart($temporary, 'cms-limit.png', 'image/png'),
        ]);
        $limitAsset = $request('POST', '/api/admin/media', null, $size === 30 * 1024 * 1024 ? 201 : 422, true, $largeImage);
        if (isset($limitAsset['id'])) {
            $mediaIds[] = $limitAsset['id'];
            $request('DELETE', "/api/admin/media/{$limitAsset['id']}");
            $mediaIds = array_values(array_diff($mediaIds, [$limitAsset['id']]));
        }
        unset($largeImage);
        fclose($temporary);
    }
    $image = new FormDataPart([
        'file' => new DataPart($pixel->getImageBlob(), 'cms-test.png', 'image/png'),
        'altText' => 'Photo de contrôle',
    ]);
    $asset = $request('POST', '/api/admin/media', null, 201, true, $image);
    $mediaIds[] = $asset['id'];
    foreach (['brand_logo_id', 'brand_icon_id'] as $key) {
        $request('PUT', '/api/admin/site-editor', [$key => (string) $asset['id']]);
        $request('DELETE', "/api/admin/media/{$asset['id']}", null, 409);
        $request('PUT', '/api/admin/site-editor', [$key => $editor['values'][$key]]);
    }
    $changedAsset = $request('PATCH', "/api/admin/media/{$asset['id']}", ['altText' => 'Description modifiée']);
    $assert($changedAsset['altText'] === 'Description modifiée', 'Description du média non conservée.');
    $request('PATCH', "/api/admin/media/{$asset['id']}", ['altText' => ''], 422);
    $request('PATCH', "/api/admin/media/{$asset['id']}", ['altText' => 'Sans jeton'], 419, false);
    $slots = $request('GET', '/api/admin/cms/photos');
    foreach ($slots as $slot) {
        $photoRestore[$slot['id']] = $slot['mediaAssetId'];
        $updatedSlot = $request('PATCH', "/api/admin/cms/photos/{$slot['id']}", ['mediaAssetId' => $asset['id']]);
        $assert($updatedSlot['mediaAssetId'] === $asset['id'], 'Emplacement photo non modifiable.');
        $request('PATCH', "/api/admin/cms/photos/{$slot['id']}", ['mediaAssetId' => $slot['mediaAssetId']]);
    }
    if ($slots) {
        $request('DELETE', "/api/admin/cms/photos/{$slots[0]['id']}", null, 422);
        $request('POST', '/api/admin/cms/photos', [], 422);
    }
    foreach ($request('GET', '/api/admin/content') as $entry) {
        if (!str_starts_with($entry['contentKey'], 'email.')) continue;
        $contentRestore[$entry['id']] = $entry['customValue'];
        $result = $request('PATCH', "/api/admin/content/{$entry['id']}", ['customValue' => 'Contrôle {prenom} {nom} {ville}']);
        $assert($result['value'] === 'Contrôle {prenom} {nom} {ville}', 'Texte e-mail non enregistré.');
        $result = $request('PATCH', "/api/admin/content/{$entry['id']}", ['customValue' => '']);
        $assert($result['value'] === '', 'Texte e-mail vide remplacé.');
        $request('PATCH', "/api/admin/content/{$entry['id']}", ['customValue' => $entry['customValue'] ?? $entry['defaultValue']]);
    }
    $quote = new QuoteRequest();
    foreach (['PropertyType' => 'maison', 'RequestedServices' => ['Cuisine'], 'Description' => 'Demande de contrôle du CMS, aucun envoi.', 'City' => 'Paris', 'PostalCode' => '75001', 'BudgetRange' => 'moins_15k', 'Timeline' => 'souple', 'FirstName' => 'Contrôle', 'LastName' => $suffix, 'Email' => "test-$suffix@example.invalid", 'Phone' => '0100000000'] as $field => $value) {
        $quote->{'set'.$field}($value);
    }
    $em->persist($quote);
    $em->flush();
    $testQuoteId = $quote->getId();
    $attachmentPath = 'var/share/cms-smoke-'.$suffix.'.png';
    if (file_put_contents(dirname(__DIR__).'/'.$attachmentPath, $pixel->getImageBlob()) === false) throw new RuntimeException('Impossible de créer la pièce jointe de contrôle.');
    $attachment = new QuoteAttachment();
    $attachment->setQuoteRequest($quote);
    $attachment->setStoragePath($attachmentPath);
    $attachment->setOriginalName('controle.png');
    $attachment->setMimeType('image/png');
    $attachment->setSizeBytes(strlen($pixel->getImageBlob()));
    $em->persist($attachment);
    $em->flush();
    $mailer = new class implements \Symfony\Component\Mailer\MailerInterface {
        public array $messages = [];

        public function send(\Symfony\Component\Mime\RawMessage $message, ?\Symfony\Component\Mailer\Envelope $envelope = null): void
        {
            $this->messages[] = $message;
        }
    };
    $adminPath = getenv('ADMIN_PATH') ?: '/atelier-admin';
    (new \App\Service\QuoteMailer($mailer, $em, 'http://localhost:8080', $adminPath, 'recipient@example.invalid', 'sender@example.invalid'))->sendForQuote($quote, 1);
    $assert(count($mailer->messages) === 2, 'Confirmation et notification non préparées.');
    $notification = $mailer->messages[1];
    $assert($notification instanceof \Symfony\Bridge\Twig\Mime\TemplatedEmail, 'Notification sans gabarit.');
    $assert($notification->getContext()['link'] === 'http://localhost:8080/'.trim($adminPath, '/').'?quote='.$testQuoteId, 'Lien de notification hors de la nouvelle adresse admin.');
    foreach (['new', 'contacted', 'visit_planned', 'quote_sent', 'won', 'lost', 'archived'] as $status) {
        $request('PATCH', "/api/admin/quotes/$testQuoteId/status", ['status' => $status]);
        $matching = array_values(array_filter($request('GET', '/api/admin/quotes'), static fn ($item) => $item['id'] === $testQuoteId));
        $assert($matching[0]['status'] === $status, 'Statut de devis non conservé : '.$status);
    }
    $request('PATCH', "/api/admin/quotes/$testQuoteId/status", ['status' => 'invalid'], 422);
    $request('PATCH', "/api/admin/quotes/$testQuoteId/status", ['status' => 'new'], 419, false);
    $download = $request('GET', "/api/admin/quotes/$testQuoteId/attachments/{$attachment->getId()}", null, 200, true, null, true);
    $assert($download['body'] === $pixel->getImageBlob(), 'Contenu de la pièce jointe incorrect.');
    $request('GET', "/api/admin/quotes/0/attachments/{$attachment->getId()}", null, 404);
    $em->clear();
    $stored = $em->find(MediaAsset::class, $asset['id']);
    $paths = [$stored->getPath(), ...array_column($stored->getVariants(), 'path')];
    $assert(count($paths) > 1, 'Les variantes ne sont pas générées.');
    $section = ['id' => "test-$suffix", 'type' => 'text', 'title' => "Test\u{2014}normalisé", 'body' => 'Contenu de contrôle', 'mediaAssetId' => $asset['id'], 'layout' => 'image-left', 'tone' => 'soft', 'spacing' => 'compact', 'alignment' => 'center', 'width' => 'narrow', 'columns' => '2', 'imageFit' => 'contain', 'hideImage' => true];
    $page = $create('pages', ['title' => 'Page de contrôle', 'slug' => "cms-test-$suffix", 'sections' => [$section], 'showInNavigation' => true]);
    $assert(!str_contains($page['sections'][0]['title'], "\u{2014}"), 'Le tiret interdit a été conservé.');
    foreach (['layout', 'tone', 'spacing', 'alignment', 'width', 'columns', 'imageFit', 'hideImage'] as $key) {
        $assert($page['sections'][0][$key] === $section[$key], "Présentation non conservée : $key");
    }
    $request('PATCH', "/api/admin/cms/pages/{$page['id']}", ['sections' => [[...$section, 'layout' => 'unknown']]], 422);
    $request('PATCH', "/api/admin/cms/pages/{$page['id']}", ['sections' => [[...$section, 'columns' => 100]]], 422);
    $request('PATCH', "/api/admin/cms/pages/{$page['id']}", ['sections' => [[...$section, 'presentation' => 'cards']]], 422);
    $steps = $request('PATCH', "/api/admin/cms/pages/{$page['id']}", ['sections' => [[...$section, 'type' => 'cards', 'presentation' => 'steps', 'cards' => [['title' => 'Étape', 'body' => 'Description']]]]]);
    $assert($steps['sections'][0]['presentation'] === 'steps', 'Présentation de collection non conservée.');
    $request('PATCH', "/api/admin/cms/pages/{$page['id']}", ['sections' => [$section]]);
    $bootstrap = $request('GET', '/api/site/bootstrap');
    $assert(!in_array($page['id'], array_column($bootstrap['pages'], 'id'), true), 'Un brouillon est visible.');
    $request('DELETE', "/api/admin/media/{$asset['id']}", null, 409);
    $request('PATCH', "/api/admin/cms/pages/{$page['id']}", ['isPublished' => true]);
    $bootstrap = $request('GET', '/api/site/bootstrap');
    $assert(in_array($page['id'], array_column($bootstrap['pages'], 'id'), true), 'La page publiée reste invisible.');
    $assert(isset($bootstrap['media'][$asset['id']]), 'La photo de la page est absente du bootstrap.');
    $request('POST', '/api/admin/cms/pages', ['title' => 'Doublon', 'slug' => $page['slug']], 422);
    $request('POST', '/api/admin/cms/pages', ['title' => 'Réservé', 'slug' => 'private-atelier'], 422);
    $request('POST', '/api/admin/cms/pages', ['title' => 'Vide', 'slug' => "vide-$suffix", 'isPublished' => true], 422);
    $request('PATCH', "/api/admin/cms/pages/{$page['id']}", ['sections' => [['id' => 'x', 'type' => 'html']]], 422);
    $request('PATCH', "/api/admin/cms/pages/{$page['id']}", ['sections' => [['id' => 'x', 'type' => []]]], 422);
    $request('PATCH', "/api/admin/cms/pages/{$page['id']}", ['title' => null], 422);
    $request('PATCH', "/api/admin/cms/pages/{$page['id']}", ['isPublished' => null], 422);
    $request('PATCH', "/api/admin/cms/pages/{$page['id']}", ['sections' => [['id' => 'x', 'type' => 'text', 'linkUrl' => 'javascript:alert(1)']]], 422);
    $request('PATCH', "/api/admin/cms/pages/{$page['id']}", ['sections' => [['id' => 'x', 'type' => 'text', 'mediaAssetId' => 99999999]]], 422);
    $request('PATCH', "/api/admin/cms/pages/{$page['id']}", ['sections' => [['id' => 'x', 'type' => 'text', 'gallery' => [null]]]], 422);
    $request('PUT', '/api/admin/cms/pages/order', ['ids' => [$page['id'], $page['id']]], 422);
    $create('pages', ['title' => 'Deuxième page de contrôle', 'slug' => "cms-second-$suffix", 'sections' => [$section]]);
    $all = $request('GET', '/api/admin/cms/pages');
    $order = array_column($all, 'id');
    $last = count($order) - 1;
    [$order[$last], $order[$last - 1]] = [$order[$last - 1], $order[$last]];
    $request('PUT', '/api/admin/cms/pages/order', ['ids' => $order]);
    $assert(array_column($request('GET', '/api/admin/cms/pages'), 'id') === $order, 'Ordre non persistant.');
    $request('PUT', '/api/admin/cms/pages/order', ['ids' => array_column($all, 'id')]);

    $announcement = $create('announcements', [
        'title' => 'Annonce de contrôle', 'slug' => "cms-test-$suffix", 'type' => 'job',
        'body' => 'Description de l’annonce', 'isPublished' => true,
        'startsAt' => '2099-01-01T10:00',
    ]);
    $assert(!in_array($announcement['id'], array_column($request('GET', '/api/site/bootstrap')['announcements'], 'id'), true), 'Annonce future diffusée.');
    $request('PATCH', "/api/admin/cms/announcements/{$announcement['id']}", ['startsAt' => '']);
    $assert(in_array($announcement['id'], array_column($request('GET', '/api/site/bootstrap')['announcements'], 'id'), true), 'Annonce active absente.');
    $request('PATCH', "/api/admin/cms/announcements/{$announcement['id']}", ['endsAt' => '2000-01-01T10:00']);
    $assert(!in_array($announcement['id'], array_column($request('GET', '/api/site/bootstrap')['announcements'], 'id'), true), 'Annonce expirée visible.');
    $request('PATCH', "/api/admin/cms/announcements/{$announcement['id']}", ['type' => 'autre'], 422);
    $request('PATCH', "/api/admin/cms/announcements/{$announcement['id']}", ['startsAt' => '2026-10-23T10:00', 'endsAt' => '2026-10-22T10:00'], 422);

    $service = $create('services', ['name' => "Test $suffix", 'serviceGroup' => 'interieur', 'description' => 'Description', 'items' => ['Ligne 1', ''], 'mediaAssetId' => $asset['id']]);
    $assert($service['items'] === ['Ligne 1'], 'Les lignes vides ne sont pas ignorées.');
    $category = $create('realisations', ['title' => "Test $suffix", 'serviceId' => $service['id'], 'gallery' => [$asset['id']], 'isPublished' => true]);
    $assert($category['gallery'] === [$asset['id']] && $category['isPublished'], 'Galerie non enregistrée.');
    $request('DELETE', "/api/admin/cms/services/{$service['id']}");
    $created = array_values(array_filter($created, static fn ($item) => $item !== ['services', $service['id']]));
    $categories = $request('GET', '/api/admin/cms/realisations');
    $unlinked = array_values(array_filter($categories, static fn ($item) => $item['id'] === $category['id']))[0];
    $assert($unlinked['serviceId'] === null, 'La suppression du service laisse une référence cassée.');
    $partner = $create('partners', ['name' => "Test $suffix", 'category' => 'Peinture', 'websiteUrl' => 'https://example.com', 'logoMediaId' => $asset['id']]);
    $assert($partner['category'] === 'Peinture', 'Catégorie du carrousel non conservée.');
    $assert(!in_array($partner['name'], array_column($request('GET', '/api/site/bootstrap')['partners'], 'name'), true), 'Partenaire brouillon affiché.');
    $request('PATCH', "/api/admin/cms/partners/{$partner['id']}", ['isPublished' => true, 'category' => 'Isolation']);
    $publicPartner = array_values(array_filter($request('GET', '/api/site/bootstrap')['partners'], static fn ($item) => $item['name'] === $partner['name']));
    $assert(count($publicPartner) === 1 && $publicPartner[0]['category'] === 'Isolation' && $publicPartner[0]['logoMediaId'] === $asset['id'], 'Partenaire publié absent de sa catégorie ou sans logo.');
    $request('DELETE', "/api/admin/media/{$asset['id']}", null, 409);
    $create('certifications', ['name' => "Test $suffix", 'verificationUrl' => '/contact']);
    $create('faq', ['question' => "Test $suffix ?", 'answer' => 'Réponse.', 'isPublished' => true]);
    $create('reviews', ['authorName' => "Test $suffix", 'city' => 'Paris', 'projectType' => 'Peinture', 'rating' => 5, 'message' => 'Très bien.']);
    $request('POST', '/api/admin/cms/reviews', ['authorName' => 'Test', 'city' => 'Paris', 'projectType' => 'Test', 'rating' => 6, 'message' => 'Test'], 422);
    foreach ($created as [$resource, $id]) {
        $rows = $request('GET', "/api/admin/cms/$resource");
        $row = array_values(array_filter($rows, static fn ($item) => $item['id'] === $id))[0];
        $row['isPublished'] = !$row['isPublished'];
        $savedRow = $request('PATCH', "/api/admin/cms/$resource/$id", $row);
        $assert($savedRow['isPublished'] === $row['isPublished'], "Publication non conservée : $resource");
        $order = array_column($rows, 'id');
        $request('PUT', "/api/admin/cms/$resource/order", ['ids' => $order]);
        $assert(array_column($request('GET', "/api/admin/cms/$resource"), 'id') === $order, "Ordre non conservé : $resource");
    }

    foreach (array_reverse($created) as [$resource, $id]) $request('DELETE', "/api/admin/cms/$resource/$id");
    $created = [];
    $request('DELETE', "/api/admin/media/{$asset['id']}");
    $mediaIds = [];
    foreach ($paths as $path) $assert(!is_file(dirname(__DIR__) . '/' . $path), "Fichier conservé : $path");
    $request('GET', "/api/media/{$asset['id']}", null, 404);
    $request('DELETE', "/api/admin/cms/pages/{$page['id']}", null, 404);
    $request('POST', '/api/admin/logout');
    $request('GET', '/api/admin/me', null, 401);
    echo "OK : $checks contrôles CMS réussis.\n";
} finally {
    $em->clear();
    foreach ($contentRestore as $id => $value) {
        $entry = $em->find(ContentEntry::class, $id);
        if ($entry) $entry->setCustomValue($value);
    }
    foreach ($photoRestore as $id => $value) {
        $slot = $em->find(PhotoSlot::class, $id);
        if ($slot) $slot->setMediaAssetId($value);
    }
    foreach ($em->getRepository(ContentRevision::class)->findBy(['editor' => $user->getId()]) as $revision) $em->remove($revision);
    if ($testQuoteId && ($quote = $em->find(QuoteRequest::class, $testQuoteId))) $em->remove($quote);
    if ($attachmentPath && is_file(dirname(__DIR__).'/'.$attachmentPath)) unlink(dirname(__DIR__).'/'.$attachmentPath);
    foreach ($settingRestore as $key => $original) {
        $setting = $em->getRepository(Setting::class)->findOneBy(['settingKey' => $key]);
        if ($setting) {
            if (!$original['exists']) $em->remove($setting); else $setting->setSettingValue($original['value']);
        }
    }
    $em->flush();
    foreach (array_reverse($created) as [$resource, $id]) {
        try { $request('DELETE', "/api/admin/cms/$resource/$id"); }
        catch (Throwable $error) { fwrite(STDERR, "Nettoyage $resource/$id : {$error->getMessage()}\n"); }
    }
    foreach ($mediaIds as $id) {
        try { $request('DELETE', "/api/admin/media/$id"); }
        catch (Throwable $error) { fwrite(STDERR, "Nettoyage média $id : {$error->getMessage()}\n"); }
    }
    $em->clear();
    $testUser = $em->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
    if ($testUser) { $em->remove($testUser); $em->flush(); }
    $kernel->shutdown();
}
