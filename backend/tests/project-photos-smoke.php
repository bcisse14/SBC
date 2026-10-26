<?php

declare(strict_types=1);

use App\Entity\MediaAsset;
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpClient\HttpClient;

require dirname(__DIR__) . '/vendor/autoload.php';
(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
$kernel = new Kernel('dev', true);
$kernel->boot();
$em = $kernel->getContainer()->get('doctrine')->getManager();
$client = HttpClient::create(['base_uri' => getenv('CMS_TEST_BASE_URL') ?: 'http://nginx']);
$data = $client->request('GET', '/api/site/bootstrap')->toArray();
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) throw new RuntimeException($message);
    ++$checks;
};
$assignments = [
    'renovation-salle-de-bains' => ['sdb-01.jpeg', 'sdb-02.jpeg', 'sdb-03.jpeg', 'sdb-04.jpeg'],
    'renovation-chambre' => ['chambre-01.jpeg'],
    'amenagement-sur-mesure' => ['amenagement-01.jpg', 'amenagement-02.jpg'],
];
$seen = [];
foreach ($assignments as $slug => $names) {
    $category = array_values(array_filter($data['realisationCategories'], static fn (array $row) => $row['slug'] === $slug))[0] ?? null;
    $assert($category !== null, 'Catégorie absente : ' . $slug);
    $expectedIds = [];
    foreach ($names as $name) {
        $asset = $em->getRepository(MediaAsset::class)->findOneBy(['originalName' => $name]);
        $assert($asset !== null, 'Photo absente : ' . $name);
        $expectedIds[] = $asset->getId();
        $assert(!isset($seen[$asset->getId()]), 'Photo classée dans deux catégories : ' . $name);
        $seen[$asset->getId()] = true;
        $media = $data['media'][$asset->getId()];
        $assert(!empty($media['version']), 'Version de cache absente : ' . $name);
        $response = $client->request('GET', '/api/media/' . $asset->getId() . '?v=' . $media['version']);
        $image = new Imagick();
        $image->readImageBlob($response->getContent());
        $assert($image->getImageWidth() === $media['width'] && $image->getImageHeight() === $media['height'], 'Dimensions originales incohérentes : ' . $name);
        if (str_starts_with($name, 'sdb-')) {
            $assert($image->getImageHeight() > $image->getImageWidth(), 'Salle de bains non verticale : ' . $name);
        }
        $image->clear();
        foreach ($media['variants'] as $variant) {
            $url = sprintf('/api/media/%d?w=%d&f=%s&v=%s', $asset->getId(), $variant['w'], $variant['format'], $media['version']);
            $image->readImageBlob($client->request('GET', $url)->getContent());
            $assert($image->getImageWidth() === $variant['w'], 'Largeur de variante incorrecte : ' . $name);
            $assert(abs($image->getImageWidth() / $image->getImageHeight() - $media['width'] / $media['height']) < 0.01, 'Orientation de variante incorrecte : ' . $name);
            $image->clear();
        }
    }
    $assert(array_slice($category['gallery'], 0, count($expectedIds)) === $expectedIds, 'Mauvais classement : ' . $slug);
}
$heroAsset = $em->getRepository(MediaAsset::class)->findOneBy(['originalName' => 'amenagement-01.jpg']);
$home = array_values(array_filter($data['pages'], static fn (array $page) => $page['slug'] === ''))[0];
$hero = array_values(array_filter($home['sections'], static fn (array $section) => $section['type'] === 'hero'))[0];
$assert($hero['mediaAssetId'] === $heroAsset->getId(), 'La photo principale doit être amenagement-01.jpg.');
$assert(count($seen) === 7, 'Le jeu de photos doit contenir exactement sept photos connues.');
echo "OK : $checks contrôles, classement, originaux et variantes, orientation portrait et photo principale.\n";
