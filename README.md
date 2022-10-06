# TYPO3 Bootcamp - an Environment to develop and run TYPO3 in Docker containers

### Requirements:
1.  Docker(4Mac) 17.09 or greater for build
1.  [Docker Compose](https://docs.docker.com/compose/install/) (included in Docker4Mac)
1.  composer
1.  A Traefik reverse proxy, e.g. [docker-frontend](https://github.com/t3easy/docker-frontend)
    or include `.docker/env.direct.yml` and set `WEB_PORT`.

### Start a new project
1.  `composer create-project t3easy/typo3-bootcamp awesome-project.tld`  
    (Or clone the project with git and checkout the desired branch)
1.  Change to awesome-project.tld / open it in you favorite IDE
1.  Rename `.env.dev` to `.env` and adjust it to your needs, see below and comments in the file for more information
    E.g. `VHOST=typo3.localhost`  
    If you use a `.localhost` vhost, you can access it with Chrome w/o a host entry.  
    See: <https://tools.ietf.org/html/rfc2606#page-2>
1.  Add your vhost as a hosts entry for 127.0.0.1 / the box you're running docker on
1.  Start the environment with `docker-compose up -d`
1.  Setup TYPO3  
    1.  With TYPO3 Console  
        ```bash
        docker-compose exec -u www-data typo3 vendor/bin/typo3cms install:setup
        ```
    1.  Or with the browser  
        ```bash
        docker-compose exec -u www-data typo3 touch /app/private/FIRST_INSTALL
        ```
        Open <http://typo3.localhost/typo3/install.php> and configure TYPO3
1.  Go to
    * <http://typo3.localhost/> for TYPO3 frontend
    * <http://typo3.localhost/typo3/> for TYPO3 backend
    * <http://adminer-typo3.localhost/> for Adminer
    * <http://mailhog-typo3.localhost> for Mailhog

## .env
In this file you define the environment you'd like to setup.
There are two examples, `.env.dev` to start an development environment and `.env.prod` as a template to build and deploy your project.

To check the result, run `docker-compose config`.  
To deploy to a swarm write the result to a file `docker-compose config > stack.yml` and use it `docker stack deploy --compose-file stack.yml myproject`

### COMPOSE_PROJECT_NAME
A unique project name. It must not contain dots.
E.g. project-typo3version like company-typo3v11
See <https://docs.docker.com/compose/reference/envvars/#compose_project_name> for more details

### COMPOSE_FILE
Add all necessary compose files separated with `:`, always start with the root `docker-compose.yml` to have a proper project name and relative paths.
The settings of the last config wins.
More at <https://docs.docker.com/compose/reference/envvars/#compose_file>

### VHOST
The FQDN of the TYPO3 project.
It gets prefixed for other services, e.g. if you set VHOST to `typo3.localhost`,
you can reach Adminer at `adminer-typo3.localhost` and Mailhog at `mailhog-typo3.localhost`.

### ADDITIONAL_VHOSTS
Adds additional names to the TYPO3 web service. The value must start with a comma (`,`).
Example `,2nd.domain.tld,3rd.domain.tld`

### FRONTEND_NETWORK
The name of the docker network that Traefik can use to connect to the web service.

### RESTART
Define the restart policy for all services.
Should be `always` for production and `no` for development.

### DB_IMAGE
The image of the db service, see

* <https://hub.docker.com/_/mariadb/>
* <https://hub.docker.com/_/mysql/>
* <https://forge.typo3.org/issues/82023#note-8>

Example `mariadb:10.9`

### MYSQL_ROOT_PASSWORD
Set the password of the root db user.
You should not set the password in the `.env` file for production setup.
Set it on CLI 
```bash
MYSQL_ROOT_PASSWORD=MyV3rySecretP4sswd docker-compose up -d
```
or set it in CI variables.

### DB_BIND_TO
Bind the db service to a specified ip and port.
Format `IP:Port`
Use `127.0.0.1:` to publish a dynamic port to localhost only.
Use `127.0.0.1:13306` to publish the port `13306`.
Use `13306` to publish `13306` to all available IP. ATTENTION! That allows access from anywhere!
The port is mapped to 3306, the MySQL/MariaDB port, inside the container.
See [Access the database during development via tcp](#access-the-database-during-development-via-tcp)

### REDIS and LDAP
Build the TYPO3 image with that PHP extensions.

### WEB_PORT
The port the web container expose. Only if you use `.docker/env.direct.yml`.

### TRAEFIK_ENTRYPOINT_HTTP and TRAEFIK_ENTRYPOINT_HTTPS
Names of the Traefik entrypoints

## Build
To build a productive environment use `docker-compose -f .docker/build.yml` from the root with an prepared `.env`
or by setting REDIS and LDAP in the environment of the builder.
If you build on GitLab CI, you can use `.docker/env.gitlab.yml` to tag your images.
See `.gitlab-ci.example.yml`.

## Deploy
See `.gitlab-ci.example.yml` for an example how to deploy to docker hosts with GitLab CI.
Consider to set `COMPOSE_PROJECT_NAME` at the deploy job, to be able to deploy the project multiple times to the same docker-host, e.g. testing, staging and live.
<https://docs.docker.com/compose/reference/envvars/#compose_project_name>

## Access the database during development via tcp
A dynamic port is mapped to the database service port 3306. To get this port run:
```bash
docker-compose ps db
```
You'll get something like:
```
      Name                    Command               State             Ports          
-------------------------------------------------------------------------------------
project_db_1       docker-entrypoint.sh --cha ...   Up      127.0.0.1:32770->3306/tcp
```
where `32770` is the port on the local docker host to connect to. 

## Run TYPO3 Console commands

To run a command inside the TYPO3 PHP Container use `docker-compose`:
E.g. flush the cache
```
docker-compose exec -u www-data typo3 typo3cms cache:flush
```

## Import/export the database

### Export database
```shell
docker-compose exec -T db sh -c 'exec mysqldump --opt --single-transaction -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' > dump.sql
```
### Import a sql dump
```shell
docker-compose exec -T db sh -c 'exec mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' < dump.sql
# Setup new/development extensions
docker-compose exec -u www-data typo3 typo3cms extension:setupactive --verbose
```

### Import directly from remote
```shell
ssh user@server 'TYPO3_CONTEXT="Production" /path/to/typo3cms database:export' | docker-compose exec -T db sh -c 'exec mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"'
# Setup new/development extensions
docker-compose exec -u www-data typo3 typo3cms extension:setupactive --verbose
```

### Why can't you use typo3-console to import/export the database to/from the docker environment
The TYPO3 PHP image does not include mysql or mysqldump binary which are required by typo3-console.

## Play with docker
Start a demo stack:
*  [TYPO3 12](https://labs.play-with-docker.com/?stack=https://raw.githubusercontent.com/t3easy/docker-typo3/12.x/.docker/pwd/stack.yml)
*  [TYPO3 11 LTS](https://labs.play-with-docker.com/?stack=https://raw.githubusercontent.com/t3easy/docker-typo3/11.x/.docker/pwd/stack.yml)
*  [TYPO3 10 LTS](https://labs.play-with-docker.com/?stack=https://raw.githubusercontent.com/t3easy/docker-typo3/10.x/.docker/pwd/stack.yml)
*  [TYPO3 9 LTS](https://labs.play-with-docker.com/?stack=https://raw.githubusercontent.com/t3easy/docker-typo3/9.x/.docker/pwd/stack.yml)
