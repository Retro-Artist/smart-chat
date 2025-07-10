We are in the process of developing an exceptionally simple interface application that utilizes EvolutionAPI. The implementation will employ PHP 8.4 on a Nginx server, structured within a clean and intuitive project architecture that emphasizes minimalism and user-friendliness. The core objective is to provide a chat interface that delivers the full spectrum of EvolutionAPI capabilities.

**Code Quality**:  
Our codebase will adhere to the highest standards of cleanliness and maintainability, with a pronounced focus on clear and comprehensible syntax. We will emphasize meaningful and descriptive naming conventions for functions and variables, favoring straightforward and explicit code over unnecessary complexity and over-engineering, avoid procedural code at all costs and look to always use existing methods instead of creating variations of one.

*Front-End**:
We will be using html, css altogether with alpine.js and tailwind to create beautiful and modern interfaces to make our application to look like a modernized version of whatsapp-web.

**Maintainability**  
Our foundational principle is that clarity and maintainability are paramount; every line of code should be easily understandable, extensible, and debuggable, developer-friendly.

**Resources**
- Before starting any task, open and thoroughly review the existing project files and the `database.sql` file to gain a solid understanding of the project's structure and data model.

**Project Status**

- API consumption is set up under the `src/` directory and can be tested using the files in `tests/`.
- The `public/` directory is currently not integrated with the API. Its sole purpose for now is to verify database connectivity and environment variable loading. Check `config.php` and `load_env.php` to see how `index.php` interfaces with them. This setup also ties into `docker-compose.yml` and the `.env` file.


# Current Project Structure:

smart-chat/
├── config
│   ├── config.php               # Environment config and declaration
│   └── load_env.php             # load .env script
├── database
│   ├── database.sql             # database (current database file)
│   └── migrate.php              # migration script
├── logs
├── media                        # EvolutionAPI media resources for sending in whatsapp messages
│   ├── audio
│   ├── documents
│   ├── images
│   └── videos
├── public
│   ├── assets
│   │   ├── css
│   │   │   └── style.css
│   │   └── js
│   │       └── theme-manager.js # javascript code for dark-light mode
│   ├── favicon.ico
│   └── index.php
├── src
│   ├── Api
│   │   ├── EvolutionAPI         # Api used for Whatsapp requests
│   │   │   ├── ChatController.php
│   │   │   ├── EvolutionAPI.php
│   │   │   ├── Instances.php
│   │   │   ├── MediaHandler.php
│   │   │   ├── MessageImporter.php
│   │   │   ├── ProfileSettings.php
│   │   │   ├── SendMessage.php
│   │   │   ├── Settings.php
│   │   │   └── WebhookHandler.php
│   │   ├── Models
│   │   │   ├── Agent.php
│   │   │   ├── Run.php
│   │   │   ├── Thread.php
│   │   │   └── Tool.php
│   │   ├── OpenAI
│   │   │   ├── AgentsAPI.php
│   │   │   ├── SystemAPI.php
│   │   │   ├── ThreadsAPI.php
│   │   │   └── ToolsAPI.php
│   │   └── Tools
│   │       ├── Math.php
│   │       ├── ReadPDF.php
│   │       ├── Search.php
│   │       └── Weather.php
│   ├── Core
│   │   ├── Database.php
│   │   ├── Helpers.php
│   │   ├── Logger.php
│   │   ├── Router.php
│   │   ├── Security.php
│   │   └── Status.php
│   └── Web
│       ├── Controllers
│       │   ├── AgentController.php
│       │   ├── AuthController.php
│       │   ├── ChatController.php
│       │   ├── DashboardController.php
│       │   ├── HomeController.php
│       │   └── InstanceController.php
│       ├── Models
│       │   ├── ChatSession.php
│       │   ├── Project.php
│       │   ├── User.php
│       │   └── WhatsappInstance.php
│       └── Views
│           ├── agents.php
│           ├── chat.php
│           ├── dashboard.php
│           ├── error.php
│           ├── home.php
│           ├── instances.php
│           ├── layout.php
│           ├── login.php
│           ├── register.php
│           └── status.php
├── temp                         # temporary processed files by EvolutionAPI
├── tests                        # tests for evolution API
│   ├── POST                     # examples for sending messages with media
│   │   ├── send-audio.php
│   │   ├── send-docs.php
│   │   ├── send-images.php
│   │   ├── send-plain-text.php
│   │   └── send-video.php
│   ├── debug-api-endpoint.php
│   ├── debug-current-instance.php
│   ├── debug-qr-web.php
│   ├── instance-management.php  # test creating and managing instances.
│   ├── list-instances-simple.php
│   └── send-message.php         # test sending whatsapp messages to test number.
├── composer.json
├── docker-compose.yml
├── Dockerfile
├── .env #env file that's parsed in config.php
├── env.example
├── nginx.conf
└── README.md
