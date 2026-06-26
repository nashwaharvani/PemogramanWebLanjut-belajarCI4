<?php

namespace App\Services;

class RajaOngkirService
{
    protected $baseUrl;
    protected $apiKey;
    protected $client;

    public function __construct()
    {
        $this->baseUrl = getenv('RAJAONGKIR_BASE_URL') ?: 'https://rajaongkir.komerce.id/api/v1';
        $this->apiKey = getenv('RAJAONGKIR_API_KEY') ?: 'AM3JN8gP93b8149963ef2d53hqufbCux';
        $this->client = \Config\Services::curlrequest();
    }

    /**
     * Get destinations/locations based on search query
     * 
     * @param string $search
     * @return array
     */
    public function getDestination(string $search): array
    {
        try {
            $response = $this->client->request('GET', $this->baseUrl . '/destination/domestic-destination', [
                'headers' => [
                    'Accept' => 'application/json',
                    'key'    => $this->apiKey
                ],
                'query' => [
                    'search' => $search,
                    'limit'  => 50
                ],
                'http_errors' => false
            ]);

            $body = $response->getBody();
            return json_decode($body, true) ?? [];
        } catch (\Exception $e) {
            return [
                'meta' => [
                    'message' => $e->getMessage(),
                    'code'    => 500,
                    'status'  => 'error'
                ],
                'data' => []
            ];
        }
    }

    /**
     * Get shipping costs
     * 
     * @param string $origin
     * @param string $destination
     * @param string $weight
     * @param string $courier
     * @return array
     */
    public function getCost(string $origin, string $destination, string $weight, string $courier): array
    {
        try {
            $response = $this->client->request('POST', $this->baseUrl . '/calculate/domestic-cost', [
                'headers' => [
                    'Accept' => 'application/json',
                    'key'    => $this->apiKey,
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'form_params' => [
                    'origin'      => $origin,
                    'destination' => $destination,
                    'weight'      => $weight,
                    'courier'     => $courier
                ],
                'http_errors' => false
            ]);

            $body = $response->getBody();
            return json_decode($body, true) ?? [];
        } catch (\Exception $e) {
            return [
                'meta' => [
                    'message' => $e->getMessage(),
                    'code'    => 500,
                    'status'  => 'error'
                ],
                'data' => []
            ];
        }
    }
}
