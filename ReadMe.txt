Records class

License GPLv3

This expects to see the following constants

DB_USERNAME //User name for the database
DB_PASSWORD //Password for the database
DB_DATABASE //Name of the database

You can define these constants anywhere in your code but I would suggest a config file. If these constants are not found then it will instead look for the file usedat.php. This file exists only for compatibility with some of my old code, but it's also useful if you are doing something simple and don't want to define a config file. Rather than constants though it has the rather self explanitory variables

$username = "";
$password = "";
$database = "";

To use this code you instantiate it with the name of your table. If we had a table of users we might create the object as follows

$users = new records("users");

When instantiated the object is blank. You can get users from the database via the following command

$users->get_records($column_name, $column_value);

To insert a new user the code would look like

$users->$username;
$users->$password;
$user_id = $users->insert(); //last insert id is returned on success, false on failure. 

Currently this code uses the mysqli API. If you wish to use something else, such as if you're using MSSQL then you would create the code in the folder "DBD" with the prefix "dbd_". You would also have to define the constant "DB_DRIVER" before records is first called so you would have define("DB_DRIVER", "mssql") and the file would be "DBD/dbd_mssql.php".