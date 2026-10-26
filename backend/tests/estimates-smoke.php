<?php

declare(strict_types=1);

use App\Entity\{Estimate, EstimateProfile, QuoteRequest, User};
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpClient\HttpClient;

require dirname(__DIR__).'/vendor/autoload.php';
(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
$kernel = new Kernel('dev', true);
$kernel->boot();
$em = $kernel->getContainer()->get('doctrine')->getManager();
$suffix = bin2hex(random_bytes(6));
$password = bin2hex(random_bytes(24));
$user = new User();
$user->setEmail("estimate-test-$suffix@example.invalid");
$user->setPassword(password_hash($password, PASSWORD_BCRYPT));
$user->setRoles(['ROLE_ADMIN']);
$quote = new QuoteRequest();
$quote->setFirstName('Alice');
$quote->setLastName('Contrôle');
$quote->setEmail("client-$suffix@example.invalid");
$quote->setPhone('0600000000');
$quote->setCity('Paris');
$quote->setPostalCode('75001');
$quote->setPropertyType('maison');
$quote->setDescription('Travaux de peinture et isolation.');
$quote->setBudgetRange('15k_40k');
$quote->setTimeline('souple');
$quote->setRequestedServices(['Peinture']);
$em->persist($user);
$em->persist($quote);
$em->flush();
$userId = $user->getId();
$quoteId = $quote->getId();
$profileIds = $estimateIds = $cookies = [];
$csrf = '';
$checks = 0;
$client = HttpClient::create(['base_uri' => getenv('CMS_TEST_BASE_URL') ?: 'http://nginx', 'max_redirects' => 0]);
$request = function (string $method, string $path, ?array $data = null, int $expected = 200, bool $token = true, bool $binary = false) use ($client, &$csrf, &$cookies, &$checks): array {
    $headers = ['Accept' => 'application/json'];
    if ($cookies) $headers['Cookie'] = implode('; ', $cookies);
    if ($csrf && $token) $headers['X-CSRF-TOKEN'] = $csrf;
    $options = ['headers' => $headers];
    if ($data !== null) $options['json'] = $data;
    $response = $client->request($method, $path, $options);
    foreach ($response->getHeaders(false)['set-cookie'] ?? [] as $cookie) {
        $pair = explode(';', $cookie, 2)[0];
        $cookies[explode('=', $pair, 2)[0]] = $pair;
    }
    $body = $response->getContent(false);
    if ($response->getStatusCode() !== $expected) throw new RuntimeException("$method $path : attendu $expected, reçu {$response->getStatusCode()} : ".substr($body, 0, 500));
    ++$checks;
    return $binary ? ['body' => $body, 'headers' => $response->getHeaders(false)] : json_decode($body, true, 512, JSON_THROW_ON_ERROR);
};
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) throw new RuntimeException($message);
    ++$checks;
};
try {
    foreach (['', '/profiles', '/seed', '/1/pdf'] as $path) $request('GET', '/api/admin/estimates'.$path, null, 401);
    $request('POST', '/api/admin/login', ['email' => $user->getEmail(), 'password' => $password]);
    $csrf = $request('GET', '/api/admin/csrf')['tokens']['admin'];
    $seed = $request('GET', '/api/admin/estimates/seed?requestId='.$quoteId);
    $assert($seed['client']['name'] === 'Alice Contrôle' && $seed['client']['email'] === $quote->getEmail(), 'Coordonnées de la demande non reprises.');
    $assert($seed['introduction'] === $quote->getDescription() && $seed['siteAddress'] === '75001 Paris', 'Projet et chantier non repris.');
    $request('GET', '/api/admin/estimates/seed?requestId=99999999', null, 404);
    $company = ['name' => 'Entreprise '.$suffix, 'address' => "1 rue du Test\n75001 Paris", 'email' => 'company@example.invalid', 'phone' => '0600000001', 'registration' => 'SIRET de contrôle', 'vatNumber' => 'FR-test', 'insurance' => 'Assurance de contrôle'];
    $contact = [...$seed['client'], 'address' => '2 rue des Tests, 75001 Paris'];
    foreach (['company' => $company, 'client' => $contact] as $kind => $data) {
        $request('POST', '/api/admin/estimates/profiles', ['kind' => $kind, 'data' => $data], 419, false);
        $profile = $request('POST', '/api/admin/estimates/profiles', ['kind' => $kind, 'data' => $data], 201);
        $profileIds[] = $profile['id'];
        $assert($profile['data'] === $data && $profile['kind'] === $kind, 'Fiche non conservée.');
    }
    $request('POST', '/api/admin/estimates/profiles', ['kind' => 'client', 'data' => ['name' => '']], 422);
    $request('POST', '/api/admin/estimates/profiles', ['kind' => 'client', 'data' => ['name' => 'Client', 'email' => 'invalid']], 422);
    $request('POST', '/api/admin/estimates/profiles', ['kind' => 'unknown', 'data' => $contact], 422);
    $request('POST', '/api/admin/estimates/profiles', ['kind' => 'client', 'data' => [...$contact, 'name' => null]], 422);
    $request('POST', '/api/admin/estimates/profiles', ['kind' => 'client', 'data' => [...$contact, 'name' => str_repeat('a', 181)]], 422);
    $request('PUT', '/api/admin/estimates/profiles/'.$profileIds[1], ['kind' => 'company', 'data' => $company], 422);
    $data = [...$seed, 'company' => $company, 'client' => $contact, 'title' => 'Rénovation de contrôle', 'lines' => [
        ['description' => 'Peinture', 'quantity' => '2,5', 'unit' => 'm²', 'unitPrice' => '100', 'vatRate' => '5.5'],
        ['description' => 'Isolation', 'quantity' => '3', 'unit' => 'm²', 'unitPrice' => '20', 'vatRate' => '10'],
    ], 'conditions' => 'Règlement à réception. Délai : 30 jours.', 'notes' => 'Contrôle <script> et accents : électricité.'];
    $calculation = $request('POST', '/api/admin/estimates/calculate', $data);
    $totals = $calculation['totals'];
    $assert($totals['totalHt'] === 31000 && $totals['totalVat'] === 1975 && $totals['totalTtc'] === 32975, 'Total ou TVA multiple incorrect.');
    $assert($totals['deposit'] === 9893 && $totals['balance'] === 23082, 'Acompte ou arrondi incorrect.');
    $request('POST', '/api/admin/estimates/calculate', $data, 419, false);
    $request('POST', '/api/admin/estimates', ['data' => $data, 'requestId' => $quoteId], 419, false);
    $estimate = $request('POST', '/api/admin/estimates', ['data' => $data, 'requestId' => $quoteId], 201);
    $estimateIds[] = $estimate['id'];
    $id = $estimate['id'];
    $assert($estimate['totals'] === $totals && $estimate['missing'] === [], 'Calculs enregistrés différents.');
    $assert($estimate['requestId'] === $quoteId && preg_match('/^DEV-\d{4}-\d{6,}$/', $estimate['number']) === 1, 'Numéro ou lien demande invalide.');
    $request('PUT', "/api/admin/estimates/$id", ['data' => $data, 'requestId' => null], 422);
    $request('POST', '/api/admin/estimates', ['data' => $data, 'requestId' => 99999999], 422);
    foreach ([
        ['date' => '2026-02-30'], ['validUntil' => '2000-01-01'], ['depositPercent' => '101'],
        ['depositPercent' => '-1'], ['title' => null], ['extra' => 'invalid'],
        ['client' => [...$contact, 'email' => 'not-email']], ['lines' => array_fill(0, 101, $data['lines'][0])],
    ] as $invalid) $request('PUT', "/api/admin/estimates/$id", ['data' => [...$data, ...$invalid], 'requestId' => $quoteId], 422);
    foreach ([
        ['quantity' => '0'], ['quantity' => '-1'], ['quantity' => '1.0001'], ['quantity' => '1001'],
        ['unitPrice' => '1.001'], ['unitPrice' => '-1'], ['unitPrice' => '1e3'], ['vatRate' => '7'],
        ['unitPrice' => []], ['unitPrice' => null], ['description' => str_repeat('x', 2001)],
    ] as $invalid) $request('POST', '/api/admin/estimates/calculate', [...$data, 'lines' => [[...$data['lines'][0], ...$invalid]]], 422);
    $request('POST', '/api/admin/estimates/calculate', [...$data, 'lines' => array_fill(0, 100, [...$data['lines'][0], 'quantity' => '1000', 'unitPrice' => '1000000'])], 422);
    $rounded = $request('POST', '/api/admin/estimates/calculate', [...$data, 'lines' => [
        [...$data['lines'][0], 'quantity' => '1.125', 'unitPrice' => '19.99', 'vatRate' => '5.5'],
    ]]);
    $assert($rounded['totals']['lines'][0] === ['ht' => 2249, 'vat' => 124, 'ttc' => 2373], 'Arrondi décimal de ligne incorrect.');
    $pdf = $request('GET', "/api/admin/estimates/$id/pdf", null, 200, true, true);
    $assert(str_starts_with($pdf['body'], '%PDF-') && strlen($pdf['body']) > 5000 && $pdf['headers']['content-type'][0] === 'application/pdf', 'PDF manquant ou invalide.');
    $assert(str_contains($pdf['headers']['content-disposition'][0], $estimate['number'].'.pdf'), 'Nom de téléchargement PDF incorrect.');
    $request('PUT', '/api/admin/estimates/profiles/'.$profileIds[1], ['kind' => 'client', 'data' => [...$contact, 'name' => 'Nouveau nom']]);
    $stored = array_values(array_filter($request('GET', '/api/admin/estimates'), static fn ($item) => $item['id'] === $id))[0];
    $assert($stored['data']['client']['name'] === $contact['name'], 'Modifier une fiche altère un ancien devis.');
    $request('DELETE', '/api/admin/estimates/profiles/'.$profileIds[1], null, 419, false);
    $request('DELETE', '/api/admin/estimates/profiles/'.$profileIds[1]);
    $assert($request('GET', "/api/admin/estimates/$id/pdf", null, 200, true, true)['headers']['content-type'][0] === 'application/pdf', 'Supprimer une fiche altère un ancien devis.');
    $partial = $request('POST', '/api/admin/estimates', ['data' => [...$seed, 'company' => ['name' => '', 'address' => ''], 'lines' => []], 'requestId' => null], 201);
    $estimateIds[] = $partial['id'];
    $assert(count($partial['missing']) > 0 && $partial['totals']['totalTtc'] === 0, 'Brouillon incomplet non conservé.');
    $request('GET', '/api/admin/estimates/'.$partial['id'].'/pdf', null, 422);
    $long = [...$data, 'lines' => array_fill(0, 70, [...$data['lines'][0], 'description' => 'Ligne longue avec accents : peinture, rénovation et préparation des murs.'])];
    $longSaved = $request('PUT', "/api/admin/estimates/$id", ['data' => $long, 'requestId' => $quoteId]);
    $longPdf = $request('GET', "/api/admin/estimates/$id/pdf", null, 200, true, true);
    $assert(preg_match_all('/\/Type\s*\/Page\b/', $longPdf['body']) > 1, 'PDF multiligne non paginé.');
    $request('PUT', "/api/admin/estimates/$id", ['data' => $data, 'requestId' => $quoteId]);
    $em->clear();
    $original = $em->find(QuoteRequest::class, $quoteId);
    $assert($original->getFirstName() === 'Alice' && $original->getStatus() === 'new', 'La demande a été modifiée par le devis ou le PDF.');
    $request('DELETE', "/api/admin/estimates/$id", null, 419, false);
    $request('DELETE', "/api/admin/estimates/$id");
    $request('GET', "/api/admin/estimates/$id/pdf", null, 404);
    $request('POST', '/api/admin/logout');
    $request('GET', '/api/admin/estimates/profiles', null, 401);
    echo "OK : $checks contrôles devis, fiches et PDF réussis.\n";
} finally {
    $em->clear();
    foreach ($estimateIds as $id) { if ($item = $em->find(Estimate::class, $id)) $em->remove($item); }
    foreach ($profileIds as $id) { if ($item = $em->find(EstimateProfile::class, $id)) $em->remove($item); }
    if ($item = $em->find(QuoteRequest::class, $quoteId)) $em->remove($item);
    if ($item = $em->find(User::class, $userId)) $em->remove($item);
    $em->flush();
    $kernel->shutdown();
}
