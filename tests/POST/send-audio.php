<?php

echo "\nTesting voice messages audio submission...\n";
$result1 = $mediaHelper->sendWhatsAppAudioFromMediaDir($testNumber, "audio/message.mp3", "Test for voice message");

echo "\nTesting audio files submission...\n";
$result2 = $mediaHelper->sendAudioAsDocument($testNumber, "media/audio/message.mp3", "Test for audio file as document");