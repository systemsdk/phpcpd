<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Log;

use DateTime;
use DateTimeInterface;
use DOMElement;
use DOMException;
use DOMNode;
use Systemsdk\PhpCPD\Cli\Application;
use Systemsdk\PhpCPD\CodeCloneMap;
use Systemsdk\PhpCPD\Exceptions\LoggerException;

use function is_object;
use function sprintf;

final class PMD extends AbstractXmlLogger
{
    private const string SCHEMA_HOST = 'systemsdk.github.io';
    private const string REPORT_V1_XSD = 'phpcpd-report-v1_0_0.xsd';
    private const string CPD_ELEMENT_NAME = 'pmd-cpd';
    private const string DUPLICATION_ELEMENT_NAME = 'duplication';
    private const string FILE_ELEMENT_NAME = 'file';
    private const string CODE_FRAGMENT_ELEMENT_NAME = 'codefragment';
    private const string ATTRIBUTE_XMLNS_NAME = 'xmlns';
    private const string ATTRIBUTE_XMLNS_VALUE = 'https://' . self::SCHEMA_HOST . '/phpcpd/report';
    private const string ATTRIBUTE_XMLNS_XSI_NAME = 'xmlns:xsi';
    private const string ATTRIBUTE_XMLNS_XSI_VALUE = 'http://www.w3.org/2001/XMLSchema-instance';
    private const string ATTRIBUTE_PHPCPD_VERSION_NAME = 'phpcpdVersion';
    private const string ATTRIBUTE_TIMESTAMP_NAME = 'timestamp';
    private const string ATTRIBUTE_VERSION_NAME = 'version';
    private const string ATTRIBUTE_VERSION_1_VALUE = '1.0.0';
    private const string ATTRIBUTE_XSI_SCHEMA_LOCATION_NAME = 'xsi:schemaLocation';
    private const string ATTRIBUTE_XSI_SCHEMA_LOCATION_VALUE = self::ATTRIBUTE_XMLNS_VALUE . ' https://'
        . self::SCHEMA_HOST . '/phpcpd/report/' . self::REPORT_V1_XSD;
    private const string ATTRIBUTE_LINES_NAME = 'lines';
    private const string ATTRIBUTE_TOKENS_NAME = 'tokens';
    private const string ATTRIBUTE_PATH_NAME = 'path';
    private const string ATTRIBUTE_LINE_NAME = 'line';
    private const string ATTRIBUTE_END_LINE_NAME = 'endline';
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

            /** @var DOMElement $result */
            $result->setAttribute(self::ATTRIBUTE_XMLNS_NAME, self::ATTRIBUTE_XMLNS_VALUE);

            $resultAttr = $result->setAttribute(self::ATTRIBUTE_XMLNS_XSI_NAME, self::ATTRIBUTE_XMLNS_XSI_VALUE);
            $this->checkResult($resultAttr, self::ATTRIBUTE_XMLNS_XSI_NAME, self::ACTION_SET_ATTRIBUTE);

            $resultAttr = $result->setAttribute(self::ATTRIBUTE_PHPCPD_VERSION_NAME, Application::VERSION);
            $this->checkResult($resultAttr, self::ATTRIBUTE_PHPCPD_VERSION_NAME, self::ACTION_SET_ATTRIBUTE);

            $resultAttr = $result->setAttribute(
                self::ATTRIBUTE_TIMESTAMP_NAME,
                (new DateTime())->format(DateTimeInterface::ATOM)
            );
            $this->checkResult($resultAttr, self::ATTRIBUTE_TIMESTAMP_NAME, self::ACTION_SET_ATTRIBUTE);

            $resultAttr = $result->setAttribute(self::ATTRIBUTE_VERSION_NAME, self::ATTRIBUTE_VERSION_1_VALUE);
            $this->checkResult($resultAttr, self::ATTRIBUTE_VERSION_NAME, self::ACTION_SET_ATTRIBUTE);

            $resultAttr = $result->setAttribute(
                self::ATTRIBUTE_XSI_SCHEMA_LOCATION_NAME,
                self::ATTRIBUTE_XSI_SCHEMA_LOCATION_VALUE
            );
            $this->checkResult($resultAttr, self::ATTRIBUTE_XSI_SCHEMA_LOCATION_NAME, self::ACTION_SET_ATTRIBUTE);

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
                    $result1 = $file->setAttribute(self::ATTRIBUTE_LINE_NAME, (string)$codeCloneFile->startLine());
                    $this->checkResult($result1, self::ATTRIBUTE_LINE_NAME, self::ACTION_SET_ATTRIBUTE);

                    $result2 = $file->setAttribute(self::ATTRIBUTE_END_LINE_NAME, (string)$codeCloneFile->endLine());
                    $this->checkResult($result2, self::ATTRIBUTE_END_LINE_NAME, self::ACTION_SET_ATTRIBUTE);

                    $result3 = $file->setAttribute(self::ATTRIBUTE_PATH_NAME, $codeCloneFile->name());
                    $this->checkResult($result3, self::ATTRIBUTE_PATH_NAME, self::ACTION_SET_ATTRIBUTE);
                }

                $codeFragmentEl = $this->document->createElement(self::CODE_FRAGMENT_ELEMENT_NAME);
                $this->checkResult($codeFragmentEl, self::CODE_FRAGMENT_ELEMENT_NAME, self::ACTION_CREATE_ELEMENT);

                $cdata = $this->document->createCDATASection($this->escapeForXml($clone->lines()));
                $this->checkResult($cdata, self::CODE_FRAGMENT_ELEMENT_NAME, self::ACTION_CREATE_ELEMENT);

                $result = $codeFragmentEl->appendChild($cdata);
                $this->checkResult($result, self::CODE_FRAGMENT_ELEMENT_NAME, self::ACTION_APPEND_CHILD);

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
