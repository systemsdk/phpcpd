<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Detector\Strategy;

use Systemsdk\PhpCPD\CodeCloneMap;

use const T_ATTRIBUTE;
use const T_CLOSE_TAG;
use const T_COMMENT;
use const T_DECLARE;
use const T_DOC_COMMENT;
use const T_INLINE_HTML;
use const T_NS_SEPARATOR;
use const T_OPEN_TAG;
use const T_OPEN_TAG_WITH_ECHO;
use const T_USE;
use const T_WHITESPACE;

abstract class AbstractStrategy
{
    /**
     * @var array<int, bool>
     */
    protected array $tokensIgnoreList = [
        T_INLINE_HTML => true,
        T_COMMENT => true,
        T_DOC_COMMENT => true,
        T_OPEN_TAG => true,
        T_OPEN_TAG_WITH_ECHO => true,
        T_CLOSE_TAG => true,
        T_DECLARE => true,
        T_WHITESPACE => true,
        T_USE => true,
        T_NS_SEPARATOR => true,
        T_ATTRIBUTE => true,
    ];

    public function __construct(
        protected StrategyConfiguration $config
    ) {
    }

    public function setConfig(StrategyConfiguration $config): void
    {
        $this->config = $config;
    }

    abstract public function processFile(string $file, CodeCloneMap $result): void;

    public function postProcess(): void
    {
    }
}
