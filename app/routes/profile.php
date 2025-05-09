<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use Firebase\JWT\JWT;

require_once __DIR__ . '/../utilities.php';
require_once __DIR__ . '/../sql-utilities.php';

// Profile view route
$app->get('/profile', function (Request $request, Response $response, array $args): Response {
    if (empty($_SESSION['current_user'])) {
        return redirect($response, '/login?redirect=/profile');
    }
    
    $db = new DB();
    $req = $db->prepareNamedQuery('select_user_from_id_user');
    $req->execute(['id_user' => $_SESSION['current_user']['id_user']]);
    $user = $req->fetch();
    
    // Debug logging
    error_log("Session user data: " . print_r($_SESSION['current_user'], true));
    error_log("Fetched user data: " . print_r($user, true));
    
    /** @var Twig $view */
    $view = $this->get('view');

    // Fetch user emails
    $user_emails = [];
    if (!empty($user)) {
        $emails_req = $db->prepareNamedQuery('select_user_emails_by_user_id');
        $emails_req->execute(['user_id' => $user['id_user']]);
        $user_emails = $emails_req->fetchAll();
    }

    return $view->render($response, 'profile.html.twig', [
        'user' => $user,
        'user_emails' => $user_emails // Pass user emails to the Twig template
    ]);
});

// Profile update route
$app->post('/profile', function (Request $request, Response $response, array $args): Response {
    if (empty($_SESSION['current_user'])) {
        return redirect($response, '/login?redirect=/profile');
    }
    
    $params = $request->getParsedBody();
    $new_username = $params['username'] ?? '';

    // Validate username:
    // 1. Check if the username is empty
    if (empty($new_username)) {
        alert('Username cannot be empty.', 3);
        return redirect($response, '/profile');
    }

    // 2. Validate username format using regex
    if (!preg_match('/^[a-zA-Z0-9_]{1,15}$/', $new_username)) {
        alert('Username can only contain letters, numbers, and underscores, and must be between 1 and 15 characters long.', 3);
        return redirect($response, '/profile');
    }

    $db = new DB();

    // 3. Check if the username already exists for another user
    $existing_user_req = $db->prepareNamedQuery('select_user_from_username');
    $existing_user_req->execute(['username' => $new_username]);
    $existing_user = $existing_user_req->fetch();

    if ($existing_user && $existing_user['id_user'] !== $_SESSION['current_user']['id_user']) {
        alert('This username is already taken.', 3);
        return redirect($response, '/profile');
    }
    
    // Update user profile
    $req = $db->prepareNamedQuery('update_user_profile');
    $req->execute([
        'id_user' => $_SESSION['current_user']['id_user'],
        'username' => $new_username
    ]);
    
    // Update session data with the new username
    $_SESSION['current_user']['username'] = $new_username;
    
    // Update last_user_update to invalidate old tokens
    $req = $db->prepareNamedQuery('update_last_user_update');
    $req->execute([
        'id_user' => $_SESSION['current_user']['id_user'],
        'new_date' => date('H:i:s')
    ]);
    
    alert('Profile updated successfully', 1);
    return redirect($response, '/profile');
});

