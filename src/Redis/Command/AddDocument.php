<?php

namespace MacFJA\RediSearch\Redis\Command;

class AddDocument extends AbstractCommand
{
    protected $arguments;

    public function __construct(array $options = [], string $rediSearchVersion = self::MIN_IMPLEMENTED_VERSION)
    {
        parent::__construct($options, $rediSearchVersion);
    }

    public function getId(): string
    {
        return 'hset';
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
    }

    protected function getRequiredOptions(): array
    {
        return [];
    }
}