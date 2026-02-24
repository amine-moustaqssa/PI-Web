<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class HolidayApiService
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Vérifie si une date précise est un jour férié en Tunisie.
     */
    public function isHoliday(\DateTimeInterface $date): bool
    {
        $year = $date->format('Y');
        $formattedDate = $date->format('Y-m-d');

        try {
            // Appel à l'API pour récupérer les jours fériés de l'année en Tunisie (TN)
            $response = $this->client->request(
                'GET',
                "https://date.nager.at/api/v3/PublicHolidays/{$year}/TN"
            );

            // Si l'API ne répond pas correctement, on suppose que ce n'est pas férié pour ne pas bloquer le système
            if ($response->getStatusCode() !== 200) {
                return false;
            }

            $holidays = $response->toArray();

            // On parcourt la liste pour voir si notre date correspond à un jour férié
            foreach ($holidays as $holiday) {
                if ($holiday['date'] === $formattedDate) {
                    return true; // C'est un jour férié !
                }
            }
        } catch (\Exception $e) {
            // Log erreur ou ignorer et considérer non-férié pour ne pas casser le workflow
            return false;
        }

        return false;
    }

    /**
     * Récupère tous les jours fériés pour une année donnée en Tunisie.
     */
    public function getHolidaysForYear(int $year): array
    {
        try {
            $response = $this->client->request(
                'GET',
                "https://date.nager.at/api/v3/PublicHolidays/{$year}/TN"
            );

            if ($response->getStatusCode() !== 200) {
                return [];
            }

            return $response->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }
}
