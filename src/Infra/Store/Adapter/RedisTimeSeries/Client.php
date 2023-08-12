<?php

declare(strict_types=1);

namespace App\Infra\Store\Adapter\RedisTimeSeries;

use App\Domain\Store\Adapter\RedisTimeSeries\ClientException\RedisAuthenticationException;
use App\Domain\Store\Adapter\RedisTimeSeries\ClientException\RedisClientException;
use App\Domain\Store\Adapter\RedisTimeSeries\ClientInterface;
use App\Domain\Store\Adapter\RedisTimeSeries\Vo\ConnectionParams;

/**
 * Adapter for simplified use of PhpRedis.
 * This client is extended by RedisTimeSeries.
 */
class Client implements ClientInterface
{
    protected \Redis $redis;
    private ConnectionParams $connectionParams;

    public function __construct(\Redis $redis, ConnectionParams $connectionParams)
    {
        $this->redis = $redis;
        $this->connectionParams = $connectionParams;
    }

    private function authenticate(?string $username, ?string $password): void
    {
        try {
            if ($password) {
                if ($username) {
                    // Calling auth() with an array throws a TypeError in some cases
                    $result = $this->redis->rawCommand('AUTH', $username, $password);
                } else {
                    $result = $this->redis->auth($password);
                }
                if (false === $result) {
                    throw new RedisAuthenticationException(sprintf('Failure authenticating user %s', $username ?: 'default'));
                }
            }
        } catch (\RedisException $e) {
            throw new RedisAuthenticationException(sprintf('Failure authenticating user %s: %s', $username ?: 'default', $e->getMessage()));
        }
    }

    public function set(string $key, array|string $value, int|float|null $expiration): bool|\Redis
    {
        $this->connectIfNeeded();
        $options = [];
        if (null !== $expiration) {
            if (is_int($expiration)) {
                $options['EX'] = $expiration; // in seconds
            }

            if (is_float($expiration)) {
                $options['PX'] = (int) ($expiration * 1000); // in ms
            }
        }

        return $this->redis->set($key, is_array($value) ? json_encode($value) : $value, $options);
    }

    public function get(string $key): false|\Redis|string
    {
        $this->connectIfNeeded();

        return $this->redis->get($key);
    }

    public function executeCommand(array $params): mixed
    {
        $this->connectIfNeeded();
        // UNDOCUMENTED FEATURE: option 8 is REDIS_OPT_REPLY_LITERAL
        $value = (PHP_VERSION_ID < 70300) ? '1' : 1;
        $this->redis->setOption(8, $value);

        return $this->redis->rawCommand(...$params);
    }

    private function connectIfNeeded(): void
    {
        if ($this->redis->isConnected()) {
            return;
        }

        $params = $this->connectionParams;

        $result = $this->redis->pconnect(
            $params->getHost(),
            $params->getPort(),
            $params->getTimeout(),
            $params->isPersistentConnection() ? gethostname() : null,
            $params->getRetryInterval()
        );

        if (false === $result) {
            throw new RedisClientException(sprintf('Unable to connect to redis server %s:%s: %s', $params->getHost(), $params->getPort(), $this->redis->getLastError() ?? 'unknown error'));
        }

        $this->authenticate($params->getUsername(), $params->getPassword());
    }

    public function unlink(string $key)
    {
        $this->redis->unlink($key);
    }
}
