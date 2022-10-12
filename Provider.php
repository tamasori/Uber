<?php

namespace SocialiteProviders\Uber;

use GuzzleHttp\Client;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'UBER';

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected $scopes = ['profile', 'partner.accounts', 'partner.rewards'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://login.uber.com/oauth/authorize',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://login.uber.com/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response_simple  = $this->getHttpClient()->get(
            'https://api.uber.com/v1/me',
            [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );
        $response_simple = json_decode((string)$response_simple->getBody(), true);

        $response_partner = [];
        $response_partner = $this->getHttpClient()->get(
            'https://api.uber.com/v1/partners/me',
            [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                ],
            ]
        );
        $response_partner = json_decode((string)$response_partner->getBody(), true);


        $response_tier = [];
        $response_tier    = $this->getHttpClient()->get(
            'https://api.uber.com/v1/partners/me/rewards/tier',
            [
                'headers'     => [
                    'Authorization' => 'Bearer '.$token,
                ],
                'http_errors' => false,
            ]
        );
        $response_tier = json_decode((string)$response_tier->getBody(), true);

        $user_data        = array_merge(
            $response_tier,
            $response_partner,
            $response_simple
        );

        return $user_data;
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map(
            [
                'id'       => $user['rider_id'],
                'nickname' => null,
                'name'     => $user['first_name'].' '.$user['last_name'],
                'email'    => $user['email'],
                'avatar'   => $user['picture'],
                'status'   => $user['activation_status'] ?? "",
                'uuid'     => $user['uuid'] ?? "",
                'tier'     => $user['current_tier'] ?? "",
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(
            parent::getTokenFields($code),
            [
                'grant_type' => 'authorization_code',
            ]
        );
    }
}
