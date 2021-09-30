$dsn      = 'mysql:dbname=' . DATABASE_NAME . ';host=' . DATABASE_HOST.';charset=utf8';
$user     = DATABASE_USER;
$password = DATABASE_PASS;

You this line to make code simple :

include('connection.php');

This will help in reading and understanding code easily
