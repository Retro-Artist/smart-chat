<?php

echo "\nTesting plain text submission...\n";
$result = $api->sendSimpleMessage($testNumber, "hello", 30);