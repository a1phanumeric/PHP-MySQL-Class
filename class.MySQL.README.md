PHP MySQL Class
===============

This is the README for the class.MySQL.php class that I no longer support as the `mysql_*` functions are deprecated.

This is a simple to use MySQL class that easily bolts on to any existing PHP application, streamlining your MySQL interactions.


Latest Changes
--------------

I have refactored the entire class, and improved the code somewhat. This means that some things now work differently to the original version.


Setup
-----

Simply include this class into your project like so:

`include_once('/path/to/class.MySQL.php');`

Then invoke the class in your project using the class constructor (which now sets the db credentials):

`$oMySQL = new MySQL(MYSQL_NAME, MYSQL_USER, MYSQL_PASS, [MYSQL_HOST]);`

`MYSQL_NAME` The name of your database

`MYSQL_USER` Your username for the server / database

`MYSQL_PASS` Your password for the server / database

`MYSQL_HOST` The hostname of the MySQL server (*optional*, defaults to 'localhost')


Usage
-----

To use this class, you'd first init the object like so (using example credentials):

`$oMySQL = new MySQL('my_database','username','password');`

Provided you see no errors, you are now connected and can execute full MySQL queries using:

`$oMySQL->ExecuteSQL($query);`

`ExecuteSQL()` will return an array of results, or a true (if an UPDATE or DELETE).

There are other functions such as `Insert()`, `Delete()` and `Select()` which may or may not help with your queries to the database.

Example
-------

To show you how easy this class is to use, consider you have a table called *admin*, which contains the following:

```
+----+--------------+
| id | username     |
+----+--------------+
|  1 | superuser    |
|  2 | a1phanumeric |
+----+--------------+
```

To add a user, you'd simply use:

```
$newUser = array('username' => 'Thrackhamator');
$oMySQL->Insert($newUser, 'admin');
```

And voila:

```
+----+---------------+
| id | username      |
+----+---------------+
|  1 | superuser     |
|  2 | a1phanumeric  |
|  3 | Thrackhamator |
+----+---------------+
```

To get the results into a usable array, just use `$oMySQL->Select('admin')` ...for example, doing the following:

`print_r($oMySQL->Select('admin'));`

will yield:

```
Array
(
    [0] => Array
        (
            [id] => 1
            [username] => superuser
        )

    [1] => Array
        (
            [id] => 2
            [username] => a1phanumeric
        )

    [2] => Array
        (
            [id] => 3
            [username] => Thrackhamator
        )

)
```

### License

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.