# Voxelmanip Forums
This is the source repository for the codebase that currently runs the Voxelmanip Forums.

It is a fork of [a fork](https://github.com/rasmusolle/Acmlmboard) of [Acmlmboard 2.5.3](https://github.com/acmlmboard/acmlmboard-2/tree/6968140893db53f66179884bf9697f91266efbb3) which in itself is a rather shoddily written forum software, so do not expect the topmost code quality, although it is certainly better than the upstreams.

The codebase is minimally lightweight (less than 4 KLOC), it is blazing fast on any server you throw at it, and it implements all the essential features you would want from a forum software without being overwhelming. Perfect for small forums.

## Requirements
1. Any computer hooked up to the internet (anything from a Raspberry Pi to a stolen Google serverrack should work)
1. An operating system (Linux recommended for sanity, Windows for insanity)
1. A web server capable of running PHP in some form (Apache/nginx/lighttpd/Synchronet)
1. PHP 8.0+ w/ the MySQL PDO extension
	1. `opcache` is not required but recommended for a smoother board experience.
	1. If you want to run this on older versions of PHP, you'll need to rewrite the match statements back into switch statements. At that point it should work on at least 7.2.
1. MariaDB (regular MySQL should work too)

## Installation
1. Set up a LAMP/LEMP/MAMP/WEMP/ZORP web server stack that meets the requirements.
1. Put this software somewhere in your site's directory structure. Putting it at the root is recommended but it should be able to be installed in a subdirectory too.
1. Import the database dump in the `sql` folder into a new database.
1. Copy the `conf/config.sample.php` file to `conf/config.php` and fill in your database credentials.

User profile pictures are stored in `userpic/`. Be sure to make this folder writeable by PHP, and also test to see that your server setup will not execute PHP code embedded within.

When the software has been set up, the first account to register will become the Root Administrator. Make sure not to share the link before you have done so.

## Backups
Please keep your forum data safe. This is an example script you could use to make backups on a remote server running the forum, saving an SQL dump locally.

```sh
current_date=$(date +'%Y-%m-%d_%H:%M:%S')
echo "Backing up database... Enter SSH key password then DB password."
ssh <USER>@<DOMAIN> "mysqldump -u <SQL USER> -p <SQL DATABASE>" > backup_${current_date}.sql
```
