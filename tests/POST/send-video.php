<?php

echo "\nTesting video files submission...\n";
$result = $mediaHelper->sendFromMediaDir($testNumber, "videos/demo.mp4", "Demo video");