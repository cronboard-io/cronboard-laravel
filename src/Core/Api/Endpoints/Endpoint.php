<?php

namespace Cronboard\Core\Api\Endpoints;

use Cronboard\Core\Api\Client;
use Cronboard\Core\Api\Exception;
use Cronboard\Support\Signing\Signer;
use Cronboard\Support\Signing\Verifier;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;

class Endpoint
{
    protected $client;
    protected $requestVerifier;

    public function __construct(Client $client, Verifier $requestVerifier)
    {
        $this->client = $client;
        $this->requestVerifier = $requestVerifier;
    }

    public function __call($method, $args)
    {
        return call_user_func_array([$this->client, $method], $args);
    }

    public function get(string $endpoint)
    {
        return $this->respond(
            $this->request('GET', $endpoint)
        );
    }

    /**
     * @param array $data
     */
    public function postWithoutVerification(string $endpoint, array $data)
    {
        return $this->post($endpoint, $data, true);
    }

    public function post(string $endpoint, array $data, bool $disableVerify = false)
    {
        return $this->respond(
            $this->request('POST', $endpoint, [
                'json' => $data,
            ]),
            $disableVerify
        );
    }

    public function request(string $method, string $uri = '', array $options = [])
    {
        try {
            $response = $this->client->request($method, 'api/' . $uri, array_merge($options, [
                'headers' => array_merge([
                    'Accept' => 'application/json'
                ], $this->client->getAuthHeaders())
            ]));
            return $response;
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();

            $payload = json_decode($response->getBody() . '', true);
            $errorMessage = $payload['message'] ?? '';

            if ($statusCode === 402) {
                throw Exception::paymentRequired($e, $errorMessage);
            }

            throw new Exception($statusCode, $errorMessage, $e);
        } catch (ConnectException $e) {
            throw Exception::offline($e);
        }
    }

    protected function respond($response, $disableVerify = false)
    {
        $responseContent = $response->getBody()->getContents();

        if (!$disableVerify) {
            $signatureHeader = $response->getHeader(Signer::SIGNATURE_HEADER);
            $signature = $signatureHeader[0] ?? null;

            if (empty($signature) || !$this->requestVerifier->verify($responseContent, $signature)) {
                throw new Exception(400, 'Invalid response signature');
            }
        }

        return json_decode($responseContent, true);
    }
}
