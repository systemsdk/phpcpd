<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Log;

use DOMElement;
use DOMException;
use DOMNode;
use Systemsdk\PhpCPD\CodeCloneMap;
use Systemsdk\PhpCPD\Exceptions\LoggerException;

use function is_object;
use function sprintf;

final class PMD extends AbstractXmlLogger
{
    private const string CPD_ELEMENT_NAME = 'pmd-cpd';
    private const string DUPLICATION_ELEMENT_NAME = 'duplication';
    private const string FILE_ELEMENT_NAME = 'file';
    private const string CODE_FRAGMENT_ELEMENT_NAME = 'codefragment';
    private const string ATTRIBUTE_LINES_NAME = 'lines';
    private const string ATTRIBUTE_TOKENS_NAME = 'tokens';
    private const string ATTRIBUTE_PATH_NAME = 'path';
    private const string ATTRIBUTE_LINE_NAME = 'line';
    private const string ACTION_CREATE_ELEMENT = 'create-element';
    private const string ACTION_APPEND_CHILD = 'append-child';
    private const string ACTION_SET_ATTRIBUTE = 'set-attribute';

    /**
     * @throws LoggerException
     */
    public function processClones(CodeCloneMap $clones): void
    {
        try {
            $cpd = $this->document->createElement(self::CPD_ELEMENT_NAME);
            $this->checkResult($cpd, self::CPD_ELEMENT_NAME, self::ACTION_CREATE_ELEMENT);

            $result = $this->document->appendChild($cpd);
            $this->checkResult($result, self::CPD_ELEMENT_NAME, self::ACTION_APPEND_CHILD);

            foreach ($clones as $clone) {
                $duplicationEl = $this->document->createElement(self::DUPLICATION_ELEMENT_NAME);
                $this->checkResult($duplicationEl, self::DUPLICATION_ELEMENT_NAME, self::ACTION_CREATE_ELEMENT);

                $duplication = $cpd->appendChild($duplicationEl);
                $this->checkResult($duplication, self::DUPLICATION_ELEMENT_NAME, self::ACTION_APPEND_CHILD);

                /** @var DOMElement $duplication */
                $result1 = $duplication->setAttribute(self::ATTRIBUTE_LINES_NAME, (string)$clone->numberOfLines());
                $this->checkResult($result1, self::ATTRIBUTE_LINES_NAME, self::ACTION_SET_ATTRIBUTE);

                $result2 = $duplication->setAttribute(self::ATTRIBUTE_TOKENS_NAME, (string)$clone->numberOfTokens());
                $this->checkResult($result2, self::ATTRIBUTE_TOKENS_NAME, self::ACTION_SET_ATTRIBUTE);

                foreach ($clone->files() as $codeCloneFile) {
                    $fileEl = $this->document->createElement(self::FILE_ELEMENT_NAME);
                    $this->checkResult($fileEl, self::FILE_ELEMENT_NAME, self::ACTION_CREATE_ELEMENT);

                    $file = $duplication->appendChild($fileEl);
                    $this->checkResult($file, self::FILE_ELEMENT_NAME, self::ACTION_APPEND_CHILD);

                    /** @var DOMElement $file */
                    $result1 = $file->setAttribute(self::ATTRIBUTE_PATH_NAME, $codeCloneFile->name());
                    $this->checkResult($result1, self::ATTRIBUTE_PATH_NAME, self::ACTION_SET_ATTRIBUTE);

                    $result2 = $file->setAttribute(self::ATTRIBUTE_LINE_NAME, (string)$codeCloneFile->startLine());
                    $this->checkResult($result2, self::ATTRIBUTE_LINE_NAME, self::ACTION_SET_ATTRIBUTE);
                }

                $codeFragmentEl = $this->document->createElement(
                    self::CODE_FRAGMENT_ELEMENT_NAME,
                    $this->escapeForXml($clone->lines())
                );
                $this->checkResult($codeFragmentEl, self::CODE_FRAGMENT_ELEMENT_NAME, self::ACTION_CREATE_ELEMENT);

                $codeFragment = $duplication->appendChild($codeFragmentEl);
                $this->checkResult($codeFragment, self::CODE_FRAGMENT_ELEMENT_NAME, self::ACTION_APPEND_CHILD);
            }

            $this->flush();
        } catch (DOMException $exception) {
            throw new LoggerException($exception->getMessage());
        }
    }

    /**
     * @throws LoggerException
     */
    private function checkResult(DOMNode|false $result, string $name, string $type): void
    {
        if (is_object($result)) {
            return;
        }

        $error = 'Unknown action: %s';

        if ($type === self::ACTION_CREATE_ELEMENT) {
            $error = 'Can not create element: %s';
        } elseif ($type === self::ACTION_APPEND_CHILD) {
            $error = 'Can not add new child at the end: %s';
        } elseif ($type === self::ACTION_SET_ATTRIBUTE) {
            $error = 'Can not set attribute: %s';
        }

        $this->generateException($error, $name);
    }

    /**
     * @throws LoggerException
     */
    private function generateException(string $error, string $name): void
    {
        throw new LoggerException(sprintf($error, $name));
    }
}
