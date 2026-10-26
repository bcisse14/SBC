<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\{Estimate, EstimateProfile, QuoteRequest};
use Doctrine\ORM\EntityManagerInterface;

class EstimateEditor
{
    private const COMPANY = ['name' => 180, 'address' => 500, 'email' => 180, 'phone' => 40, 'registration' => 180, 'vatNumber' => 80, 'insurance' => 1000];
    private const CLIENT = ['name' => 180, 'address' => 500, 'email' => 180, 'phone' => 40];

    public function __construct(private readonly EntityManagerInterface $em, private readonly SiteEditor $siteEditor) {}

    public function profile(array $input, string $kind): array
    {
        $fields = $kind === 'company' ? self::COMPANY : self::CLIENT;
        $this->keys($input, array_keys($fields));
        $data = [];
        foreach ($fields as $key => $limit) $data[$key] = $this->text(array_key_exists($key, $input) ? $input[$key] : '', $limit, $key);
        if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) throw new \InvalidArgumentException('Adresse e-mail invalide.');
        return $data;
    }

    public function initial(?QuoteRequest $request): array
    {
        $company = $this->em->getRepository(EstimateProfile::class)->findOneBy(['kind' => 'company'], ['updatedAt' => 'DESC', 'id' => 'DESC']);
        $settings = $this->siteEditor->values();
        $defaults = [];
        foreach (['name' => 'company_name', 'address' => 'company_address', 'email' => 'company_email', 'phone' => 'company_phone'] as $key => $setting) {
            $value = $settings[$setting] ?? '';
            $defaults[$key] = $value === 'À COMPLÉTER' ? '' : $value;
        }
        return [
            'title' => $request ? 'Travaux à '.$request->getCity() : 'Devis de travaux',
            'date' => date('Y-m-d'), 'validUntil' => date('Y-m-d', strtotime('+30 days')),
            'company' => $company?->getData() ?? $this->profile($defaults, 'company'),
            'client' => $this->profile($request ? [
                'name' => trim($request->getFirstName().' '.$request->getLastName()),
                'email' => $request->getEmail(), 'phone' => $request->getPhone(),
                'address' => trim($request->getPostalCode().' '.$request->getCity()),
            ] : [], 'client'),
            'siteAddress' => $request ? trim($request->getPostalCode().' '.$request->getCity()) : '',
            'introduction' => $request?->getDescription() ?? '',
            'lines' => [['description' => '', 'quantity' => '1', 'unit' => 'forfait', 'unitPrice' => '0', 'vatRate' => '20']],
            'depositPercent' => '30', 'conditions' => '', 'notes' => '',
        ];
    }

    public function validate(array $input): array
    {
        $this->keys($input, ['title', 'date', 'validUntil', 'company', 'client', 'siteAddress', 'introduction', 'lines', 'depositPercent', 'conditions', 'notes']);
        $data = [];
        foreach (['title' => 180, 'siteAddress' => 500, 'introduction' => 5000, 'conditions' => 5000, 'notes' => 3000] as $key => $limit) {
            $data[$key] = $this->text(array_key_exists($key, $input) ? $input[$key] : '', $limit, $key);
        }
        foreach (['date', 'validUntil'] as $key) {
            $date = $input[$key] ?? '';
            if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date) || !($parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date)) || $parsed->format('Y-m-d') !== $date) {
                throw new \InvalidArgumentException('Date du devis ou de validité invalide.');
            }
            $data[$key] = $date;
        }
        if ($data['validUntil'] < $data['date']) throw new \InvalidArgumentException('La validité doit être postérieure ou égale à la date du devis.');
        foreach (['company', 'client'] as $kind) {
            if (!isset($input[$kind]) || !is_array($input[$kind])) throw new \InvalidArgumentException('Coordonnées manquantes.');
            $data[$kind] = $this->profile($input[$kind], $kind);
        }
        $lines = $input['lines'] ?? null;
        if (!is_array($lines) || !array_is_list($lines) || count($lines) > 100) throw new \InvalidArgumentException('Un devis accepte au maximum 100 lignes de prestations.');
        $data['lines'] = [];
        foreach ($lines as $line) {
            if (!is_array($line)) throw new \InvalidArgumentException('Ligne de prestation invalide.');
            $this->keys($line, ['description', 'quantity', 'unit', 'unitPrice', 'vatRate']);
            $description = $this->text(array_key_exists('description', $line) ? $line['description'] : '', 2000, 'description de prestation');
            $unit = $this->text(array_key_exists('unit', $line) ? $line['unit'] : '', 30, 'unité');
            $quantity = $this->decimal($line['quantity'] ?? null, 3, 1000000, 'quantité', 1);
            $price = $this->decimal($line['unitPrice'] ?? null, 2, 100000000, 'prix unitaire');
            $vat = $this->decimal($line['vatRate'] ?? null, 2, 10000, 'TVA');
            if (!in_array($vat, [0, 550, 1000, 2000], true)) throw new \InvalidArgumentException('TVA autorisée : 0, 5,5, 10 ou 20 %.');
            $data['lines'][] = ['description' => $description, 'unit' => $unit, 'quantity' => $this->format($quantity, 3), 'unitPrice' => $this->format($price, 2), 'vatRate' => $this->format($vat, 2)];
        }
        $deposit = $this->decimal($input['depositPercent'] ?? null, 2, 10000, 'acompte');
        $data['depositPercent'] = $this->format($deposit, 2);
        $totals = $this->totals($data);
        if ($totals['totalHt'] > 1000000000) throw new \InvalidArgumentException('Le total HT ne doit pas dépasser 10 000 000 €.');
        return $data;
    }

    public function totals(array $data): array
    {
        $lines = [];
        $vatGroups = [];
        $ht = $tax = 0;
        foreach ($data['lines'] as $line) {
            $quantity = $this->decimal($line['quantity'], 3, 1000000, 'quantité', 1);
            $price = $this->decimal($line['unitPrice'], 2, 100000000, 'prix unitaire');
            $rate = $this->decimal($line['vatRate'], 2, 10000, 'TVA');
            $amount = intdiv($quantity * $price + 500, 1000);
            $vat = intdiv($amount * $rate + 5000, 10000);
            $lines[] = ['ht' => $amount, 'vat' => $vat, 'ttc' => $amount + $vat];
            $key = $this->format($rate, 2);
            $vatGroups[$key] = ($vatGroups[$key] ?? 0) + $vat;
            $ht += $amount;
            $tax += $vat;
        }
        $deposit = $this->decimal($data['depositPercent'], 2, 10000, 'acompte');
        $ttc = $ht + $tax;
        $advance = intdiv($ttc * $deposit + 5000, 10000);
        return ['lines' => $lines, 'totalHt' => $ht, 'totalVat' => $tax, 'totalTtc' => $ttc, 'vatGroups' => $vatGroups, 'deposit' => $advance, 'balance' => $ttc - $advance];
    }

    public function missing(array $data): array
    {
        $missing = [];
        foreach (['company' => 'entreprise', 'client' => 'client'] as $kind => $label) {
            foreach (['name' => 'nom', 'address' => 'adresse'] as $key => $field) {
                if ($data[$kind][$key] === '') $missing[] = "$field ($label)";
            }
        }
        if ($data['company']['registration'] === '') $missing[] = 'immatriculation de l’entreprise';
        if ($data['siteAddress'] === '') $missing[] = 'adresse du chantier';
        if ($data['title'] === '') $missing[] = 'titre';
        if (!$data['lines']) $missing[] = 'au moins une prestation';
        foreach ($data['lines'] as $index => $line) {
            if ($line['description'] === '') $missing[] = 'description de la prestation '.($index + 1);
            if ($line['unit'] === '') $missing[] = 'unité de la prestation '.($index + 1);
        }
        return $missing;
    }

    public function serialize(Estimate $estimate): array
    {
        $data = $estimate->getData();
        return ['id' => $estimate->getId(), 'number' => $estimate->getNumber(), 'requestId' => $estimate->getRequest()?->getId(), 'data' => $data,
            'totals' => $this->totals($data), 'missing' => $this->missing($data), 'updatedAt' => $estimate->getUpdatedAt()?->format(DATE_ATOM)];
    }

    private function keys(array $data, array $allowed): void
    {
        if (array_diff(array_keys($data), $allowed)) throw new \InvalidArgumentException('Champ non reconnu dans le devis ou la fiche.');
    }

    private function text(mixed $value, int $max, string $label): string
    {
        if (!is_string($value) || mb_strlen($value) > $max) throw new \InvalidArgumentException("Champ $label invalide (maximum $max caractères).");
        return trim(str_replace("\u{2014}", '-', $value));
    }

    private function decimal(mixed $value, int $scale, int $max, string $label, int $min = 0): int
    {
        if (!is_string($value) || !preg_match('/^\d{1,9}(?:[.,]\d{1,'.$scale.'})?$/D', $value)) throw new \InvalidArgumentException("Valeur invalide pour $label.");
        $parts = explode('.', str_replace(',', '.', $value));
        $number = (int) $parts[0] * (10 ** $scale) + (int) str_pad($parts[1] ?? '', $scale, '0');
        if ($number < $min || $number > $max) throw new \InvalidArgumentException("Valeur hors limites pour $label.");
        return $number;
    }

    private function format(int $value, int $scale): string
    {
        $factor = 10 ** $scale;
        return intdiv($value, $factor).'.'.str_pad((string) ($value % $factor), $scale, '0', STR_PAD_LEFT);
    }
}
