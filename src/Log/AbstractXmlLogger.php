<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Log;

use DOMDocument;
use Systemsdk\PhpCPD\CodeCloneMap;
use Systemsdk\PhpCPD\Exceptions\LoggerException;

use function file_put_contents;
use function htmlspecialchars;
use function mb_convert_encoding;
use function ord;
use function preg_replace;
use function strlen;

use const ENT_COMPAT;

abstract class AbstractXmlLogger
{
    protected DOMDocument $document;

    public function __construct(
        private readonly string $filename
    ) {
        $this->document = new DOMDocument('1.0', 'UTF-8');
        $this->document->formatOutput = true;
    }

    abstract public function processClones(CodeCloneMap $clones): void;

    /**
     * @throws LoggerException
     */
    protected function flush(): void
    {
        $xml = $this->document->saveXML();

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
        $length = strlen($string);

        for ($i = 0; $i < $length; $i++) {
            if (ord($string[$i]) < 0x80) {
                $n = 0;
            } elseif ((ord($string[$i]) & 0xE0) === 0xC0) {
                $n = 1;
            } elseif ((ord($string[$i]) & 0xF0) === 0xE0) {
                $n = 2;
            } elseif ((ord($string[$i]) & 0xF0) === 0xF0) {
                $n = 3;
            } else {
                return false;
            }

            for ($j = 0; $j < $n; $j++) {
                if ((++$i === $length) || ((ord($string[$i]) & 0xC0) !== 0x80)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @throws LoggerException
     */
    protected function escapeForXml(string $string): string
    {
        $string = $this->convertToUtf8($string);
        $string = (string)preg_replace(
            '/[^\x09\x0A\x0D\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]/u',
            "\xEF\xBF\xBD",
            $string
        );

        return htmlspecialchars($string, ENT_COMPAT);
    }
}
