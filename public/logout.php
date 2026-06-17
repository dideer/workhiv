<?php
require_once __DIR__ . '/../src/Helpers/Session.php';

Session::destroy();

header('Location: index.html');
exit;