// Profile picture update route
$app->post('/profile/picture', function (Request $request, Response $response, array $args): Response {
    if (empty($_SESSION['current_user'])) {
        return redirect($response, '/login?redirect=/profile');
    }
    
    $uploadedFiles = $request->getUploadedFiles();
    
    // Check if a file was uploaded
    if (empty($uploadedFiles['profile_picture'])) {
        alert('No file was uploaded', 3);
        return redirect($response, '/profile');
    }
    
    $uploadedFile = $uploadedFiles['profile_picture'];
    
    // Check for errors
    if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
        alert('Upload error: ' . $uploadedFile->getError(), 3);
        return redirect($response, '/profile');
    }
    
    // Validate file type
    $fileExtension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array(strtolower($fileExtension), $allowedExtensions)) {
        alert('Only image files (JPG, JPEG, PNG, GIF) are allowed', 3);
        return redirect($response, '/profile');
    }
    
    // Generate a unique filename
    $filename = 'profile_' . $_SESSION['current_user']['id_user'] . '_' . uniqid() . '.' . $fileExtension;
    $uploadPath = __DIR__ . '/../../uploads/';
    
    // Create uploads directory if it doesn't exist
    if (!file_exists($uploadPath)) {
        mkdir($uploadPath, 0777, true);
    }
    
    // Move the uploaded file
    $uploadedFile->moveTo($uploadPath . $filename);
    
    // Update the user profile in the database
    $db = new DB();
    $req = $db->prepareNamedQuery('update_user_profile_picture');
    $req->execute([
        'id_user' => $_SESSION['current_user']['id_user'],
        'profile_picture' => $filename
    ]);
    
    // Update session data with the new profile picture
    $_SESSION['current_user']['profile_picture'] = $filename;
    
    // Update last_user_update to invalidate old tokens
    $req = $db->prepareNamedQuery('update_last_user_update');
    $req->execute([
        'id_user' => $_SESSION['current_user']['id_user'],
        'new_date' => date('H:i:s')
    ]);
    
    alert('Profile picture updated successfully', 1);
    return redirect($response, '/profile');
});

// Username validation route (for AJAX)
$app->post('/profile/validate-username', function (Request $request, Response $response): Response {
    $params = $request->getParsedBody();
    $username = $params['username'] ?? '';
    $current_user_id = $_SESSION['current_user']['id_user'] ?? null;

    $response_data = ['valid' => true];

    if (empty($username)) {
        $response_data = ['valid' => false, 'message' => 'Username cannot be empty.'];
    } else {
        // Validate username format using regex
        if (!preg_match('/^[a-zA-Z0-9_]{1,15}$/', $username)) {
            $response_data = ['valid' => false, 'message' => 'Username can only contain letters, numbers, and underscores, and must be between 1 and 15 characters long.'];
        } else {
            $db = new DB();
            // Check if the username already exists for another user
            $existing_user_req = $db->prepareNamedQuery('select_user_from_username');
            $existing_user_req->execute(['username' => $username]);
            $existing_user = $existing_user_req->fetch();

            if ($existing_user && $existing_user['id_user'] !== $current_user_id) {
                $response_data = ['valid' => false, 'message' => 'This username is already taken.'];
            }
        }
    }

    $response = $response->withHeader('Content-Type', 'application/json');
    $response->getBody()->write(json_encode($response_data));
    return $response;
});

$app->post('/profile/emails/add', function (Request $request, Response $response) {
    if (!isset($_SESSION['current_user'])) {
        alert("Please log in to manage your email addresses.", 3);
        return redirect($response, '/signin');
    }

    $params = $request->getParsedBody();
    $user = $_SESSION['current_user'];

    // Debug logging
    error_log("Session user: " . print_r($user, true));
    error_log("User ID from session: " . ($user['id_user'] ?? 'not set'));

    if (empty($user['id_user'])) {
        alert("Invalid user session. Please log in again.", 3);
        return redirect($response, '/signin');
    }

    if (empty($params['email'])) {
        alert("Email address is required.", 3);
        return redirect($response, '/profile');
    }

    $email = $params['email'];

    // Check if email already exists for this user or another user
    $db = new DB();
    $existing_email = $db->prepareNamedQuery('select_user_email_by_email');
    $existing_email->execute(['email' => $email]);

    if ($existing_email->rowCount() > 0) {
        alert("This email address is already in use.", 3);
        return redirect($response, '/profile');
    }

    try {
        // Generate verification token
        $verification_token = jwt_encode([
            "email" => $email,
            "user_id" => (int)$user['id_user'],
            "type" => "email_verification"
        ], 60 * 24); // Token valid for 24 hours

        // Debug logging
        error_log("Generated verification token: " . $verification_token);

        // Insert email into user_emails table
        $insert_email = $db->prepareNamedQuery('insert_user_email');
        $insert_email->execute([
            'user_id' => (int)$user['id_user'],
            'email' => $email,
            'verification_token' => $verification_token,
        ]);

        // Verify the email was inserted correctly
        $check_email = $db->prepareNamedQuery('select_user_email_by_user_id_and_email');
        $check_email->execute(['user_id' => (int)$user['id_user'], 'email' => $email]);
        $inserted_email = $check_email->fetch();
        error_log("Inserted email record: " . print_r($inserted_email, true));

        // Send verification email
        $email_response = sendVerificationEmail($this, $response, $email, $verification_token);
        if ($_ENV['app_mode'] == 'dev' && $email_response instanceof Response) {
            return $email_response;
        }

        alert("Verification email sent.", 1);
        return redirect($response, '/profile');
    } catch (Exception $e) {
        error_log("Error adding email: " . $e->getMessage());
        alert("Error adding email: " . $e->getMessage(), 3);
        return redirect($response, '/profile');
    }
});

