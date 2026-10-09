<?php

declare(strict_types=1);

namespace Vampqwe\Engine\Tests\Security;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Vampqwe\Engine\Config\Config;
use Vampqwe\Engine\Security\LoginRateLimiter;

final class LoginRateLimiterTest extends TestCase
{
    public function testLoginLimitCombinesAccountAndIpBucketsAndCanResetAccount(): void
    {
        $limiter = new LoginRateLimiter(new ArrayAdapter(), new Config([
            'AUTH_LOGIN_LIMIT_PER_ACCOUNT' => '2',
            'AUTH_LOGIN_LIMIT_PER_IP' => '10',
            'AUTH_REGISTER_LIMIT_PER_IP' => '2',
            'AUTH_RATE_LIMIT_WINDOW_SECONDS' => '60',
        ]));

        self::assertNull($limiter->consumeLogin('user@example.test', '192.0.2.1'));
        self::assertNull($limiter->consumeLogin('USER@example.test', '192.0.2.1'));
        self::assertNotNull($limiter->consumeLogin('user@example.test', '192.0.2.1'));

        $limiter->resetAccount('user@example.test');

        self::assertNull($limiter->consumeLogin('user@example.test', '192.0.2.1'));
    }

    public function testRegistrationLimitIsScopedToIp(): void
    {
        $limiter = new LoginRateLimiter(new ArrayAdapter(), new Config([
            'AUTH_LOGIN_LIMIT_PER_ACCOUNT' => '5',
            'AUTH_LOGIN_LIMIT_PER_IP' => '10',
            'AUTH_REGISTER_LIMIT_PER_IP' => '2',
            'AUTH_RATE_LIMIT_WINDOW_SECONDS' => '60',
        ]));

        self::assertNull($limiter->consumeRegistration('192.0.2.1'));
        self::assertNull($limiter->consumeRegistration('192.0.2.1'));
        self::assertNotNull($limiter->consumeRegistration('192.0.2.1'));
        self::assertNull($limiter->consumeRegistration('192.0.2.2'));
    }
}