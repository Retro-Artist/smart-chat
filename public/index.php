<?php

/**
 * Simple PHP Initialization
 * A minimal Docker environment for PHP 8.4.7 development
 * Now properly integrated with app/config.php and Status class
 */

// Load the configuration system and Status class
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../src/Core/Status.php';
$config = require __DIR__ . '/../app/config.php';

// Run all status checks using the Status class
$configStatus = Status::checkConfigurationStatus($config);
$openaiTest = Status::testOpenAIAPI($config);
$evolutionTest = Status::testEvolutionAPI($config);
$db = Status::setupDatabaseConnection($config);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($config['app']['name'] ?? 'Simple PHP Initialization') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-php-50 text-php-800">
    <div class="max-w-5xl mx-auto p-6">
        <header class="pb-4 border-b border-php-200 mb-6">
            <h1 class="text-3xl font-bold text-php-900"><?= htmlspecialchars($config['app']['name'] ?? 'Simple PHP Initialization') ?></h1>
            <p class="text-lg text-php-600">Your PHP <?= phpversion() ?> environment is ready! <span class="text-php-500">(v<?= htmlspecialchars($config['app']['version'] ?? '1.0.0') ?>)</span></p>
            <?php if ($config['app']['debug'] ?? false): ?>
                <div class="mt-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    <i class="fas fa-bug mr-1"></i> Debug Mode Enabled
                </div>
            <?php endif; ?>
        </header>

        <!-- Configuration Status -->
        <div class="bg-white rounded-md shadow-sm border border-php-200 mb-6">
            <div class="bg-gradient-to-r from-php-500 to-php-600 text-white p-3 rounded-t-md">
                <i class="fas fa-cog mr-2"></i> Configuration Status
            </div>
            <div class="p-4">
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <h4 class="font-medium text-php-800 mb-2">Environment Loading</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center space-x-2">
                                <?php if ($configStatus['env_loaded']): ?>
                                    <i class="fas fa-check-circle text-green-500"></i>
                                    <span class="text-green-700">.env file found and loaded</span>
                                <?php else: ?>
                                    <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                                    <span class="text-yellow-700">.env file not found (using defaults)</span>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center space-x-2">
                                <?php if ($configStatus['config_accessible']): ?>
                                    <i class="fas fa-check-circle text-green-500"></i>
                                    <span class="text-green-700">Configuration accessible</span>
                                <?php else: ?>
                                    <i class="fas fa-times-circle text-red-500"></i>
                                    <span class="text-red-700">Configuration loading failed</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-medium text-php-800 mb-2">Configuration Sections</h4>
                        <div class="space-y-2 text-sm">
                            <?php foreach ($configStatus['required_keys'] as $key => $isValid): ?>
                                <div class="flex items-center space-x-2">
                                    <?php if ($isValid): ?>
                                        <i class="fas fa-check-circle text-green-500"></i>
                                        <span class="text-green-700"><?= ucfirst($key) ?> config loaded & valid</span>
                                    <?php else: ?>
                                        <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                                        <span class="text-yellow-700"><?= ucfirst($key) ?> config missing/empty</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-6 mb-6">
            <!-- Environment Info -->
            <div class="bg-white rounded-md shadow-sm border border-php-200">
                <div class="bg-gradient-to-r from-php-500 to-php-600 text-white p-3 rounded-t-md">
                    <i class="fas fa-circle-info mr-2"></i> Environment Information
                </div>
                <ul class="divide-y divide-php-100 p-0">
                    <li class="p-3 text-php-700">PHP Version: <span class="text-php-900 font-medium"><?= phpversion() ?></span></li>
                    <li class="p-3 text-php-700">Web Server: <span class="text-php-900 font-medium"><?= $_SERVER['SERVER_SOFTWARE'] ?></span></li>
                    <li class="p-3 text-php-700">Document Root: <span class="text-php-900 font-medium"><?= $_SERVER['DOCUMENT_ROOT'] ?></span></li>
                    <li class="p-3 text-php-700">Server Protocol: <span class="text-php-900 font-medium"><?= $_SERVER['SERVER_PROTOCOL'] ?></span></li>
                    <li class="p-3 text-php-700">Environment: <span class="text-php-900 font-medium"><?= $config['app']['debug'] ? 'Development' : 'Production' ?></span></li>
                </ul>
            </div>

            <!-- Database Connection -->
            <div class="bg-white rounded-md shadow-sm border border-php-200">
                <div class="bg-gradient-to-r from-php-500 to-php-600 text-white p-3 rounded-t-md">
                    <i class="fas fa-database mr-2"></i> Database Connection
                </div>
                <div class="p-4">
                    <?php if ($db['connected']): ?>
                        <div class="bg-green-50 border border-green-200 text-green-700 p-3 rounded-md mb-3">
                            <i class="fas fa-check-circle mr-2"></i>Database connection successful!
                        </div>
                        <div class="space-y-1 text-sm text-php-700">
                            <p>Host: <strong class="text-php-900"><?= htmlspecialchars($config['database']['host']) ?>:<?= $config['database']['port'] ?></strong></p>
                            <p>Database: <strong class="text-php-900"><?= htmlspecialchars($db['dbname']) ?></strong></p>
                            <?php if ($db['mysqlVersion']): ?>
                                <p>MySQL Version: <strong class="text-php-900"><?= htmlspecialchars($db['mysqlVersion']) ?></strong></p>
                            <?php endif; ?>
                        </div>
                    <?php elseif (isset($db['error']) && strpos($db['error'], 'does not exist yet') !== false): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded mb-3">
                           <?= htmlspecialchars($db['error']) ?>
                        </div>
                        <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-md">
                            <h4 class="font-medium mb-3 text-red-800 flex items-center">
                                <i class="fas fa-exclamation-triangle mr-2 text-red-600 opacity-75"></i>
                                Database Needs Initialization
                            </h4>
                            <p class="text-red-700 mb-4">Choose one of the following options to initialize your database:</p>

                            <div class="space-y-3">
                                <div class="bg-white border border-red-100 rounded-md overflow-hidden">
                                    <div class="bg-gradient-to-r from-red-100 to-red-50 p-3 border-b border-red-100">
                                        <div class="flex items-center">
                                            <div class="w-6 h-6 bg-red-200 text-red-700 rounded-full flex items-center justify-center text-sm font-medium mr-3">1</div>
                                            <span class="font-medium text-red-800">Migration Script</span>
                                        </div>
                                    </div>
                                    <div class="p-4 space-y-3">
                                        <div class="flex items-start space-x-3">
                                            <span class="text-slate-500 text-sm font-medium min-w-[4rem]">Docker:</span>
                                            <code class="bg-slate-50 text-slate-700 px-3 py-1.5 rounded-md text-sm flex-1 block">
                                                docker-compose exec app php database/migrate.php
                                            </code>
                                        </div>
                                        <div class="flex items-start space-x-3">
                                            <span class="text-slate-500 text-sm font-medium min-w-[4rem]">Standard:</span>
                                            <code class="bg-slate-50 text-slate-700 px-3 py-1.5 rounded-md text-sm flex-1 block">
                                                php database/migrate.php
                                            </code>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-white border border-red-100 rounded-md overflow-hidden">
                                    <div class="bg-gradient-to-r from-red-100 to-red-50 p-3 border-b border-red-100">
                                        <div class="flex items-center">
                                            <div class="w-6 h-6 bg-red-200 text-red-700 rounded-full flex items-center justify-center text-sm font-medium mr-3">2</div>
                                            <span class="font-medium text-red-800">Manual Import</span>
                                        </div>
                                    </div>
                                    <div class="p-4">
                                        <div class="space-y-2">
                                            <div class="flex items-center space-x-3">
                                                <span class="w-1.5 h-1.5 bg-red-300 rounded-full"></span>
                                                <span class="text-slate-600 text-sm">Access phpMyAdmin at <a href="http://localhost:8081" class="text-red-600 hover:text-red-700 underline decoration-red-200 hover:decoration-red-300">localhost:8081</a></span>
                                            </div>
                                            <div class="flex items-center space-x-3">
                                                <span class="w-1.5 h-1.5 bg-red-300 rounded-full"></span>
                                                <span class="text-slate-600 text-sm">Create database <strong class="text-slate-700"><?= htmlspecialchars($db['dbname']) ?></strong></span>
                                            </div>
                                            <div class="flex items-center space-x-3">
                                                <span class="w-1.5 h-1.5 bg-red-300 rounded-full"></span>
                                                <span class="text-slate-600 text-sm">Import <code class="bg-slate-50 text-slate-700 px-2 py-0.5 rounded text-xs">database/database.sql</code></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 p-3 rounded-md mb-3">
                            <i class="fas fa-exclamation-triangle mr-2"></i><?= htmlspecialchars($db['error']) ?>
                        </div>
                        <p class="mt-2 text-php-700">Check your database connection settings in the .env file</p>
                        <div class="mt-3 text-xs text-php-600 bg-php-50 p-2 rounded border">
                            <strong>Current config:</strong><br>
                            Host: <?= htmlspecialchars($config['database']['host'] ?: 'not set') ?><br>
                            Port: <?= ($config['database']['port'] && $config['database']['port'] > 0) ? htmlspecialchars($config['database']['port']) : 'not set' ?><br>
                            Database: <?= htmlspecialchars($config['database']['database'] ?: 'not set') ?><br>
                            Username: <?= htmlspecialchars($config['database']['username'] ?: 'not set') ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- API Configuration Status -->
        <div class="bg-white rounded-md shadow-sm border border-php-200 mb-6">
            <div class="bg-gradient-to-r from-php-500 to-php-600 text-white p-3 rounded-t-md">
                <i class="fas fa-plug mr-2"></i> API Configuration
            </div>
            <div class="p-4">
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-medium text-php-800 mb-3 flex items-center">
                            <i class="fab fa-whatsapp mr-2 text-green-500"></i>Evolution API
                        </h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center space-x-2">
                                <?php if ($evolutionTest['status'] === 'success'): ?>
                                    <i class="fas fa-check-circle text-green-500"></i>
                                    <span class="text-green-700">API Working</span>
                                <?php elseif ($evolutionTest['status'] === 'warning'): ?>
                                    <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                                    <span class="text-yellow-700">API Warning</span>
                                <?php elseif ($evolutionTest['status'] === 'error'): ?>
                                    <i class="fas fa-times-circle text-red-500"></i>
                                    <span class="text-red-700">API Error</span>
                                <?php else: ?>
                                    <i class="fas fa-power-off text-gray-500"></i>
                                    <span class="text-gray-600">Disabled</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-php-600 text-xs">
                                Status: <?= htmlspecialchars($evolutionTest['message']) ?>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-medium text-php-800 mb-3 flex items-center">
                            <i class="fas fa-robot mr-2 text-blue-500"></i>OpenAI API
                        </h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center space-x-2">
                                <?php if ($openaiTest['status'] === 'success'): ?>
                                    <i class="fas fa-check-circle text-green-500"></i>
                                    <span class="text-green-700">API Working</span>
                                <?php elseif ($openaiTest['status'] === 'warning'): ?>
                                    <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                                    <span class="text-yellow-700">API Warning</span>
                                <?php elseif ($openaiTest['status'] === 'error'): ?>
                                    <i class="fas fa-times-circle text-red-500"></i>
                                    <span class="text-red-700">API Error</span>
                                <?php else: ?>
                                    <i class="fas fa-power-off text-gray-500"></i>
                                    <span class="text-gray-600">Disabled</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-php-600 text-xs">
                                Status: <?= htmlspecialchars($openaiTest['message']) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PHP Extensions -->
        <div class="bg-white rounded-md shadow-sm border border-php-200 mb-6 overflow-hidden">
            <div class="bg-gradient-to-r from-php-500 to-php-600 text-white p-3 rounded-t-md">
                <i class="fas fa-puzzle-piece mr-2"></i> Available PHP Extensions
            </div>
            <div class="p-4">
                <div class="grid md:grid-cols-3 gap-4">
                    <?php
                    $extensions = get_loaded_extensions();
                    sort($extensions);
                    $chunks = array_chunk($extensions, ceil(count($extensions) / 3));

                    foreach ($chunks as $chunk) {
                        echo '<ul class="divide-y divide-php-100 text-sm">';
                        foreach ($chunk as $ext) {
                            echo '<li class="py-1.5 text-php-700">' . htmlspecialchars($ext) . '</li>';
                        }
                        echo '</ul>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Sample Notes Display -->
        <?php if ($db['tableExists'] && count($db['notes']) > 0): ?>
            <div class="bg-white rounded-md shadow-sm border border-php-200 mb-6">
                <div class="bg-gradient-to-r from-php-500 to-php-600 text-white p-3 rounded-t-md">
                    <i class="fas fa-sticky-note mr-2"></i> Sample Notes from Database
                </div>
                <div class="p-4 space-y-3">
                    <?php foreach ($db['notes'] as $note): ?>
                        <div class="border border-php-200 rounded-md">
                            <div class="bg-php-200 p-2 border-b border-php-200 font-medium text-php-800">
                                <?= htmlspecialchars($note['title']) ?>
                            </div>
                            <div class="p-3">
                                <p class="text-php-700"><?= htmlspecialchars($note['content']) ?></p>
                                <div class="text-php-500 text-xs mt-2">Created: <?= htmlspecialchars($note['created_at']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php elseif (!$db['tableExists'] && $db['connected']): ?>
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-3 mb-6 rounded-md">
                <h4 class="font-semibold mb-1 text-yellow-900">Database exists but tables are missing</h4>
                <p class="text-yellow-800">Run the migration script to create the required tables:</p>
                <pre class="text-yellow-800 p-2 mt-1 rounded border border-yellow-200 text-sm">docker-compose exec app php database/migrate.php</pre>
            </div>
        <?php endif; ?>

        <!-- Next Steps Cards -->
        <div class="grid md:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-md shadow-sm border border-php-200">
                <div class="bg-gradient-to-r from-php-500 to-php-600 text-white p-3 rounded-t-md">
                    <i class="fas fa-code mr-2"></i> Development Setup
                </div>
                <div class="p-5">
                    <div class="space-y-4">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 w-6 h-6 bg-php-100 text-php-700 rounded-full flex items-center justify-center text-sm font-medium">1</div>
                            <div class="flex-1">
                                <p class="text-php-800 font-medium mb-1">Create your .env file</p>
                                <div class="text-sm text-php-700">
                                    <span>Copy <code class="bg-php-100 text-php-800 px-2 py-0.5 rounded border border-php-200 text-xs">.env.example</code> to <code class="bg-php-100 text-php-800 px-2 py-0.5 rounded border border-php-200 text-xs">.env</code></span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 w-6 h-6 bg-php-100 text-php-700 rounded-full flex items-center justify-center text-sm font-medium">2</div>
                            <div class="flex-1">
                                <p class="text-php-800 font-medium mb-1">Install packages</p>
                                <code class="bg-php-100 text-php-800 px-2 py-1 rounded border border-php-200 text-xs block">composer install</code>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 w-6 h-6 bg-php-100 text-php-700 rounded-full flex items-center justify-center text-sm font-medium">3</div>
                            <div class="flex-1">
                                <p class="text-php-800 font-medium mb-1">Initialize database</p>
                                <code class="bg-php-100 text-php-800 px-2 py-1 rounded border border-php-200 text-xs block">php database/migrate.php</code>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 w-6 h-6 bg-php-100 text-php-700 rounded-full flex items-center justify-center text-sm font-medium">4</div>
                            <div class="flex-1">
                                <p class="text-php-800 font-medium mb-1">Test API functionality</p>
                                <div class="text-sm text-php-700">
                                    <span>Configure API keys and run tests in <code class="bg-php-100 text-php-800 px-2 py-0.5 rounded border border-php-200 text-xs">tests/</code></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-md shadow-sm border border-php-200">
                <div class="bg-gradient-to-r from-php-500 to-php-600 text-white p-3 rounded-t-md">
                    <i class="fab fa-docker mr-2"></i> Docker Commands
                </div>
                <div class="p-5">
                    <div class="space-y-4">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 w-6 h-6 bg-php-100 text-php-700 rounded-full flex items-center justify-center text-sm font-medium">1</div>
                            <div class="flex-1">
                                <p class="text-php-800 font-medium mb-1">Run migration</p>
                                <code class="bg-php-100 text-php-800 px-2 py-1 rounded border border-php-200 text-xs block">docker-compose exec app php database/migrate.php</code>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 w-6 h-6 bg-php-100 text-php-700 rounded-full flex items-center justify-center text-sm font-medium">2</div>
                            <div class="flex-1">
                                <p class="text-php-800 font-medium mb-1">Install packages</p>
                                <code class="bg-php-100 text-php-800 px-2 py-1 rounded border border-php-200 text-xs block">docker-compose exec app composer install</code>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 w-6 h-6 bg-php-100 text-php-700 rounded-full flex items-center justify-center text-sm font-medium">3</div>
                            <div class="flex-1">
                                <p class="text-php-800 font-medium mb-1">Test API</p>
                                <code class="bg-php-100 text-php-800 px-2 py-1 rounded border border-php-200 text-xs block">docker-compose exec app php tests/tester.php</code>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 w-6 h-6 bg-php-100 text-php-700 rounded-full flex items-center justify-center text-sm font-medium">4</div>
                            <div class="flex-1">
                                <p class="text-php-800 font-medium mb-1">Access phpMyAdmin</p>
                                <a href="http://localhost:8081" class="text-php-600 hover:text-php-800 underline decoration-php-300 hover:decoration-php-400 text-sm">localhost:8081</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer class="pt-4 my-md-5 pt-md-5 border-t border-php-200">
            <div class="row">
                <div class="col-12 col-md">
                    <small class="d-block mb-3 text-php-500">&copy; <?= date('Y') ?> <?= htmlspecialchars($config['app']['name'] ?? 'Simple PHP Initialization') ?></small>
                </div>
            </div>
        </footer>
    </div>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'php': {
                            50: '#f8f9fc',
                            100: '#f1f2f8',
                            200: '#e2e5f0',
                            300: '#c8cde3',
                            400: '#9ba5d1',
                            500: '#7881bf',
                            600: '#4e5b93',
                            700: '#414d7a',
                            800: '#363f64',
                            900: '#2d3553',
                        }
                    }
                }
            }
        }
    </script>
</body>

</html>