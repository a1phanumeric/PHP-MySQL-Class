Important Notice
===============

As of December 2014 I decided to upload the PHP MySQL Class I wrote a while back, and now use on a daily basis. It's PDO based (the `mysql_*` functions were due to be deprecated quite a while back now!).

The old version is still a part of this repo for now, and the readme is still available [here](class.MySQL.README.md).



PHP MySQL Class
===============

This is a simple to use MySQL class that easily bolts on to any existing PHP application, streamlining your MySQL interactions.


Setup
-----

Firstly, define four constants for the host, database name, username and password:

`define('DATABASE_NAME', 'my_database');`

`define('DATABASE_USER', 'username');`

`define('DATABASE_PASS', 'password');`

`define('DATABASE_HOST', 'localhost');`

Then, simply include this class into your project like so:

`include_once('/path/to/class.DBPDO.php');`

Then invoke the class:

`$DB = new DBPDO();`


Direct Queries
-----

To perform direct queries where you don't need to return any results (such as update, insert etc...), just do the following:

`$DB->execute("UPDATE customers SET email = 'newemail@domain.com' WHERE username = 'a1phanumeric'");`

That's the easiest way to use the class, but we should be utilising prepared statements now. This means no more escaping shizzle! To utilise prepared statements, just change the above code to the following:

`$DB->execute("UPDATE customers SET email = ? WHERE username = ?", array('newemail@domain.com', 'a1phanumeric'));`

The class will invoke PDO's prepared statements and put the email and username in their place respectively, as well as escape all values passed to it. **Note:** You don't need to put the speechmarks in on the query, the **?** is enough, and PDO will sort that out for you.


Fetching Rows
-----

To perform select queries with this class, the syntax is similar to the above, but we have two functions we can utilise, `fetch` and `fetchAll`.

`fetch` simply returns one row, useful for getting a user by their ID for example. This returns an associative array and looks like:

`$user = $DB->fetch("SELECT * FROM users WHERE id = ?", $id);`

Now `$user` will contain an array of the fields for the row where there query matches. Oh, what's that? We didn't pass an array as the second parameter we just passed a single variable? That's cool, the class will treat a single variable the same as if you passed `array($id)`. It's just a handy little time-saver.

`fetchAll` is used to fetch multiple rows, the parameters are similar, but the result returns an array of records:

`$counties = $DB->fetchAll("SELECT * FROM counties");`

The above will return a list of counties (in the UK) in my database like so:

```
[0] => Array
(
    [id] => 1
    [county] => London
)

[1] => Array
(
    [id] => 2
    [county] => Bedfordshire
)

[2] => Array
(
    [id] => 3
    [county] => Buckinghamshire
)
```

However, what if I want to loop over some raw data and check if the data matches the county name? To do that means either looping over these results every time, or shifting the key to the root dimension of the multi-dimensional array. However, if we pass a third variable, we can have that column as the key:

`$counties = $DB->fetchAll("SELECT * FROM counties", null, 'county');`

**Note:** I passed null as the second paramater as we're not passing any variables into the query to be escaped.

This will now return an array like the following:

```
[London] => Array
(
    [id] => 1
    [county] => London
)

[Bedfordshire] => Array
(
    [id] => 2
    [county] => Bedfordshire
)

[Buckinghamshire] => Array
(
    [id] => 3
    [county] => Buckinghamshire
)
```

So of course we could now do something like:

`if(isset($counties[$raw_data['county_name']])){ //Do something }`

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