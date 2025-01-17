<?php

declare(strict_types=1);

/*
 * Copyright MacFJA
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
 * Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace MacFJA\RediSearch\Redis\Client;

use Closure;
use MacFJA\RediSearch\Redis\Client;
use MacFJA\RediSearch\Redis\Command;
use RedisClient\Client\AbstractRedisClient;
use RedisClient\Pipeline\PipelineInterface;
use RuntimeException;

class CheprasovRedisClient extends AbstractClient
{
    /** @var AbstractRedisClient */
    private $redis;

    private function __construct(AbstractRedisClient $redis)
    {
        if (!static::supports($redis)) {
            throw new RuntimeException($this->getMissingMessage('Cheprasov Redis', false, [
                AbstractRedisClient::class => ['executeRaw', 'pipeline'],
                PipelineInterface::class => [],
            ]));
        }
        $this->redis = $redis;
    }

    public static function make($redis): Client
    {
        return new self($redis);
    }

    public function execute(Command $command)
    {
        $response = $this->redis->executeRaw(array_merge([$command->getId()], $command->getArguments()));

        return $command->parseResponse($response);
    }

    public function executeRaw(...$args)
    {
        return $this->redis->executeRaw(array_map('strval', $args));
    }

    public static function supports($redis): bool
    {
        return $redis instanceof AbstractRedisClient
            && static::fcqnExists(PipelineInterface::class)
            && method_exists(AbstractRedisClient::class, 'executeRaw')
            && method_exists(AbstractRedisClient::class, 'pipeline');
    }

    protected function doPipeline(Command ...$commands): array
    {
        false === static::$disableNotice
            && trigger_error('Warning, a workaround is used to enable custom command in pipeline for \\RedisClient\\Pipeline\\PipelineInterface', E_USER_NOTICE);

        return $this->redis->pipeline(function (PipelineInterface $pipeline) use ($commands): void {
            foreach ($commands as $command) {
                $closure = Closure::fromCallable(function () use ($command): void {
                    // @phpstan-ignore-next-line
                    $this->returnCommand(array_merge([$command->getId()], $command->getArguments()), null);
                });
                $closure->call($pipeline);
            }
        });
    }
}
