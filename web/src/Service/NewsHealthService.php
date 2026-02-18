<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class NewsHealthService
{
    private $httpClient;
    private $apiKey;

    public function __construct(HttpClientInterface $httpClient, string $apiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
    }

    public function getHealthNews(string $query = 'Santé'): array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://newsapi.org/v2/everything', [
                'query' => [
                    'q' => $query . ' AND (santé OR médical)',
                    'language' => 'fr',
                    'sortBy' => 'publishedAt',
                    'pageSize' => 3, // On en prend 3 pour ne pas surcharger la page
                    'apiKey' => $this->apiKey,
                ],
            ]);

            $data = $response->toArray();
            return $data['articles'] ?? [];
        } catch (\Exception $e) {
            return []; // En cas d'erreur API, on retourne une liste vide
        }
    }
}