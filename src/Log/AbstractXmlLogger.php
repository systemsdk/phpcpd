<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Log;

use Dom\XMLDocument;
use Systemsdk\PhpCPD\CodeCloneMap;
use Systemsdk\PhpCPD\Exceptions\LoggerException;

use function file_put_contents;
use function mb_convert_encoding;
use function preg_replace;

abstract class AbstractXmlLogger
{
    protected XMLDocument $document;

    public function __construct(
        private readonly string $filename
    ) {
        $this->document = XMLDocument::createEmpty('1.0', 'UTF-8');
        $this->document->formatOutput = true;
    }

    abstract public function processClones(CodeCloneMap $clones): void;

    /**
     * @throws LoggerException
     */
    protected function flush(): void
    {
        $xml = $this->document->saveXml();

        if ($xml === false) {
            throw new LoggerException('Can not dump the internal xml tree back into a string');
        }

        $results = file_put_contents($this->filename, $xml);

        if ($results === false) {
            throw new LoggerException('Can not save log file');
        }
    }

    /**
     * @throws LoggerException
     */
    protected function convertToUtf8(string $string): string
    {
        if (!$this->isUtf8($string)) {
            /** @var string|false $string */
            $string = mb_convert_encoding($string, 'UTF-8');

            if ($string === false) {
                throw new LoggerException('Cannot convert to UTF-8');
            }
        }

        return (string)$string;
    }

    protected function isUtf8(string $string): bool
    {
        return mb_check_encoding($string, 'UTF-8');
    }

    /**
     * @throws LoggerException
     */
    protected function escapeForXml(string $string): string
    {
        $string = $this->convertToUtf8($string);

        return (string)preg_replace(
            '/[^\x09\x0A\x0D\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]/u',
            "\xEF\xBF\xBD",
            $string
        );
    }
}
