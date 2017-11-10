<?php

namespace NathanBurgess\SocialiteBungie;

use Illuminate\Support\Arr;
use SocialiteProviders\Manager\OAuth2\User;
use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'BUNGIE';

    protected $membershipId = '';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://www.bungie.net/en/oauth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return "https://www.bungie.net/platform/app/oauth/token/";
    }

    public function user()
    {
        if($this->hasInvalidState())
            throw new InvalidStateException;

        $response = $this->getAccessTokenResponse($this->getCode());
        $this->membershipId = $response['membership_id'];

        $user = $this->mapUserToObject($this->getUserByToken($token = Arr::get($response, 'access_token')));

        return $user->setToken($token)
                    ->setRefreshToken(Arr::get($response, 'refresh_token'))
                    ->setExpiresIn(Arr::get($response, 'expires_in'));
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://bungie.net/platform/User/GetMembershipsById/' . $this->membershipId . '/-1', [
            'headers' => [
                'X-API-Key' => env("BUNGIE_API_KEY"),
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        return (array)json_decode($response->getBody())->Response;
    }

    /**
     * {@inheritdoc}
     */
    protected function getCodeFields($state = null)
    {
        $fields = [
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUrl,
            'state'         => $state,
            'response_type' => 'code',
        ];

        return array_merge($fields, $this->parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        $data = $user["bungieNetUser"];
        return ( new User() )->setRaw($user)->map([
            'id'       => $data->membershipId,
            'nickname' => $data->displayName,
            'name'     => $data->blizzardDisplayName,
            'email'    => null,
            'avatar'   => $data->profilePicturePath
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }
}