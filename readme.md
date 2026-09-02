# PHP Copy/Paste Detector (PHPCPD)
An enterprise-grade static analysis tool for detecting code duplication in modern PHP applications.

`phpcpd` analyzes your PHP source code to find structurally identical clones, helping your team reduce technical debt, enforce DRY (Don't Repeat Yourself) principles, and maintain high architectural standards.

Optimized for modern workflows, this tool provides seamless integration with CI/CD pipelines, GitHub Code Scanning (SARIF), and enterprise reporting systems.

Note: This repository is an actively maintained, heavily upgraded continuation of the abandoned [sebastianbergmann/phpcpd](https://github.com/sebastianbergmann/phpcpd). It has been re-architected to meet the strict demands of enterprise environments and modern PHP 8.4+ standards.

[![PHP Copy/Paste Detector](https://github.com/systemsdk/phpcpd/actions/workflows/ci.yml/badge.svg)](https://github.com/systemsdk/phpcpd/actions/workflows/ci.yml)
[![Coverage Status](https://coveralls.io/repos/github/systemsdk/phpcpd/badge.svg)](https://coveralls.io/github/systemsdk/phpcpd)
[![License](https://img.shields.io/badge/license-BSD-blue.svg)](LICENSE)

[Source code](https://github.com/systemsdk/phpcpd.git)

## Requirements

* **PHP 8.4** or later

## Installation

### Composer (Recommended)
The preferred method to install `phpcpd` is as a development-time dependency using [Composer](https://getcomposer.org/):

```bash
composer require --dev systemsdk/phpcpd 
```

### Standalone PHAR (Manual Installation)
If you prefer not to manage this tool via Composer, you can download the standalone [PHP Archive (PHAR)](https://php.net/phar), which bundles all necessary dependencies into a single file.

1. Download the latest phpcpd.phar from the [Releases page](https://github.com/systemsdk/phpcpd/tree/master/releases).
2. Make the file executable and place it either in your project root (e.g., `./phpcpd.phar`) or move it to your system's global binaries directory.

You can now run it locally (`./phpcpd.phar`) or globally (`phpcpd`), depending on your preference.

## Usage example
```bash
$ ./vendor/bin/phpcpd --fuzzy --verbose src tests
Copy/Paste Detector 9.1.0
 458/458 [==============================>] 100% Loading & Processing
Found 1 code clones with 17 duplicated lines in 1 files:

  - /var/www/html/tests/Application/Identity/Transport/Controller/Api/V1/ApiKeyControllerTest.php:138-155 (17 lines) [inconsistent]
    /var/www/html/tests/Application/Identity/Transport/Controller/Api/V1/ApiKeyControllerTest.php:163-180

    public function testThatFindOneActionForRootUserReturnsSuccessResponse(): void
    {
        $client = $this->getTestClient('john-root', 'password-root');

        $resource = static::getContainer()->get(ApiKeyResource::class);
        $apiKeyEntity = $resource->findOneBy([
            'description' => 'ApiKey Description: api',
        ]);
        self::assertInstanceOf(ApiKey::class, $apiKeyEntity);

        $client->request('GET', static::$baseUrl . '/' . $apiKeyEntity->getId());
        $response = $client->getResponse();
        $content = $response->getContent();
        self::assertNotFalse($content);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), "Response:\n" . $response);
        $responseData = JSON::decode($content, true);
        $this->checkBasicFieldsInResponse($responseData);

0.05% duplicated lines out of 32210 total lines of code.
Average code clone size is 17 lines, the largest code clone has 17 lines

ℹ️  Note: 1 clone(s) (38 lines) were suppressed by #[SuppressCpd] attribute(s).

Time: 00:00.373, Memory: 10.00 MB
```

Note: If you installed the tool manually via the standalone PHAR archive, you can run it using:
```bash
php phpcpd.phar --fuzzy --verbose src tests
```

## Configuration
Instead of passing multiple arguments via the command line, `phpcpd` supports a fluent configuration builder. This allows you to define your analysis rules cleanly, keep your CI/CD pipelines readable, and easily version-control your settings.

To set up your configuration:

1. Copy the [`phpcpd.config.php.dist`](https://github.com/systemsdk/phpcpd/blob/master/phpcpd.config.php.dist) template from our GitHub repository.
2. Place it in the root directory of your project.
3. Rename the file to `phpcpd.config.php`.
4. Open the file and adjust the parameters (such as directories, algorithms, thresholds, and reporting formats) to suit your project's needs.

By default, running `./vendor/bin/phpcpd` (or `phpcpd` globally) will automatically locate and apply this configuration. Any arguments passed via the command line will override the settings defined in the configuration file.

## Suppressing False Positives
You can tell PHPCPD to ignore specific parts of your code using the `#[SuppressCpd]` attribute. This is particularly useful for generated code, large arrays, or boilerplate methods where duplication is intentional or unavoidable.

To use it, simply add the `#[SuppressCpd]` attribute directly above the class, method, or function you want to exclude from the analysis:

```php
use Systemsdk\PhpCPD\Attributes\SuppressCpd;

// 1. Suppress an entire class
#[SuppressCpd]
class LegacyDataFixtures
{
    // All methods and properties inside this class will be ignored
}

// 2. Suppress a specific method
class ReportGenerator
{
    #[SuppressCpd]
    public function generateBoilerplate(): void
    {
        // This specific method will be ignored, but the rest of the class is analyzed
    }
}

// 3. Suppress a standalone function
#[SuppressCpd]
function globalHelperFunction() {
    // ...
}
```

Note: For transparency and enterprise auditing, phpcpd tracks all suppressed clones. The total number of suppressed instances and lines is printed in the CLI output and included in the JSON and HTML reports.

## HTML Report
`phpcpd` supports generating visual HTML reports for easier analysis of duplicated code. To utilize this feature, you must have the [Xalan](https://xalan.apache.org/) XSLT processor installed on your local machine or within your Docker environment.

For detailed instructions on how to generate and view these reports, please refer to the [HTML Report Documentation](https://github.com/systemsdk/phpcpd/blob/master/docs/report.md).

## GitHub Code Scanning (SARIF Report)
`phpcpd` supports exporting results in the SARIF 2.1.0 (Static Analysis Results Interchange Format) standard. This allows you to integrate the tool seamlessly with GitHub Code Scanning, GitLab CI, and other modern security dashboards.

You can generate a SARIF report by using the `--log-sarif` CLI option or by defining `->withSarifLogfile('reports/phpcpd/phpcpd-report.sarif')` in your configuration file.

### Example: GitHub Actions Integration
To automatically run `phpcpd` on every push and display the duplicated code directly in your GitHub Pull Requests, add the following workflow to your repository (e.g., `.github/workflows/ci.yml`):

```yaml
name: PHP Copy/Paste Detector

on:
    push:
        branches:
            - main
            - develop
    pull_request:
        branches:
            - main
            - develop

jobs:
    analyze:
        name: Code Duplication Analysis
        runs-on: ubuntu-latest
        permissions:
            security-events: write
            actions: read
            contents: read
        steps:
            - name: Checkout repository
              uses: actions/checkout@v4
            - name: Setup PHP
              uses: shivammathur/setup-php@v2
              with:
                  php-version: '8.5'
                  coverage: none
            - name: Install dependencies
              run: composer install --prefer-dist --no-progress
            - name: Run PHPCPD and generate SARIF
              # We use continue-on-error so the workflow doesn't fail before the upload step
              run: ./vendor/bin/phpcpd --log-sarif=reports/phpcpd/phpcpd-report.sarif src
              continue-on-error: true
            - name: Upload SARIF report to GitHub Security
              uses: github/codeql-action/upload-sarif@v4
              if: always()
              with:
                  sarif_file: reports/phpcpd/phpcpd-report.sarif
                  category: phpcpd
```

## Excluding Files and Directories (Exact Segment Matching)
The `--exclude` logic has been completely overhauled to fix long-standing upstream issues (such as [Issue #202](https://github.com/sebastianbergmann/phpcpd/issues/202)).

It now uses **exact path segment matching**. This means excluding a directory named `Vendor` will safely ignore the `Vendor/` folder, but will **not** accidentally exclude a folder named `Vendor2/`. You can reliably ignore specific directories, nested paths, or individual files.

### Using the CLI
Pass the `--exclude` flag for each specific path segment you want to ignore:

```bash
./vendor/bin/phpcpd --exclude vendor --exclude tests/Fixtures src tests
```

Note: In this example, the engine will scan `src` and `tests`, but will completely ignore the `vendor` directory and anything inside `tests/Fixtures`.

### Using `phpcpd.config.php`
For a more permanent setup, pass an array of paths or substrings to the exclude() method in your configuration builder:
```php
// ...
    // Exact path segments or file names to exclude from analysis
->exclude([
    'vendor',                      // Excludes any directory exactly named "vendor"
    'tests/Fixtures',              // Excludes specific nested directories
    'src/Legacy/Generated.php'     // Excludes a specific file
])
// ...
```

## Command-Line Options
You can customize the behavior of PHPCPD using the following command-line options.
```bash
Options for selecting files:
--suffix <suffix>   Include files with names ending on <suffix> (default: .php; can be given multiple times)
--exclude <path>    Exclude files with <path> in their path (can be given multiple times)

Options for analysing files:
--algorithm <name>  Select which algorithm to use ('rabin-karp' (default) or 'suffix-tree')
--fuzzy             Fuzz variable names
--min-lines <N>     Minimum number of identical lines (default: 5)
--min-tokens <N>    Minimum number of identical tokens (default: 70)
--edit-distance <N> Distance in number of edits between two clones (only for suffix-tree; default: 0)
--head-equality <N> Minimum equality at start of clone (only for suffix-tree; default: 10)
--verbose           Print results details
--quiet             Disable all output except file logs and error messages
--ignore-no-files   Return a success exit code if no files were found

Options for CI/CD:
--max-percentage <N>         Fail only if the percentage of duplicated lines exceeds this threshold (default: 0.0)
--ignore-violations-on-exit  Always exit with 0, regardless of violations found

Options for report generation:
--log-pmd <file>    Write log in PMD-CPD XML format to <file>
--log-json <file>   Write log in JSON format to <file>
--log-sarif <file>  Write log in SARIF 2.1.0 format to <file>

General options:
--version           Display version
--help              Display help
```

## Additional Guidelines
* [General Guidelines for the Support Team](https://github.com/systemsdk/phpcpd/blob/master/docs/support.md)
* [HTML Report Generation Documentation](https://github.com/systemsdk/phpcpd/blob/master/docs/report.md)
