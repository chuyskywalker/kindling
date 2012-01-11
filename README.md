Kindling
========

Inspired by the content type simplicity of tumblr, but desireing my own self hosted solution, and unhappy with Wordpress/Drupal like overbearing solutions, I created Kindling.

Kindling is simple blogging system that has a minimilistic admin, no-b.s. template overrides, and is backed by Redis making it insanely fast.


Setup
-----

I *HIGHLY* reccommend that you install the Kindling in your webserve like this:

```
+ /var/www/site.com/
+-- config.php
+-- site/
|   +-- uploads/
|   +-- index.php
|   +-- css/
|   +-- js/
|   +-- img/
+-- code/
+-- theme/
```

The Kindling code should be placed inside the code folder. The "docroot" for your virtual host should be pointed at the "/var/www/site.com/site" folder. This ensures that the only PHP file in your docroot is index.php, while the remaining exposed files are all static files.

After that, you must also have the "site/uploads/" folder present so that you may upload images.
