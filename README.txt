=== Plugin Name ===
Contributors: xjlin0
Donate link: https://xjlin0.github.io/
Tags: audio, player
Requires at least: 3.0.1
Tested up to: 6.5.3
Stable tag: 6.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Here is a short description of the plugin.  This should be no more than 150 characters.  No markup here.

== Description ==

This is the long description.  No limit, and you can use Markdown (as well as in the following sections).

For backwards compatibility, if this section is missing, the full length of the short description will be used, and
Markdown parsed.

A few notes about the sections above:

*   "Contributors" is a comma separated list of wp.org/wp-plugins.org usernames
*   "Tags" is a comma separated list of tags that apply to the plugin
*   "Requires at least" is the lowest version that the plugin will work on
*   "Tested up to" is the highest version that you've *successfully used to test the plugin*. Note that it might work on
higher versions... this is just the highest one you've verified.
*   Stable tag should indicate the Subversion "tag" of the latest stable version, or "trunk," if you use `/trunk/` for
stable.

    Note that the `readme.txt` of the stable tag is the one that is considered the defining one for the plugin, so
if the `/trunk/readme.txt` file says that the stable tag is `4.3`, then it is `/tags/4.3/readme.txt` that'll be used
for displaying information about the plugin.  In this situation, the only thing considered from the trunk `readme.txt`
is the stable tag pointer.  Thus, if you develop in trunk, you can update the trunk `readme.txt` to reflect changes in
your in-development version, without having that information incorrectly disclosed about the current stable version
that lacks those changes -- as long as the trunk's `readme.txt` points to the correct stable tag.

    If no stable tag is provided, it is assumed that trunk is stable, but you should specify "trunk" if that's where
you put the stable version, in order to eliminate any doubt.

== Installation ==

1. Download the plugin as a [ZIP file](https://github.com/xjlin0/audio-list/archive/master.zip) from GitHub.
2. Install and Activate the plugin through the 'Plugins' and Upload menu in WordPress admin dashboard.
3. Optionally, restore the Mysql database table `wp_audio_list`.

== Frequently Asked Questions ==

= A question that someone might have =

An answer to that question.

= What about foo bar? =

Answer to foo bar dilemma.

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==

= 1.0 =
* Initial implantation.


== Upgrade Notice ==

= 1.0 =
There's database table name change from the previous draft code, please change SQL INSERT table name accordingly for database restore.

== Arbitrary section ==

You may provide arbitrary sections, in the same format as the ones above.  This may be of use for extremely complicated
plugins where more information needs to be conveyed that doesn't fit into the categories of "description" or
"installation."  Arbitrary sections will be shown below the built-in sections outlined above.

== local development using Wordpress in docker-compose and mysql in host ==

While [Wazoo's docker-compose](https://youtu.be/gEceSAJI_3s) is great with everything in docker-compose, here is yet another docker-compose.yml if you'd like to develop Wordpress plug-in in docker-compose with mysql in host.  Just `docker-compose up` and you will see `wp-content` folder shows up in the folder of docker-compose.yml.

```yaml
version: "3.8"

services:
  wordpress:
    extra_hosts:
      - host.docker.internal:host-gateway
    image: wordpress:6.5.3-apache
    restart: unless-stopped
    ports:
      - "8888:80"
    environment:
      # WORDPRESS_DEBUG: 1
      WORDPRESS_DB_HOST: host.docker.internal:3306
      WORDPRESS_DB_USER: host_mysql_user_name
      WORDPRESS_DB_PASSWORD: 'host mysql password or empty string with quotes'
      WORDPRESS_DB_NAME: host_mysql_database_name
    volumes:
      - ./wp-content:/var/www/html
```

Please also add the following lines in html/.htaccess to overcome PHP upload limit:
```
php_value upload_max_filesize 500M
php_value post_max_size 500M
```
Then after `docker-compose up`, please browse http://0.0.0.0:8888 