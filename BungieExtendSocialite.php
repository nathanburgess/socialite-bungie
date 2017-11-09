<?php

namespace NathanBurgess\SocialiteBungie;

use SocialiteProviders\Manager\SocialiteWasCalled;

class BungieExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'bungie', __NAMESPACE__.'\Provider'
        );
    }
}
