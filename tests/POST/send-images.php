<?php

echo "\nTesting Jpg images submission...\n";
$result1 = $mediaHelper->sendFromMediaDir($testNumber, "images/photo1.jpg", "Test for jpg image from media directory");

echo "\nTesting Png images submission...\n";
$result2 = $mediaHelper->sendFromMediaDir($testNumber, "images/photo2.png", "Test for png image from media directory");

echo "\nTesting Webp images submission...\n";
$result3 = $mediaHelper->sendFromMediaDir($testNumber, "images/logo.webp", "Test for webp image from media directory");

echo "\nTesting URL download submission...\n";
$result4 = $mediaHelper->sendFromUrl($testNumber, "https://imgur.com/gallery/commissioned-pet-portrait-artwork-of-jasper-ETpv51H", "Test for random image from imgur");

echo "\nTesting text image generation submission...\n";
$result5 = $mediaHelper->createTextImage($testNumber, "Hello from PHP!", "PHP Generated text image");