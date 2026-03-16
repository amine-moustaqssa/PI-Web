<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class PdfGeneratorService
{
    private $httpClient;
    private $logger;
    private $apiKey;
    private const API_BASE_URL = 'https://api.pdf.co/v1';

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger, string $apiKey)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiKey = $apiKey;
    }

    /**
     * Génère un PDF à partir d'un HTML en utilisant PDF.co
     */
    public function generatePdfFromHtml(string $html, string $fileName = 'document.pdf'): ?string
    {
        try {
            $this->logger->info('Tentative de génération PDF via PDF.co', [
                'fileName' => $fileName,
                'htmlLength' => strlen($html)
            ]);

            // Étape 1: Convertir HTML en PDF via PDF.co
            $response = $this->httpClient->request('POST', self::API_BASE_URL . '/pdf/convert/from/html', [
                'headers' => [
                    'x-api-key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'html' => $html,
                    'name' => $fileName,
                    'paperSize' => 'A4',
                    'orientation' => 'Portrait',
                    'printBackground' => true,
                    'margins' => '20px 20px 20px 20px',
                    'async' => false,
                    'encrypt' => false,
                    'compression' => true
                ],
                'timeout' => 30 // Timeout de 30 secondes
            ]);

            $statusCode = $response->getStatusCode();
            $result = $response->toArray();

            $this->logger->info('Réponse PDF.co reçue', [
                'statusCode' => $statusCode,
                'result' => $result
            ]);

            if (isset($result['error']) && $result['error'] === false && isset($result['url'])) {
                // Télécharger le PDF depuis l'URL fournie
                $pdfContent = $this->downloadPdf($result['url']);
                
                if ($pdfContent) {
                    $this->logger->info('PDF généré avec succès', [
                        'size' => strlen($pdfContent)
                    ]);
                    return $pdfContent;
                }
            }

            $this->logger->error('Erreur PDF.co', [
                'message' => $result['message'] ?? 'Erreur inconnue'
            ]);
            return null;

        } catch (\Exception $e) {
            $this->logger->error('Exception lors de la génération PDF', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Version asynchrone pour les gros documents
     */
    public function generatePdfAsync(string $html, string $fileName = 'document.pdf'): ?array
    {
        try {
            $response = $this->httpClient->request('POST', self::API_BASE_URL . '/pdf/convert/from/html', [
                'headers' => [
                    'x-api-key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'html' => $html,
                    'name' => $fileName,
                    'paperSize' => 'A4',
                    'orientation' => 'Portrait',
                    'async' => true
                ]
            ]);

            $result = $response->toArray();

            if (isset($result['error']) && $result['error'] === false) {
                return [
                    'jobId' => $result['jobId'] ?? null,
                    'url' => $result['url'] ?? null
                ];
            }

            return null;

        } catch (\Exception $e) {
            $this->logger->error('Exception lors de la génération PDF asynchrone', [
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Vérifie le statut d'un job asynchrone
     */
    public function checkJobStatus(string $jobId): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::API_BASE_URL . '/job/check', [
                'headers' => [
                    'x-api-key' => $this->apiKey,
                ],
                'query' => [
                    'jobid' => $jobId
                ]
            ]);

            return $response->toArray();

        } catch (\Exception $e) {
            $this->logger->error('Exception lors de la vérification du job', [
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Télécharge un PDF depuis une URL
     */
    private function downloadPdf(string $url): ?string
    {
        try {
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 30
            ]);
            
            $content = $response->getContent();
            
            // Vérifier que c'est bien un PDF
            if (strpos($content, '%PDF') !== 0) {
                $this->logger->error('Le contenu téléchargé n\'est pas un PDF valide');
                return null;
            }
            
            return $content;
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du téléchargement du PDF', [
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Teste la connexion à l'API PDF.co
     */
    public function testConnection(): bool
    {
        try {
            $response = $this->httpClient->request('GET', self::API_BASE_URL . '/profile', [
                'headers' => [
                    'x-api-key' => $this->apiKey,
                ]
            ]);

            $result = $response->toArray();
            return isset($result['error']) && $result['error'] === false;

        } catch (\Exception $e) {
            $this->logger->error('Erreur de connexion à PDF.co', [
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }
}