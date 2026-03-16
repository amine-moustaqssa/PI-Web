<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class PharmacieApiService
{
    private HttpClientInterface $httpClient;

    /** @var array<int, array{name: string, url: string, key: string, table: string}> */
    private array $pharmacies;

    public function __construct(
        HttpClientInterface $httpClient,
        array $pharmacies
    ) {
        $this->httpClient = $httpClient;
        $this->pharmacies = $pharmacies;
    }

    /**
     * Fetch medications from ALL configured pharmacies.
     * Each returned record is enriched with 'pharmacie' (name).
     *
     * @param string|null $search Search term to filter by nom_medicament
     * @return array              Flat array of medication records with pharmacy info
     */
    public function getAllMedicaments(?string $search = null): array
    {
        $results = [];

        foreach ($this->pharmacies as $pharmacy) {
            try {
                $items = $this->fetchFromPharmacy($pharmacy, $search);
                foreach ($items as &$item) {
                    $item['pharmacie'] = $pharmacy['name'];
                }
                unset($item);
                $results = array_merge($results, $items);
            } catch (\Throwable $e) {
                // Skip unavailable pharmacies silently; controller can log if needed
                continue;
            }
        }

        // Sort alphabetically by medication name
        usort($results, fn($a, $b) => strcasecmp($a['nom_medicament'], $b['nom_medicament']));

        return $results;
    }

    /**
     * @return array Raw records from a single pharmacy's Supabase table
     */
    private function fetchFromPharmacy(array $pharmacy, ?string $search): array
    {
        $url = rtrim($pharmacy['url'], '/') . '/rest/v1/' . $pharmacy['table'];
        $query = ['select' => '*', 'order' => 'nom_medicament.asc'];

        if ($search) {
            $query['nom_medicament'] = 'ilike.*' . $search . '*';
        }

        $response = $this->httpClient->request('GET', $url, [
            'headers' => [
                'apikey' => $pharmacy['key'],
                'Authorization' => 'Bearer ' . $pharmacy['key'],
                'Accept' => 'application/json',
            ],
            'query' => $query,
        ]);

        return $response->toArray();
    }
}
