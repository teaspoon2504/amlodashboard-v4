<?php
require_once 'includes/functions.php';
$res = db_fetch_one("SELECT DATABASE() as db");
echo $res['db'];
