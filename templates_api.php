<?php
// templates_api.php - CRUD API for `templates` table (MySQL via db.php)
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDBConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed. Pastikan MySQL di Laragon aktif dan database 'dynamic_parser' sudah dibuat (import schema.sql)."]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Map DB row (snake_case) -> JS payload (camelCase) supaya kompatibel dengan script.js
function rowToTemplate(array $row): array {
    return [
        'id' => $row['id'],
        'name' => $row['name'],
        'description' => $row['description'] ?? '',
        'method' => $row['method'],
        'apiUrl' => $row['api_url'],
        'inputTemplate' => $row['input_template'] ?? '',
        'inputParameter' => $row['input_parameter'] ?? '',
        'sampleWebResponse' => $row['sample_web_response'] ?? '',
        'outputTemplate' => $row['output_template'],
        'paramOptions' => $row['param_options'] ?? '',
        'active' => (bool) $row['active'],
        'updatedAt' => substr($row['updated_at'], 0, 10),
    ];
}

switch ($method) {

    case 'GET':
        $id = $_GET['id'] ?? null;

        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM templates WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $row = $stmt->fetch();

            if (!$row) {
                http_response_code(404);
                echo json_encode(["error" => "Template not found."]);
                exit;
            }
            echo json_encode(rowToTemplate($row));
        } else {
            $stmt = $pdo->query("SELECT * FROM templates ORDER BY updated_at DESC");
            $rows = $stmt->fetchAll();
            echo json_encode(array_map('rowToTemplate', $rows));
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || empty($data['name']) || empty($data['apiUrl']) || empty($data['outputTemplate'])) {
            http_response_code(400);
            echo json_encode(["error" => "Field name, apiUrl, dan outputTemplate wajib diisi."]);
            exit;
        }

        $id = !empty($data['id']) ? $data['id'] : bin2hex(random_bytes(16));

        $stmt = $pdo->prepare("
            INSERT INTO templates
                (id, name, description, method, api_url, input_template, input_parameter, sample_web_response, output_template, param_options, active)
            VALUES
                (:id, :name, :description, :method, :api_url, :input_template, :input_parameter, :sample_web_response, :output_template, :param_options, :active)
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                description = VALUES(description),
                method = VALUES(method),
                api_url = VALUES(api_url),
                input_template = VALUES(input_template),
                input_parameter = VALUES(input_parameter),
                sample_web_response = VALUES(sample_web_response),
                output_template = VALUES(output_template),
                param_options = VALUES(param_options),
                active = VALUES(active)
        ");

        $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':description' => $data['description'] ?? '',
            ':method' => ($data['method'] ?? 'GET') === 'POST' ? 'POST' : 'GET',
            ':api_url' => $data['apiUrl'],
            ':input_template' => $data['inputTemplate'] ?? '',
            ':input_parameter' => $data['inputParameter'] ?? '',
            ':sample_web_response' => $data['sampleWebResponse'] ?? '',
            ':output_template' => $data['outputTemplate'],
            ':param_options' => $data['paramOptions'] ?? '',
            ':active' => !empty($data['active']) ? 1 : 1, // default aktif
        ]);

        $stmt = $pdo->prepare("SELECT * FROM templates WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        echo json_encode(rowToTemplate($stmt->fetch()));
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(["error" => "Parameter id wajib diisi."]);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM templates WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(["success" => true, "id" => $id]);
        break;

    default:
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed."]);
}
