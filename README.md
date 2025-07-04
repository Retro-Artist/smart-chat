==============
USAGE EXAMPLES
==============

# Example 1: Send different media types

#$mediaHelper->sendFromFile("number", "videos/demo.mp4", "Demo video");
#$mediaHelper->sendFromFile("number", "audio/message.mp3", "Audio message");

===========================
DIRECTORY STRUCTURE EXAMPLE
===========================

Our project structure look like this:

# Evolution API Tests Project Structure

```
smart-chat/
├── app
│   ├── config.php
│   └── load_env.php
├── database
│   ├── database.sql
│   └── migrate.php
├── logs
├── media
│   ├── audio
│   │   ├── badexample.mp3
│   │   └── message.mp3
│   ├── documents
│   │   ├── hello.txt
│   │   ├── presentation.pptx
│   │   └── report.pdf
│   ├── images
│   │   ├── logo.webp
│   │   ├── photo1.jpg
│   │   └── photo2.png
│   └── videos
│       └── demo.mp4
├── public
│   ├── assets
│   ├── favicon.ico
│   └── index.php
├── src
│   ├── AudioProcessor.php
│   ├── EvolutionAPI.php
│   └── MediaHelper.php
├── temp
├── tests
│   ├── test_audio.php
│   ├── test_docs.php
│   ├── test_images.php
│   ├── test_message.php
│   ├── test_video.php
│   └── tester.php
├── composer.json
├── docker-compose.yml
├── Dockerfile
├── nginx.conf
├── oldtest.php
└── README.md
```


=================
QUICK SETUP GUIDE
=================

For testing, you can:
- Download a sample image: wget https://picsum.photos/400/300 -O media/test.jpg
- Or create a simple text file and rename it to test.txt