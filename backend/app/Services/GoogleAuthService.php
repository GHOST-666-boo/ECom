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
        return $client->verifyIdToken($idToken);
    }
}
