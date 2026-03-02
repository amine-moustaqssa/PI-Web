<?php

namespace App\Tests\Service;

use App\Service\HolidayApiService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Tests unitaires pour le service de jours fériés (API Nager.Date).
 * Fonctionnalité 5 : Filtrage de disponibilité médecin — détection jours fériés.
 */
class HolidayApiServiceTest extends TestCase
{
    // ──────────────────────────────────────────────────────────────
    //  isHoliday() — jour férié détecté
    // ──────────────────────────────────────────────────────────────

    public function testIsHolidayRetourneTruePourJourFerie(): void
    {
        $holidays = [
            ['date' => '2025-01-01', 'localName' => 'Jour de l\'An', 'name' => 'New Year'],
            ['date' => '2025-03-20', 'localName' => 'Fête de l\'Indépendance', 'name' => 'Independence Day'],
        ];

        $service = $this->createServiceWithResponse(200, $holidays);
        $date = new \DateTime('2025-03-20');

        $this->assertTrue($service->isHoliday($date));
    }

    public function testIsHolidayRetourneFalsePourJourNormal(): void
    {
        $holidays = [
            ['date' => '2025-01-01', 'localName' => 'Jour de l\'An', 'name' => 'New Year'],
        ];

        $service = $this->createServiceWithResponse(200, $holidays);
        $date = new \DateTime('2025-06-15');

        $this->assertFalse($service->isHoliday($date));
    }

    // ──────────────────────────────────────────────────────────────
    //  isHoliday() — gestion des erreurs API
    // ──────────────────────────────────────────────────────────────

    public function testIsHolidayRetourneFalseSiApiErreur500(): void
    {
        $service = $this->createServiceWithResponse(500, []);
        $date = new \DateTime('2025-03-20');

        $this->assertFalse($service->isHoliday($date));
    }

    public function testIsHolidayRetourneFalseSiException(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willThrowException(new \RuntimeException('Network error'));

        $service = new HolidayApiService($client);
        $date = new \DateTime('2025-03-20');

        $this->assertFalse($service->isHoliday($date));
    }

    // ──────────────────────────────────────────────────────────────
    //  getHolidaysForYear() — liste complète
    // ──────────────────────────────────────────────────────────────

    public function testGetHolidaysForYearRetourneTableau(): void
    {
        $holidays = [
            ['date' => '2025-01-01', 'localName' => 'Jour de l\'An'],
            ['date' => '2025-03-20', 'localName' => 'Fête de l\'Indépendance'],
            ['date' => '2025-07-25', 'localName' => 'Fête de la République'],
        ];

        $service = $this->createServiceWithResponse(200, $holidays);

        $result = $service->getHolidaysForYear(2025);

        $this->assertCount(3, $result);
        $this->assertSame('2025-01-01', $result[0]['date']);
    }

    public function testGetHolidaysForYearRetourneTableauVideSiErreur(): void
    {
        $service = $this->createServiceWithResponse(500, []);

        $result = $service->getHolidaysForYear(2025);

        $this->assertEmpty($result);
    }

    public function testGetHolidaysForYearRetourneTableauVideSiException(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willThrowException(new \RuntimeException('Timeout'));

        $service = new HolidayApiService($client);

        $result = $service->getHolidaysForYear(2025);

        $this->assertEmpty($result);
    }

    // ──────────────────────────────────────────────────────────────
    //  isHoliday() — vérification de l'URL appelée
    // ──────────────────────────────────────────────────────────────

    public function testIsHolidayAppelleBonneUrl(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->willReturn([]);

        $client = $this->createMock(HttpClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with('GET', 'https://date.nager.at/api/v3/PublicHolidays/2025/TN')
            ->willReturn($response);

        $service = new HolidayApiService($client);
        $service->isHoliday(new \DateTime('2025-06-15'));
    }

    // ──────────────────────────────────────────────────────────────
    //  Helper
    // ──────────────────────────────────────────────────────────────

    private function createServiceWithResponse(int $statusCode, array $data): HolidayApiService
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('toArray')->willReturn($data);

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        return new HolidayApiService($client);
    }
}
