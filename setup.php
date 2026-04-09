<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BrainRent - Database Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    <style>
        body {
            background: var(--br-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .setup-card {
            max-width: 700px;
            margin: 0 auto;
        }

        .log-output {
            background: #1a1a1a;
            color: #0f0;
            padding: 15px;
            border-radius: 8px;
            max-height: 400px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 0.85rem;
        }

        .success {
            color: #4ecb71;
        }

        .error {
            color: #ff6b6b;
        }

        .info {
            color: #3ecfcf;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="setup-card">
            <div class="text-center mb-4">
                <span class="br-logo-icon mb-3" style="width: 64px; height: 64px; font-size: 32px;">🧠</span>
                <h1 class="fw-bold mb-2">BrainRent Setup</h1>
                <p class="text-muted">Automated Database Installation</p>
            </div>

            <?php
            require_once __DIR__ . '/config/db.php';

            $output = [];
            $allGood = true;

            function logMessage($msg, $type = 'info')
            {
                global $output;
                $output[] = ['msg' => $msg, 'type' => $type];
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup'])) {
                logMessage('Starting database setup...', 'info');

                try {
                    // Connect to MySQL without selecting database
                    $dsn = 'mysql:host=' . DB_SERVER . ';charset=utf8mb4;port=' . DB_PORT;
                    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    logMessage('✓ Connected to MySQL server', 'success');

                    // Create database if not exists
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    logMessage('✓ Database "' . DB_NAME . '" created/verified', 'success');

                    // Select database
                    $pdo->exec("USE " . DB_NAME);

                    // Read and execute main SQL file
                    $mainSql = file_get_contents(__DIR__ . '/database/brain_rent_mysql.sql');
                    if ($mainSql) {
                        $statements = array_filter(array_map('trim', explode(';', $mainSql)));
                        foreach ($statements as $statement) {
                            if (!empty($statement) && !preg_match('/^(CREATE DATABASE|USE brain_rent)/i', $statement)) {
                                $pdo->exec($statement);
                            }
                        }
                        logMessage('✓ Main database schema imported', 'success');
                    }

                    // Read and execute new features SQL file
                    $featuresSql = file_get_contents(__DIR__ . '/database/add_new_features.sql');
                    if ($featuresSql) {
                        $statements = array_filter(array_map('trim', explode(';', $featuresSql)));
                        foreach ($statements as $statement) {
                            if (!empty($statement) && !preg_match('/^(USE brain_rent)/i', $statement)) {
                                try {
                                    $pdo->exec($statement);
                                } catch (PDOException $e) {
                                    // Ignore table already exists errors
                                    if (strpos($e->getMessage(), 'already exists') === false) {
                                        throw $e;
                                    }
                                }
                            }
                        }
                        logMessage('✓ New features schema imported', 'success');
                    }

                    // Read and execute admin features SQL file
                    $adminSqlFile = __DIR__ . '/database/add_admin_features.sql';
                    if (file_exists($adminSqlFile)) {
                        $adminSql = file_get_contents($adminSqlFile);
                        if ($adminSql) {
                            $statements = array_filter(array_map('trim', explode(';', $adminSql)));
                            foreach ($statements as $statement) {
                                if (!empty($statement) && !preg_match('/^(USE brain_rent)/i', $statement)) {
                                    $pdo->exec($statement);
                                }
                            }
                            logMessage('✓ Admin schema imported', 'success');
                        }
                    }

                    // Verify tables exist
                    $requiredTables = ['users', 'expert_profiles', 'libraries', 'notes', 'problem_solving_videos'];
                    $stmt = $pdo->query("SHOW TABLES");
                    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    foreach ($requiredTables as $table) {
                        if (in_array($table, $tables)) {
                            logMessage("✓ Table '$table' exists", 'success');
                        } else {
                            logMessage("✗ Table '$table' is missing!", 'error');
                            $allGood = false;
                        }
                    }

                    // Check upload directories
                    $uploadDirs = ['uploads/ebooks', 'uploads/notes', 'uploads/videos', 'uploads/thumbnails'];
                    foreach ($uploadDirs as $dir) {
                        if (is_dir(__DIR__ . '/' . $dir)) {
                            logMessage("✓ Directory '$dir/' exists", 'success');
                        } else {
                            logMessage("! Directory '$dir/' missing (will be created)", 'info');
                            @mkdir(__DIR__ . '/' . $dir, 0755, true);
                        }
                    }

                    if ($allGood) {
                        logMessage('', 'info');
                        logMessage('========================================', 'success');
                        logMessage('✓ SETUP COMPLETE!', 'success');
                        logMessage('========================================', 'success');
                        logMessage('', 'info');
                        logMessage('You can now:', 'info');
                        logMessage('1. Go to: http://localhost/brain-rent/pages/auth.php?tab=signup', 'info');
                        logMessage('2. Create your account', 'info');
                        logMessage('3. Start using BrainRent!', 'info');
                    }
                } catch (PDOException $e) {
                    logMessage('✗ ERROR: ' . $e->getMessage(), 'error');
                    $allGood = false;
                }
            }
            ?>

            <div class="br-card p-4">
                <?php if (empty($output)): ?>
                    <div class="mb-4">
                        <h5 class="fw-semibold mb-3">📋 Pre-Setup Checklist</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2">✓ XAMPP is installed</li>
                            <li class="mb-2">✓ Apache is running</li>
                            <li class="mb-2">✓ MySQL is running</li>
                            <li class="mb-2">✓ Project is in htdocs folder</li>
                        </ul>
                    </div>

                    <form method="post">
                        <div class="alert br-alert-info mb-4">
                            <strong>ℹ️ This will:</strong>
                            <ul class="mb-0 mt-2 small">
                                <li>Create the <code>brain_rent</code> database</li>
                                <li>Import all required tables</li>
                                <li>Set up upload directories</li>
                                <li>Verify installation</li>
                            </ul>
                        </div>

                        <button type="submit" name="setup" class="btn br-btn-gold w-100 btn-lg">
                            🚀 Run Automatic Setup
                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <small class="text-muted">
                            Or manually follow instructions in <a href="README.md" target="_blank">README.md</a>
                        </small>
                    </div>
                <?php else: ?>
                    <h5 class="fw-semibold mb-3">📝 Setup Log</h5>
                    <div class="log-output mb-4">
                        <?php foreach ($output as $log): ?>
                            <div class="<?= $log['type'] ?>"><?= htmlspecialchars($log['msg']) ?></div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($allGood): ?>
                        <a href="pages/auth.php?tab=signup" class="btn br-btn-gold w-100 btn-lg">
                            ✨ Create Your Account
                        </a>
                        <a href="pages/index.php" class="btn br-btn-ghost w-100 mt-2">
                            🏠 Go to Homepage
                        </a>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <strong>⚠️ Setup encountered errors.</strong><br>
                            Please check the log above and try again, or set up manually using README.md
                        </div>
                        <form method="post">
                            <button type="submit" name="setup" class="btn br-btn-gold w-100">
                                🔄 Try Again
                            </button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="text-center mt-4">
                <small class="text-muted">
                    Need help? Check <strong>README.md</strong> for detailed setup instructions
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>