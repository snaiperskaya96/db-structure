This script fetchs the database structure and builds HTML tables based on it.

In order to make it work, rename /config/databases.php.template to 
databases.php and fill the $databases array with the informations of the 
databases you want to print. 

Also you have to use composer in order to download the dependencies, so
inside /inc run composer install

That's it!