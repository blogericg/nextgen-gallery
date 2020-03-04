# Using docker for builds

Docker and Docker Compose is required for using Docker to perform builds

You'll need a token from npmjs.org, which you can get from the npmjs.org website or by running:

```
npm token create --read-only
```

When ready, you first run:

```
docker-compose build --build-arg composerToken=854a616e2d03583fb7daf24b2c4e816646255686d1516c96bbbb0dca976b --build-arg npmToken=[paste NPM auth token here]
```

This will build the initial image used by the `reactr-build` container.

You can then bring the container up by running:

```
docker-compose up -d
```

This will load the image, you can see the status by running `docker-compose ps`

Once the container is running, you can then use Docker Composer to perform builds:

```
docker-compose exec reactr-build gulp build
docker-compose exec reactr-build gulp build -z 1.0.3
```

To deploy a build, run

```
docker-compose exec reactr-build -e DEPLOY_PATH=/Users/foobar/wordpress/wp-content gulp build -d
```

You can also set environment variables as a file called `build.env` for example:

```
DEPLOY_PATH=/Users/foobar/wordpress/wp-content
FTP_HOST=1.2.3.4
FTP_PATH=public_html/wordpress/wp-content/plugins/nextgen-gallery
FTP_USER=root
FTP_PASS=root
```

You would then omit the -e argument to docker and specify --env-file instead:

If you wanted to run composer to update packages, you’d run docker-compose exec reactr-build composer update

```
docker-compose exec reactr-build --env-file build.env gulp build -d
```

Once you are done, you can leave the container running, or bring it down by running:

```
docker-compose down
```

if you ever want to clean up any unused images in docker, you can run:

```
docker image prune -a
```
