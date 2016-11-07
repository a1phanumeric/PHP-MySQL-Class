<?php
include_once dirname(__FILE__).'/class.MySQL.php';

$DB = new MySQL('test', 'root', 'myzYQswlLX', '127.0.0.1');

/*
$insertData = array('name'=>'kaysen', 'age'=>27, 'created'=>time());
$status = $DB->insert('kaysen_tab', $insertData);
var_dump($status);
 */

/*
$status = $DB->update('kaysen_tab', array('age'=>666), array('id'=>1));
var_dump($status);
 */

$status = $DB->delete('kaysen_tab', array('id'=>1));
var_dump($status);
