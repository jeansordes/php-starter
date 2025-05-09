<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

require_once __DIR__ . '/../utilities.php';
require_once __DIR__ . '/../sql-utilities.php';

// Middleware to check if the user is an admin
$adminMiddleware = loggedInSlimMiddleware(['admin']);

$app->group('/admin', function ($group) {
    $group->get('', function (Request $request, Response $response, array $args): Response {
        return redirect($response, '/admin/app-config');
    });

    $group->get('/app-config', function (Request $request, Response $response, array $args): Response {
        $table_data = getTableData('app_config');
        /** @var Twig $view */
        $view = $this->get('view');
        return $view->render($response, 'admin/app-config.html.twig', [
            'columns' => $table_data['columns'],
            'columns_types' => $table_data['columns_types'],
            'data' => $table_data['data'],
            'edit_enabled' => true,
            'delete_enabled' => true,
            'create_enabled' => true,
        ]);
    });

    $group->post('/app-config', function (Request $request, Response $response): Response {
        $params = $request->getParsedBody();
        $action = $params['action'] ?? '';
        $id = $params['id'] ?? null;

        $db = new DB();

        try {
            switch ($action) {
                case 'create':
                    // Prepare data for insertion
                    $data = [];
                    foreach ($params as $key => $value) {
                        if ($key !== 'action') {
                            $data[$key] = $value;
                        }
                    }
                    // Log the data being inserted
                    console_log("Creating config entry with data: " . json_encode($data));

                    // Get column names for the app_config table
                    $columnNames = $db->getColumnNames('app_config'); // Assuming this function returns an array of column names
                    $columns = implode(", ", $columnNames);
                    $placeholders = implode(", ", array_map(fn($col) => ":$col", $columnNames));

                    // Construct the SQL insert statement
                    $sql = "INSERT INTO app_config ($columns) VALUES ($placeholders)";
                    console_log("Executing SQL: " . $sql . " with parameters: " . json_encode($data));

                    $req = $db->prepare($sql);
                    $req->execute($data);

                    alert('Config entry added successfully.', 1);
                    break;

                case 'update':
                    if (!$id) {
                        alert('ID is required for editing.', 3);
                        return redirect($response, '/admin/app-config');
                    }
                    // Prepare data for update
                    $data = ['id' => $id];
                    foreach ($params as $key => $value) {
                        if ($key !== 'action' && $key !== 'id') {
                            $data[$key] = $value;
                        }
                    }
                    // Log the data being updated
                    console_log("Updating entry with data: " . json_encode($data));

                    // Get column names for the app_config table
                    $columnNames = $db->getColumnNames('app_config'); // Assuming this function returns an array of column names
                    $setClause = implode(", ", array_map(fn($col) => "$col = :$col", $columnNames));

                    // Construct the SQL update statement
                    $sql = "UPDATE app_config SET $setClause WHERE id = :id";
                    console_log("Executing SQL: " . $sql . " with parameters: " . json_encode($data));

                    $req = $db->prepare($sql);
                    $req->execute($data);
                    alert('Config entry updated successfully.', 1);
                    break;

                case 'delete':
                    if (!$id) {
                        alert('ID is required for deletion.', 3);
                        return redirect($response, '/admin/app-config');
                    }
                    // Log the ID being deleted
                    console_log("Deleting config entry with ID: " . $id);
                    $req = $db->prepareNamedQuery('delete_app_config');
                    $req->execute(['id' => $id]);
                    alert('Config entry deleted successfully.', 1);
                    break;

                default:
                    alert('Invalid action: ' . $action, 3);
                    return redirect($response, '/admin/app-config');
            }
        } catch (Exception $e) {
            // Log the SQL error message
            console_log('SQL Error: ' . $e->getMessage());
            alert('SQL Error: ' . $e->getMessage(), 3);
            return redirect($response, '/admin/app-config');
        }

        return redirect($response, '/admin/app-config');
    });
})->add($adminMiddleware);
