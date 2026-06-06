<?php

namespace App\Services;

class GoogleAuthService
{
    /**
     * Verify Google ID token and return payload.
     *
     * @param string $idToken
     * @return array|null
     */
    public function verifyIdToken(string $idToken): ?array
    {
        $client = new \Google_Client(['client_id' => config('services.google.client_id')]);
        $payload = $client->verifyIdToken($idToken);
        
        // Google_Client::verifyIdToken() returns false on failure, not null
        return $payload ?: null;
    }
}
