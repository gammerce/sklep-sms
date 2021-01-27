<?php
namespace App\License;

use App\Requesting\Requester;
use App\Requesting\Response;
use Exception;

class LicenseServerService
{
    /** @var Requester */
    private $requester;

    /** @var string */
    private $url;

    /** @var string */
    private $licenseSecret;

    public function __construct($url, $licenseSecret, Requester $requester)
    {
        $this->url = $url;
        $this->licenseSecret = $licenseSecret;
        $this->requester = $requester;
    }

    /**
     * @param int $lifetime
     * @param bool $hasAmxModX
     * @param bool $hasSourceMod
     * @return array ['id', 'token', 'expires_at']
     * @throws Exception
     */
    public function create($lifetime, $hasAmxModX, $hasSourceMod)
    {
        $response = $this->requester->post(
            $this->buildUrl("/v1/licenses"),
            json_encode([
                "lifetime" => $lifetime,
                "platform_amxmodx" => $hasAmxModX,
                "platform_sourcemod" => $hasSourceMod,
            ]),
            [
                "Authorization" => $this->licenseSecret,
                "Content-Type" => "application/json",
            ]
        );

        $this->guardAgainstInvalidResponse($response);

        return $response->json();
    }

    /**
     * @param int $licenseId
     * @param bool $hasAmxModX
     * @param bool $hasSourceMod
     * @return array ['expires_at']
     * @throws Exception
     */
    public function updatePlatforms($licenseId, $hasAmxModX, $hasSourceMod)
    {
        $response = $this->requester->patch(
            $this->buildUrl("/v1/licenses/${licenseId}"),
            json_encode([
                "platform_amxmodx" => $hasAmxModX,
                "platform_sourcemod" => $hasSourceMod,
            ]),
            [
                "Authorization" => $this->licenseSecret,
                "Content-Type" => "application/json",
            ]
        );

        $this->guardAgainstInvalidResponse($response);

        return $response->json();
    }

    /**
     * @param int $licenseId
     * @param int $lifetime
     * @return array ['expires_at']
     * @throws Exception
     */
    public function prolong($licenseId, $lifetime)
    {
        $response = $this->requester->patch(
            $this->buildUrl("/v1/licenses/${licenseId}"),
            json_encode([
                "lifetime" => $lifetime,
            ]),
            [
                "Authorization" => $this->licenseSecret,
                "Content-Type" => "application/json",
            ]
        );

        $this->guardAgainstInvalidResponse($response);

        return $response->json();
    }

    /**
     * @param int $licenseId
     * @return array ['token']
     * @throws Exception
     */
    public function regenerateToken($licenseId)
    {
        $response = $this->requester->post(
            $this->buildUrl("/v1/licenses/${licenseId}/token"),
            json_encode([]),
            [
                "Authorization" => $this->licenseSecret,
                "Content-Type" => "application/json",
            ]
        );

        $this->guardAgainstInvalidResponse($response);

        return $response->json();
    }

    /**
     * @param int $licenseId
     * @throws Exception
     */
    public function delete($licenseId)
    {
        $response = $this->requester->delete(
            $this->buildUrl("/v1/licenses/${licenseId}"),
            [],
            [
                "Authorization" => $this->licenseSecret,
            ]
        );

        $this->guardAgainstInvalidResponse($response);
    }

    /**
     * @param string $token
     * @return array ['id', 'expires_at']
     * @throws Exception
     */
    public function getByToken($token)
    {
        $response = $this->requester->get(
            $this->buildUrl("/v1/license"),
            [],
            [
                "Authorization" => $token,
            ]
        );

        $this->guardAgainstInvalidResponse($response);

        return $response->json();
    }

    private function buildUrl($string)
    {
        return $this->url . $string;
    }

    private function guardAgainstInvalidResponse(Response $response = null)
    {
        if (!$response) {
            throw new Exception("Problem with connecting to the license server");
        }

        if ($response->isBadResponse()) {
            throw new Exception(
                "Invalid response code [{$response->getStatusCode()}] from license server"
            );
        }
    }
}
