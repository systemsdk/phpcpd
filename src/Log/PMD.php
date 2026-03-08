<?php

declare(strict_types=1);

namespace Systemsdk\PhpCPD\Log;

use DateTime;
use DateTimeInterface;
use Dom\Element;
use DOMException;
use Systemsdk\PhpCPD\Cli\Application;
use Systemsdk\PhpCPD\CodeCloneMap;
use Systemsdk\PhpCPD\Exceptions\LoggerException;

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

    /**
     * @throws LoggerException
     */
    public function processClones(CodeCloneMap $clones): void
    {
        try {
            /** @var Element $cpd */
            $cpd = $this->document->createElement(self::CPD_ELEMENT_NAME);
            $this->document->appendChild($cpd);

            $cpd->setAttribute(self::ATTRIBUTE_XMLNS_NAME, self::ATTRIBUTE_XMLNS_VALUE);
            $cpd->setAttribute(self::ATTRIBUTE_XMLNS_XSI_NAME, self::ATTRIBUTE_XMLNS_XSI_VALUE);
            $cpd->setAttribute(self::ATTRIBUTE_PHPCPD_VERSION_NAME, Application::VERSION);
            $cpd->setAttribute(
                self::ATTRIBUTE_TIMESTAMP_NAME,
                new DateTime()->format(DateTimeInterface::ATOM)
            );
            $cpd->setAttribute(self::ATTRIBUTE_VERSION_NAME, self::ATTRIBUTE_VERSION_1_VALUE);
            $cpd->setAttribute(
                self::ATTRIBUTE_XSI_SCHEMA_LOCATION_NAME,
                self::ATTRIBUTE_XSI_SCHEMA_LOCATION_VALUE
            );

            foreach ($clones as $clone) {
                /** @var Element $duplication */
                $duplication = $this->document->createElement(self::DUPLICATION_ELEMENT_NAME);
                $cpd->appendChild($duplication);

                $duplication->setAttribute(self::ATTRIBUTE_LINES_NAME, (string)$clone->numberOfLines());
                $duplication->setAttribute(self::ATTRIBUTE_TOKENS_NAME, (string)$clone->numberOfTokens());

                foreach ($clone->files() as $codeCloneFile) {
                    /** @var Element $file */
                    $file = $this->document->createElement(self::FILE_ELEMENT_NAME);
                    $duplication->appendChild($file);

                    $file->setAttribute(self::ATTRIBUTE_LINE_NAME, (string)$codeCloneFile->startLine());
                    $file->setAttribute(self::ATTRIBUTE_END_LINE_NAME, (string)$codeCloneFile->endLine());
                    $file->setAttribute(self::ATTRIBUTE_PATH_NAME, $codeCloneFile->name());
                }

                $codeFragmentEl = $this->document->createElement(self::CODE_FRAGMENT_ELEMENT_NAME);
                $cdata = $this->document->createCDATASection($this->escapeForXml($clone->lines()));
                $codeFragmentEl->appendChild($cdata);
                $duplication->appendChild($codeFragmentEl);
            }

            $this->flush();
        } catch (DOMException $exception) {
            throw new LoggerException($exception->getMessage());
        }
    }
}
