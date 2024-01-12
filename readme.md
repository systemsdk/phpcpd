# PHP Copy/Paste Detector
`phpcpd` is a Copy/Paste Detector (CPD) for PHP code.

[Source code](https://github.com/systemsdk/phpcpd.git)

## Requirements
* PHP version 8.1 or later

## Installation
Download the latest version on our web-site [download](https://www.systemsdk.com/userfiles/file/phpcpd-latest.phar) and put phar archive into your project.

Note: This tool is distributed as a [PHP Archive (PHAR)](https://php.net/phar).

## Usage example
```
$ php phpcpd.phar --fuzzy src tests
7.0.1
Found 1 code clones with 19 duplicated lines in 1 files:

  - /var/www/html/src/User/Transport/Controller/Api/v1/User/DetachUserGroupController.php:99-118 (19 lines)
    /var/www/html/src/User/Transport/Controller/Api/v1/User/DetachUserGroupController.php:106-125

0.06% duplicated lines out of 30223 total lines of code.
Average code clone size is 19 lines, the largest code clone has 19 lines

Time: 00:00.197, Memory: 8.00 MB
```

## Requirements for support team
* Docker version 18.06 or later
* Docker compose version 1.22 or later
* An editor or IDE

Note: OS recommendation - Linux Ubuntu based.

## Components for support team
1. PHP 8.3 fpm
2. Composer 2
3. Phive 0.15
4. Phing 2.17

## Setting up Docker and docker-compose for support team
1.For installing Docker please follow steps mentioned on page [install on Ubuntu linux](https://docs.docker.com/install/linux/docker-ce/ubuntu/) or [install on Mac/Windows](https://docs.docker.com/engine/install/).

2.For installing docker-compose as `Linux Standalone binary` please follow steps on the page [install compose](https://docs.docker.com/compose/install/standalone/) if you are using Linux OS.

Note 1: Please run next cmd after above step 2 if you are using Linux OS: `sudo usermod -aG docker $USER`

Note 2: If you are using Docker Desktop for MacOS 12.2 or later - please enable [virtiofs](https://www.docker.com/blog/speed-boost-achievement-unlocked-on-docker-desktop-4-6-for-mac/) for performance (enabled by default since Docker Desktop v4.22).

## Setting up DEV environment for support team
1.Clone this repository from GitHub.

2.Edit and set `XDEBUG_CONFIG=` inside `.env` file (optional, by default `XDEBUG_CONFIG=main`).

3.Configure `/docker/dev/xdebug-main.ini` (Linux/Windows) or `/docker/dev/xdebug-osx.ini` (MacOS) (optional).

4.Build, start and install the docker images from your terminal:
```bash
make build-dev
make start
make setup
```

## Getting shell to container for support team
After application will start (`make start`) and in order to get shell access inside php container you can run following command:
```bash
make ssh
```
Note: Please use `exit` command in order to return from container's shell to local shell.

## Building container
In case you edited Dockerfile or other environment configuration you'll need to build container again using next commands:
```bash
make down
make build-dev
make start
```

## Start and stop environment containers for support team
Please use next make commands in order to start and stop environment:
```bash
make start
make stop
```

## Stop and remove environment containers, networks for support team
Please use next make commands in order to stop and remove environment containers, networks:
```bash
make down
```

## Additional main command available for support team
```bash
make build-dev

make start

make stop

make down

make restart

make ssh
make ssh-root

make setup

make update-tools

make info
make help

make phar
make signed-phar

make phpunit

make logs

etc....
```
Notes: Please see more commands in Makefile

## Guidelines for support team
* [Phive](https://github.com/phar-io/phive)
* [Phing](https://www.phing.info)

## Working on the project for support team
1. For new feature development, fork `develop` branch into a new branch with one of the two patterns:
    * `feature/{ticketNo}`
2. Commit often, and write descriptive commit messages, so its easier to follow steps taken when reviewing.
3. Push this branch to the repo and create pull request into `develop` to get feedback, with the format `feature/{ticketNo}` - "Short descriptive title of Jira task".
4. Iterate as needed.
5. Make sure that "All checks have passed" on CircleCI(or another one in case you are not using CircleCI) and status is green.
6. When PR is approved, it will be squashed & merged, into `develop` and later merged into `release/{No}` for deployment.

Note: You can find git flow detail example [here](https://danielkummer.github.io/git-flow-cheatsheet).
