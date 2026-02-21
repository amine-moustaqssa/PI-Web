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

    public function getHealthNews(string $query): array
{
    // On force l'ajout de mots-clés médicaux pour filtrer les résultats
    $refinedQuery = $query . ' AND (santé OR médical OR hôpital OR recherche)';

    $response = $this->httpClient->request('GET', 'https://newsapi.org/v2/everything', [
        'query' => [
            'q' => $refinedQuery,
            'apiKey' => $this->apiKey,
            'language' => 'fr',
            'sortBy' => 'relevance', // Priorité à la pertinence
            'pageSize' => 3
        ]
    ]);

    return $response->toArray()['articles'] ?? [];
}
}