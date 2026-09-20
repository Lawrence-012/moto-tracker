<?php

/**
 * Simple Google Drive OAuth Token Generator
 * Direct access without Laravel routing
 */

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/vendor/autoload.php';

session_start();

// Your OAuth credentials
$clientId = '';
$clientSecret = '';
$redirectUri = 'http://localhost';
$folderId = '1lrMnKP-cACYe7MgYBq9txMcCoXRwbF2D';

$client = new Google_Client();
$client->setClientId($clientId);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope(Google_Service_Drive::DRIVE);
$client->setAccessType('offline');
$client->setPrompt('select_account consent');

// If code is present in URL, exchange it for tokens
if (isset($_GET['code'])) {
    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        if (!isset($token['error'])) {
            echo "<!DOCTYPE html>
<html>
<head>
    <title>Success! - Google Drive Credentials</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f8f0; padding: 40px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #1e6031; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 4px; margin: 20px 0; }
        pre { background: #f8f9fa; padding: 20px; border-radius: 4px; overflow-x: auto; border: 1px solid #dee2e6; }
        .credential { margin: 20px 0; }
        label { font-weight: bold; color: #1e6031; display: block; margin-bottom: 5px; }
        .copy-btn { background: #1e6031; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; margin-top: 5px; }
        .copy-btn:hover { background: #164f2a; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 15px; border-radius: 4px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>✅ Success! Authorization Complete</h1>
        <div class='success'>
            <strong>Your Google Drive credentials are ready!</strong> Copy these values to your .env file.
        </div>
        
        <div class='credential'>
            <label>GOOGLE_DRIVE_CLIENT_ID:</label>
            <pre>" . htmlspecialchars($clientId) . "</pre>
        </div>
        
        <div class='credential'>
            <label>GOOGLE_DRIVE_CLIENT_SECRET:</label>
            <pre>" . htmlspecialchars($clientSecret) . "</pre>
        </div>
        
        <div class='credential'>
            <label>GOOGLE_DRIVE_REFRESH_TOKEN:</label>
            <pre>" . htmlspecialchars($token['refresh_token']) . "</pre>
            <button class='copy-btn' onclick=\"navigator.clipboard.writeText('" . htmlspecialchars($token['refresh_token']) . "')\">Copy to Clipboard</button>
        </div>
        
        <div class='credential'>
            <label>GOOGLE_DRIVE_FOLDER_ID:</label>
            <pre>" . htmlspecialchars($folderId) . "</pre>
        </div>
        
        <div class='warning'>
            <strong>⚠️ IMPORTANT:</strong> Save the refresh token immediately! You won't be able to see it again. Add all these values to your .env file.
        </div>
        
        <p><strong>Next steps:</strong></p>
        <ol>
            <li>Copy all the credentials above</li>
            <li>Add them to your <code>.env</code> file</li>
            <li>Run: <code>php artisan config:clear && php artisan cache:clear</code></li>
            <li>Test by uploading a completion file!</li>
        </ol>
    </div>
</body>
</html>";
            exit;
        } else {
            throw new Exception($token['error']);
        }
    } catch(Exception $e) {
        echo "<h1>Error</h1><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        exit;
    }
}

// Generate authorization URL
$authUrl = $client->createAuthUrl();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Google Drive OAuth Setup</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f8f0; padding: 40px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #1e6031; }
        .step { margin: 20px 0; padding: 15px; background: #f0f8f0; border-left: 4px solid #1e6031; }
        .btn { display: inline-block; background: #1e6031; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: bold; margin: 20px 0; }
        .btn:hover { background: #164f2a; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
       code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Google Drive OAuth Setup</h1>
        
        <div class="step">
            <h2>Step 1: Test User Required</h2>
            <p><strong>Important:</strong> You must add your Google account as a test user in the OAuth consent screen.</p>
            <ol>
                <li>Go to: <a href="https://console.cloud.google.com/apis/credentials/consent" target="_blank">OAuth Consent Screen</a></li>
                <li>Scroll down to "Test users"</li>
                <li>Click "+ ADD USERS"</li>
                <li>Add the Google account you want to use for Drive uploads</li>
                <li>Click "SAVE"</li>
            </ol>
        </div>

        <div class="step">
            <h2>Step 2: Authorize Access</h2>
            <p>Click the button below to authorize MPIS to access your Google Drive:</p>
            <a href="<?php echo htmlspecialchars($authUrl); ?>" class="btn">Authorize Google Drive Access</a>
        </div>

        <div class="step">
            <h2>Step 3: Save Credentials</h2>
            <p>After authorization, you'll receive your refresh token. Add all credentials to your <code>.env</code> file:</p>
            <pre>GOOGLE_DRIVE_CLIENT_ID=your_client_id
GOOGLE_DRIVE_CLIENT_SECRET=your_client_secret
GOOGLE_DRIVE_REFRESH_TOKEN=your_refresh_token
GOOGLE_DRIVE_FOLDER_ID=1lrMnKP-cACYe7MgYBq9txMcCoXRwbF2D</pre>
        </div>

        <div class="warning">
            <strong>⚠️ Note:</strong> Since your OAuth app is in testing mode, you must add your account as a test user first, or authorization will fail.
        </div>
    </div>
</body>
</html>
