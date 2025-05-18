# PHP Copy/Paste Detector
`phpcpd` is a Copy/Paste Detector (CPD) for PHP code.

This is modified version of [sebastianbergmann/phpcpd](https://github.com/sebastianbergmann/phpcpd) due to `sebastianbergmann/phpcpd` is abandoned and no longer maintained.

[![PHP Copy/Paste Detector](https://github.com/systemsdk/phpcpd/actions/workflows/ci.yml/badge.svg)](https://github.com/systemsdk/phpcpd/actions/workflows/ci.yml)
[![Coverage Status](https://coveralls.io/repos/github/systemsdk/phpcpd/badge.svg)](https://coveralls.io/github/systemsdk/phpcpd)
[![MIT licensed](https://img.shields.io/badge/license-BSD-blue.svg)](LICENSE)

[Source code](https://github.com/systemsdk/phpcpd.git)

## Requirements
* PHP version 8.3 or later

## Installation
### Using composer
If you have installed composer, you can use the next cmd command:
```bash
composer require systemsdk/phpcpd --dev
```

### Manual installation
If you are not using composer, you have a possibility to download this tool.

Download the latest version [here](releases/) and put phar archive into your project.

Note: This tool is distributed as a [PHP Archive (PHAR)](https://php.net/phar).

## Usage example
```
$ php ./vendor/bin/phpcpd --fuzzy --verbose src tests
Copy/Paste Detector 8.2.2
14/14 [==============================>] 100% Loading & Processing
Found 1 code clones with 17 duplicated lines in 1 files:

  - /var/www/html/tests/Application/ApiKey/Transport/Controller/Api/v1/ApiKeyControllerTest.php:128-145 (17 lines)
    /var/www/html/tests/Application/ApiKey/Transport/Controller/Api/v1/ApiKeyControllerTest.php:153-170

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

0.05% duplicated lines out of 31339 total lines of code.
Average code clone size is 17 lines, the largest code clone has 17 lines

Time: 00:00.100, Memory: 10.00 MB
```

Note: If you are not using composer and have manual installation, you can use:
```
php phpcpd.phar --fuzzy --verbose src tests
```

## Html report
You are able to generate html report, but you need to have installed [Xalan](https://xalan.apache.org) tool locally or inside your Docker container.

Please find more details how to generate/use it [here](https://github.com/systemsdk/phpcpd/blob/master/docs/report.md).

## Requirements for the support team
* Docker Engine version 23.0 or later
* Docker Compose version 2.0 or later
* An editor or IDE

Note: OS recommendation - Linux Ubuntu based.

## Components for the support team
1. PHP 8.4 fpm
2. Composer 2
3. Phive 0.15
4. Phing 3.0
5. Xalan 1.12

## Setting up Docker and docker compose for the support team
For installing Docker Engine with docker compose please follow steps mentioned on page [Docker Engine](https://docs.docker.com/engine/install/).

Note 1: Please run next cmd after above step if you are using Linux OS: `sudo usermod -aG docker $USER`

Note 2: If you are using Docker Desktop for MacOS 12.2 or later - please enable [virtiofs](https://www.docker.com/blog/speed-boost-achievement-unlocked-on-docker-desktop-4-6-for-mac/) for performance (enabled by default since Docker Desktop v4.22).

## Setting up DEV environment for the support team
1.Clone this repository from GitHub.

2.Edit and set `XDEBUG_CONFIG=` inside `.env` file (optional, by default `XDEBUG_CONFIG=main`).

3.Configure `/docker/dev/xdebug-main.ini` (Linux/Windows) or `/docker/dev/xdebug-osx.ini` (MacOS) (optional).

4.Build, start and install the docker images from your terminal:
```bash
make build-dev
make start
make setup
```

## Getting shell to container for the support team
After application will start (`make start`) and in order to get shell access inside php container you can run the following command:
```bash
make ssh
```
Note: Please use `exit` command in order to return from container's shell to local shell.

## Building container
In case you edited Dockerfile or other environment configuration, you'll need to build container again using next commands:
```bash
make down
make build-dev
make start
```

## Start and stop environment containers for the support team
Please use next make commands in order to start and stop environment:
```bash
make start
make stop
```

## Stop and remove environment containers, networks for the support team
Please use next make commands in order to stop and remove environment containers, networks:
```bash
make down
```

## Additional main command available for the support team
```bash
make build-dev

make start

make stop

make down

make restart

make ssh
make ssh-root

make setup

make update

make composer-audit

make info
make help

make phar
make signed-phar

make phpunit
make phpcs
make ecs
make ecs-fix
make phpstan

make phpcpd-run
make phpcpd-html-report

make logs

etc....
```
Notes: Please see more commands in Makefile

## Architecture & packages
* [cli-parser](https://packagist.org/packages/sebastian/cli-parser)
* [version](https://packagist.org/packages/sebastian/version)
* [php-file-iterator](https://packagist.org/packages/phpunit/php-file-iterator)
* [php-timer](https://packagist.org/packages/phpunit/php-timer)
* [phpunit](https://packagist.org/packages/phpunit/phpunit)
* [composer-bin-plugin](https://packagist.org/packages/bamarni/composer-bin-plugin)
* [security-advisories](https://packagist.org/packages/roave/security-advisories)
* [easy-coding-standard](https://packagist.org/packages/symplify/easy-coding-standard)
* [phpstan](https://packagist.org/packages/phpstan/phpstan)
* [php-coveralls](https://github.com/php-coveralls/php-coveralls)

## Guidelines for the support team
* [Report](https://github.com/systemsdk/phpcpd/blob/master/docs/report.md)
* [Schema](https://github.com/systemsdk/phpcpd/blob/master/docs/schema.md)
* [Phive](https://github.com/phar-io/phive)
* [Phing](https://www.phing.info)
* [Xalan](https://xalan.apache.org)

## Working on the project for the support team
1. For new feature development, fork `develop` branch into a new branch with one of the two patterns:
    * `feature/{ticketNo}`
2. Commit often and write descriptive commit messages, so it's easier to follow steps taken when reviewing.
3. Push this branch to the repo and create pull request into `develop` to get feedback, with the format `feature/{ticketNo}` - "Short descriptive title of Jira task".
4. Iterate as needed.
5. Make sure that "All checks have passed" on CircleCI(or another one in case you are not using CircleCI) and status is green.
6. When PR is approved, it will be squashed & merged, into `develop` and later merged into `release/{No}` for deployment.

Note: You can find git flow detail example [here](https://danielkummer.github.io/git-flow-cheatsheet).
