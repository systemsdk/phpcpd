# General Guidelines for the Support Team

## Requirements
* Docker Engine version 23.0 or later
* Docker Compose version 2.0 or later
* An editor or IDE

Note: We recommend using a Linux Ubuntu-based OS for the best experience.

## Components
1. PHP 8.5 fpm
2. Composer 2
3. Phive 0.16
4. Phing 3.1
5. Xalan 1.12

## Setting up Docker Engine & Docker Compose
To install Docker Engine and Docker Compose, please follow the official [Docker Engine Installation Guide](https://docs.docker.com/engine/install/).

**For Linux Users:**
After installation, run the following command to manage Docker as a non-root user (this allows you to run Docker without `sudo`):
```bash
sudo usermod -aG docker $USER
```

Note: You must log out and log back in for this change to take effect.

**For macOS Users:**
If you are using Docker Desktop for macOS 12.2 or later, we highly recommend enabling [virtiofs](https://www.docker.com/blog/speed-boost-achievement-unlocked-on-docker-desktop-4-6-for-mac/) for a significant performance boost.

Note: Enabled by default since Docker Desktop v4.22.

## Setting up the DEV environment
1. Clone this repository from GitHub.

2. Edit and set `XDEBUG_CONFIG=` inside `.env` file (optional, by default `XDEBUG_CONFIG=main`).

3. Configure `/docker/dev/xdebug-main.ini` (Linux/Windows) or `/docker/dev/xdebug-osx.ini` (MacOS) (optional).

4. Build, start and install the docker images from your terminal:
```bash
make build-dev
make start
make setup
```

## Accessing Container Shells
Once the application is running (via `make start`), you can easily access the command line inside your containers.

To open a shell inside the container, run:
```bash
make ssh
```

Tip: Type `exit` and press Enter to leave the container's shell and return to your local terminal.

## Rebuilding Containers
If you modify any `Dockerfile` or environment configurations, you will need to rebuild the containers using the following commands:
```bash
make down
make build-dev
make start
```

## Starting and Stopping Containers
Use the following commands to start or stop the development environment:
```bash
make start
make stop
```

## Stopping and Removing Containers
To completely stop and remove all environment containers and networks, use the following command:
```bash
make down
```

## Available Makefile Commands
Here is a reference list of the primary commands available for managing the environment, databases, logs and testing:
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
make phpcpd-sarif-report

make logs
```
Note: For a complete list of all available commands, please inspect the `Makefile` directly or run `make help`.

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

## Guidelines
* [Report](https://github.com/systemsdk/phpcpd/blob/master/docs/report.md)
* [Schema](https://github.com/systemsdk/phpcpd/blob/master/docs/schema.md)
* [Phive](https://github.com/phar-io/phive)
* [Phing](https://www.phing.info)
* [Xalan](https://xalan.apache.org)

## Development Workflow
1. **Branching:** Create a new branch from `develop` using one of the following patterns:
    * `feature/{ticketNo}`
    * `bugfix/{ticketNo}`
2. **Commits:** Commit frequently and write clear, descriptive commit messages to facilitate the review process.
3. **Pull Request:** Push your branch to the repository and open a Pull Request (PR) against the `develop` branch. Use the following naming convention for your PR: `feature/{ticketNo} - Short descriptive title of the Jira task`.
4. **Review:** Address any feedback from reviewers and iterate as needed.
5. **CI/CD Checks:** Ensure that all continuous integration checks (e.g., GitHub Actions) pass successfully and the build status is green.
6. **Merge:** Once approved, your PR will be squashed and merged into `develop`. It will later be merged into a `release/{version}` branch for deployment.

Note: For a detailed visual guide on this branching model, please refer to the [Git Flow Cheatsheet](https://danielkummer.github.io/git-flow-cheatsheet).
