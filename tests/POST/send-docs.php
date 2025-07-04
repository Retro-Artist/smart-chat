<?php

echo "\nTesting .txt files submission...\n";
$result1 = $mediaHelper->sendFromMediaDir($testNumber, "documents/hello.txt", "Test for text document");

echo "\nTesting .pdf files submission...\n";
$result2 = $mediaHelper->sendFromMediaDir($testNumber, "documents/report.pdf", "PDF report");

echo "\nTesting .pptx files submission...\n";
$result3 = $mediaHelper->sendFromMediaDir($testNumber, "documents/presentation.pptx", "PPTX presentation");