<?php

declare(strict_types=1);

/*
 * CoreShop
 *
 * This source file is available under two different licenses:
 *  - GNU General Public License version 3 (GPLv3)
 *  - CoreShop Commercial License (CCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) CoreShop GmbH (https://www.coreshop.org)
 * @license    https://www.coreshop.org/license     GPLv3 and CCL
 *
 */

namespace CoreShop\Bundle\PimcoreMcpBundle\Security;

use Pimcore\Model\User;
use Pimcore\Security\User\User as SecurityUser;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

final class McpAccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private readonly string $apiToken,
        private readonly string $adminUsername,
    ) {
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        if ('' === $this->apiToken) {
            throw new AuthenticationException('MCP API token is not configured. Set the MCP_API_TOKEN environment variable.');
        }

        if (!hash_equals($this->apiToken, $accessToken)) {
            throw new CustomUserMessageAuthenticationException('Invalid MCP API token.');
        }

        return new UserBadge($this->adminUsername, function (string $username): SecurityUser {
            $pimcoreUser = User::getByName($username);

            if (!$pimcoreUser instanceof User) {
                throw new CustomUserMessageAuthenticationException(
                    sprintf('Pimcore admin user "%s" not found. Check MCP_ADMIN_USERNAME.', $username),
                );
            }

            if (!$pimcoreUser->isActive()) {
                throw new CustomUserMessageAuthenticationException(
                    sprintf('Pimcore admin user "%s" is not active.', $username),
                );
            }

            return new SecurityUser($pimcoreUser);
        });
    }
}
