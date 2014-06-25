<?php
require_once('../connections/parameters.php');
require_once('./functions.php');

echo curl('http://google.com');

echo '<hr />';

echo curl('http://dotabuff.com');