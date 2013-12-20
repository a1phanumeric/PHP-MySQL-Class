PHP MySQL Class
===============

This is a simple to use MySQL class that easily bolts on to any existing PHP application, streamlining your MySQL interactions.


Latest Changes
--------------

Instead of accessing the class as an instance, now the class is designed for PDO. This will hopefully spark insight to developers to continue updating since PHP has brought better methods in the newer updates.

Usuage
-------

To use this class, you'd first create your class that extends MySQL and init the object like so (using example credentials):

```php
include_once 'class.MySQL.php';

class NewNews extends MySQL {
	function __construct() {
    	parent::__construct($dbname, $dbusername, $dbpassword, $dbhost);
    }
}

```

Add a function that will do a procedure requiring MySQL:

```php
class NewNews extends MySQL {
	...
    
    function getEntriesExampleOne($limit='') {
    	$result = parent::Select('news', NULL, 'post_date DESC', $limit, NULL, NULL);
    	echo parent::getLastQuery();
    	return $result;
	}
    
    function getEntriesExampleTwo($query) {
    	$result = parent::ExecuteSQL($query);
        // print_r($result);
        return $result;
    }
}
```

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
parent::Insert($newUser, 'admin');
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

To get the results into a usable array, just use `parent::Select('admin')` ...for example, doing the following:

`print_r(parent::Select('admin'));`

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