// Function to send verification email
function sendVerificationEmail($container, $response, $email, $token) {
    /** @var Twig $view */
    $view = $container->get('view');
    
    // Debugging: Check Twig environment and loader paths
    error_log("Twig view object class: " . (is_object($view) ? get_class($view) : 'Not an object'));
    $loader = $view->getLoader();
    error_log("Twig loader object class: " . (is_object($loader) ? get_class($loader) : 'Not an object'));
    
    // Attempt to get and log paths if the loader is a FilesystemLoader
    try {
        if ($loader instanceof \Twig\Loader\FilesystemLoader) {
             error_log("Twig template paths: " . print_r($loader->getPaths(), true));
        } else {
             error_log("Twig loader is not a FilesystemLoader. It is: " . (is_object($loader) ? get_class($loader) : 'Not an object'));
        }
    } catch (Exception $e) {
        error_log("Error getting Twig loader paths: " . $e->getMessage());
    }

    // Debugging: Check if the template file exists (relative to Twig's known paths)
    $template_name = 'emails/verify-backup-email.html.twig';
    error_log("Attempting to load template: " . $template_name);
    try {
        // Use is_callable for a safer check
        if (is_callable([$loader, 'exists']) && $loader->exists($template_name)) {
            error_log("Twig loader reports template '" . $template_name . "' exists.");
        } else {
            error_log("Twig loader reports template '" . $template_name . "' does NOT exist or exists method not available.");
        }
    } catch (Exception $e) {
        error_log("Error checking template existence with loader->exists(): " . $e->getMessage());
    }

    // Construct the verification URL
    $verification_url = getBaseUrl() . '/verify-email?token=' . $token;
    
    // Debug logging
    error_log("Sending verification email to: " . $email);
    error_log("Verification URL: " . $verification_url);
    
    $email_content = $view->fetch('emails/verify-email.html.twig', [
        'url' => $verification_url
    ]);

    // Use the existing sendEmail function
    $email_response = sendEmail(
        $container,
        $response,
        $email,
        "Verify your email address",
        $email_content
    );

    // In dev mode, return the email content directly
    if ($_ENV['app_mode'] == 'dev') {
        return $email_response;
    }
    
    return $response;
}

