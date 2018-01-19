# A TYPO3 environment

For testing:
1.  Add `web.typo3` as a hosts entry for localhost / the box you're running docker on
1.  `docker-compose up -d`
1.  Setup TYPO3
    1.  With TYPO3 Console  
        ```bash
        docker-compose exec -u www-data typo3 /app/vendor/bin/typo3cms install:setup --non-interactive \
            --database-name="typo3" --database-user-name="typo3" --database-user-password="typo3" --database-host-name="db" --database-port="3306" --use-existing-database \
            --admin-user-name="admin" --admin-password="password" --site-setup-type="site" --site-name="TYPO3 Demo"
        ```
    1.  Or in the browser  
        `docker-compose exec -u www-data typo3 touch /app/web/FIRST_INSTALL`
        <http://web.typo3/typo3/>
1.  Go to <http://web.typo3/> for the frontend and <http://web.typo3/typo3/> for the TYPO3 backend
1.  Log in with user `admin` and password `password` (or your setup credentials)


## Build and run:

1.  `docker-compose -f docker-compose.yml -f docker-compose.build.yml build --no-cache`
1.  `docker-compose up -d`