$app->get('/verify-email', function (Request $request, Response $response): Response {
    $token = $request->getQueryParams()['token'] ?? null;

    if (empty($token)) {
        alert("Verification token is missing.", 3);
        return redirect($response, '/profile');
    }

    try {
        // Debug logging
        error_log("Verifying token: " . $token);
        
        // Decode and validate the token
        $decoded_token = jwt_decode($token);
        error_log("Decoded token: " . print_r($decoded_token, true));

        // Validate token type
        if (!isset($decoded_token['type']) || $decoded_token['type'] !== 'email_verification') {
            error_log("Invalid token type: " . ($decoded_token['type'] ?? 'not set'));
            alert("Invalid verification token.", 3);
            return redirect($response, '/profile');
        }

        $email = $decoded_token['email'] ?? null;
        $userId = $decoded_token['user_id'] ?? null;

        if (empty($email) || empty($userId)) {
            error_log("Invalid token data - email: " . ($email ?? 'null') . ", userId: " . ($userId ?? 'null'));
            alert("Invalid verification token.", 3);
            return redirect($response, '/profile');
        }

        $db = new DB();

        // Find the email in the database that matches the token and is not yet verified
        $user_email_req = $db->prepareNamedQuery('select_user_email_by_user_id_and_email');
        $user_email_req->execute(['user_id' => $userId, 'email' => $email]);
        $user_email = $user_email_req->fetch();

        error_log("Found user email: " . print_r($user_email, true));

        if (!$user_email) {
            alert("Email not found.", 3);
            return redirect($response, '/profile');
        }

        if ($user_email['verification_token'] !== $token) {
            error_log("Token mismatch - Expected: " . $user_email['verification_token'] . ", Got: " . $token);
            alert("Invalid verification token.", 3);
            return redirect($response, '/profile');
        }

        if ($user_email['is_verified'] == 1) {
            alert("Email is already verified.", 3);
            return redirect($response, '/profile');
        }

        // Update the email as verified
        $update_email_req = $db->prepareNamedQuery('update_user_email_verified');
        $update_email_req->execute(['id' => $user_email['id']]);

        alert("Email address verified successfully!", 1);
        return redirect($response, '/profile');

    } catch (Exception $e) {
        // Handle token decoding errors or DB errors
        error_log("Error verifying email: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
        alert("Error verifying email: " . $e->getMessage(), 3);
        return redirect($response, '/profile');
    }
});

$app->post('/profile/emails/set-default', function (Request $request, Response $response) {
    if (!isset($_SESSION['current_user'])) {
        return redirect($response, '/signin');
    }

    $params = $request->getParsedBody();
    $user = $_SESSION['current_user'];
    $emailId = $params['email_id'] ?? null;

    if (empty($emailId)) {
        alert("No email ID provided.", 3);
        return redirect($response, '/profile');
    }

    $db = new DB();

    try {
         // Verify the email belongs to the user and is verified before setting as default
        $user_email_req = $db->prepareNamedQuery('select_user_email_by_id_and_user_id');
        $user_email_req->execute(['id' => $emailId, 'user_id' => $user['id_user']]);
        $user_email = $user_email_req->fetch();

        if (!$user_email || $user_email['is_verified'] == 0) {
            alert("Cannot set unverified or invalid email as default.", 3);
            return redirect($response, '/profile');
        }

        // Set all of the user's emails to not default first
        $db->prepareNamedQuery('update_user_emails_unset_default')->execute(['user_id' => $user['id_user']]);

        // Set the selected email to default
        $update_default_req = $db->prepareNamedQuery('update_user_email_set_default');
        // Removed user_id from this execute as it's already checked above and query only uses id
        $result = $update_default_req->execute(['id' => $emailId, 'user_id' => $user['id_user']]);

        // Check if the update was successful and the email belongs to the user
        // Note: execute() might return true even if no rows were affected (if email_id was invalid or not for this user) in some DB drivers.
        // A select after update or checking rowCount (if supported) might be more robust.

        alert("Default email address updated successfully!", 1);
        return redirect($response, '/profile');

    } catch (Exception $e) {
        alert("Error setting default email: " . $e->getMessage(), 3);
        return redirect($response, '/profile');
    }
});

$app->post('/profile/emails/delete-initiate', function (Request $request, Response $response) {
    $params = $request->getParsedBody();
    $user = $_SESSION['current_user']; // Corrected from $_SESSION['user']
    $emailId = $params['email_id'] ?? null;

    if (empty($emailId)) {
        alert("No email ID provided.", 3);
        return redirect($response, '/profile');
    }

    $db = new DB();

    try {
        // Fetch the email to be deleted
        $user_email_req = $db->prepareNamedQuery('select_user_email_by_id_and_user_id');
        $user_email_req->execute(['id' => $emailId, 'user_id' => $user['id_user']]);
        $user_email = $user_email_req->fetch();

        if (!$user_email) {
            alert("Email not found or does not belong to you.", 3);
            return redirect($response, '/profile');
        }

        // Prevent deleting the default email if it's the only one
         $all_emails_req = $db->prepareNamedQuery('select_user_emails_by_user_id');
         $all_emails_req->execute(['user_id' => $user['id_user']]);
         $all_emails = $all_emails_req->fetchAll();

        if ($user_email['is_default'] && count($all_emails) === 1) {
             alert("Cannot delete your last email address, which is also your default.", 3);
             return redirect($response, '/profile');
        }
         if ($user_email['is_default'] && count($all_emails) > 1) {
             alert("Cannot delete your default email address if you have other emails. Please set another email as default first.", 3);
             return redirect($response, '/profile');
         }


        // Generate deletion token
        // Ensure jwt_encode is available
        $deletion_token = jwt_encode([
            "email_id" => $emailId,
            "user_id" => $user['id_user'],
        ], 60 * 60 * 24 * 7); // Token valid for 7 days

        // Mark email as pending deletion and save token
        $update_pending_req = $db->prepareNamedQuery('update_user_email_set_pending_deletion');
        $update_pending_req->execute(['id' => $emailId, 'deletion_token' => $deletion_token, 'user_id' => $user['id_user']]);

        // Send cancellation email
        $email_response = sendDeletionCancellationEmail($this, $response, $user_email['email'], $deletion_token);
         // Handle the response from sendEmail if in dev mode, similar to signin.php
        if ($_ENV['app_mode'] == 'dev' && $email_response instanceof Response) {
             return $email_response;
        }

        alert("Email marked for deletion. A cancellation link has been sent to the email address.", 1);
        return redirect($response, '/profile');

    } catch (Exception $e) {
        alert("Error initiating email deletion: " . $e->getMessage(), 3);
        return redirect($response, '/profile');
    }
});

// Function to send deletion cancellation email
function sendDeletionCancellationEmail($container, $response, $email, $token) {
    /** @var Twig $view */
    $view = $container->get('view');
    $email_content = $view->fetch('emails/cancel-deletion.html.twig', [
        'url' => getBaseUrl() . '/cancel-email-deletion?token=' . $token
    ]);

    // Use the existing sendEmail function
    $email_response = sendEmail(
        $container,
        $response,
        $email,
        "Cancel Email Deletion",
        $email_content
    );

    // In dev mode, return the email content directly
    if ($_ENV['app_mode'] == 'dev') {
        return $email_response;
    }
    
    return $response;
}

$app->get('/cancel-email-deletion', function (Request $request, Response $response): Response {
    $token = $request->getQueryParams()['token'] ?? null;

    if (empty($token)) {
        alert("Cancellation token is missing.", 3);
        return $response->withHeader('Location', '/profile')->withStatus(302);
    }

    $db = new DB();

    try {
        // Decode and validate the token
         // Ensure jwt_decode is available
        $decoded_token = jwt_decode($token);

        $emailId = $decoded_token->email_id ?? null;
        $userId = $decoded_token->user_id ?? null;

         if (empty($emailId) || empty($userId)) {
             alert("Invalid cancellation token.", 3);
             return $response->withHeader('Location', '/profile')->withStatus(302);
        }

        // Find the email in the database that is pending deletion with this token and user ID
        $user_email_req = $db->prepareNamedQuery('select_user_email_pending_deletion_by_id_token_and_user_id');
        $user_email_req->execute(['id' => $emailId, 'deletion_token' => $token, 'user_id' => $userId]);
        $user_email = $user_email_req->fetch();

        if (!$user_email) {
            // Token does not match, email not found, not pending deletion, or does not belong to user
            alert("Invalid or expired cancellation token.", 3);
            return $response->withHeader('Location', '/profile')->withStatus(302);
        }

        // Cancel the pending deletion
        $cancel_deletion_req = $db->prepareNamedQuery('update_user_email_cancel_pending_deletion');
        $cancel_deletion_req->execute(['id' => $emailId]);

        alert("Email deletion cancelled successfully!", 1);
        return $response->withHeader('Location', '/profile')->withStatus(302);

    } catch (Exception $e) {
        // Handle token decoding errors or DB errors
        alert("Error cancelling email deletion: " . $e->getMessage(), 3);
        return $response->withHeader('Location', '/profile')->withStatus(302);
    }
});

// Add/Update Backup Email
$app->post('/profile/backup-email', function (Request $request, Response $response): Response {
    if (empty($_SESSION['current_user'])) {
        return redirect($response, '/login?redirect=/profile');
    }

    $params = $request->getParsedBody();
    $email = $params['backup_email'] ?? '';

    if (empty($email)) {
        alert('Backup email is required', 3);
        return redirect($response, '/profile');
    }

    // Check if email is different from primary email
    if ($email === $_SESSION['current_user']['email']) {
        alert('Backup email cannot be the same as your primary email', 3);
        return redirect($response, '/profile');
    }

    // Generate verification token
    $token = jwt_encode([
        'type' => 'backup_email_verification',
        'email' => $email,
        'id_user' => $_SESSION['current_user']['id_user'],
        'timestamp' => time()
    ], 60 * 24); // 24 hours expiry

    // Save pending backup email
    $db = new DB();
    $req = $db->prepareNamedQuery('update_pending_backup_email');
    $req->execute([
        'id_user' => $_SESSION['current_user']['id_user'],
        'email' => $email,
        'token' => $token
    ]);

    // Send verification email
    /** @var Twig $view */
    $view = $this->get('view');
    $email_content = $view->fetch('emails/verify-backup-email.html.twig', [
        'url' => getBaseUrl() . '/profile/backup-email/verify?token=' . $token
    ]);

    $email_response = sendEmail(
        $this,
        $response,
        $email,
        "Verify your backup email address",
        $email_content
    );

    if ($_ENV['app_mode'] == 'dev') {
        return $email_response;
    }

    alert('Verification email sent to your backup email address', 1);
    return redirect($response, '/profile');
});

// Verify Backup Email
$app->get('/profile/backup-email/verify', function (Request $request, Response $response): Response {
    $token = $request->getQueryParams()['token'] ?? '';

    if (empty($token)) {
        alert('Invalid verification link', 3);
        return redirect($response, '/profile');
    }

    try {
        $decoded = jwt_decode($token);
        
        if ($decoded['type'] !== 'backup_email_verification') {
            throw new Exception('Invalid token type');
        }

        $db = new DB();
        $req = $db->prepareNamedQuery('verify_backup_email');
        $req->execute([
            'id_user' => $decoded['id_user'],
            'token' => $token
        ]);

        alert('Backup email verified successfully', 1);
        return redirect($response, '/profile');
    } catch (Exception $e) {
        alert('Invalid or expired verification link', 3);
        return redirect($response, '/profile');
    }
});

// Resend Verification Email
$app->post('/profile/backup-email/resend-verification', function (Request $request, Response $response): Response {
    if (empty($_SESSION['current_user'])) {
        return redirect($response, '/login?redirect=/profile');
    }

    $db = new DB();
    $req = $db->prepareNamedQuery('select_user_from_id_user');
    $req->execute(['id_user' => $_SESSION['current_user']['id_user']]);
    $user = $req->fetch();

    if (empty($user['pending_backup_email'])) {
        alert('No pending backup email to verify', 3);
        return redirect($response, '/profile');
    }

    // Generate new verification token
    $token = jwt_encode([
        'type' => 'backup_email_verification',
        'email' => $user['pending_backup_email'],
        'id_user' => $_SESSION['current_user']['id_user'],
        'timestamp' => time()
    ], 60 * 24); // 24 hours expiry

    // Update token
    $req = $db->prepareNamedQuery('update_pending_backup_email');
    $req->execute([
        'id_user' => $_SESSION['current_user']['id_user'],
        'email' => $user['pending_backup_email'],
        'token' => $token
    ]);

    // Send verification email
    /** @var Twig $view */
    $view = $this->get('view');
    $email_content = $view->fetch('emails/verify-backup-email.html.twig', [
        'url' => getBaseUrl() . '/profile/backup-email/verify?token=' . $token
    ]);

    $email_response = sendEmail(
        $this,
        $response,
        $user['pending_backup_email'],
        "Verify your backup email address",
        $email_content
    );

    if ($_ENV['app_mode'] == 'dev') {
        return $email_response;
    }

    alert('Verification email resent', 1);
    return redirect($response, '/profile');
});

// Remove Backup Email
$app->post('/profile/backup-email/remove', function (Request $request, Response $response): Response {
    if (empty($_SESSION['current_user'])) {
        return redirect($response, '/login?redirect=/profile');
    }

    $db = new DB();
    $req = $db->prepareNamedQuery('remove_backup_email');
    $req->execute(['id_user' => $_SESSION['current_user']['id_user']]);

    alert('Backup email removed successfully', 1);
    return redirect($response, '/profile');
}); 