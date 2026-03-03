<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class InboxController extends BaseController
{
    public function list(Request $request, Response $response): void
    {
        $user = $this->requireUser($request, $response);
        if (!$user) {
            return;
        }

        $workspaceId = $this->requireWorkspace($request, $response, (int)$user['id']);
        if (!$workspaceId) {
            return;
        }

        $status = $request->query('status');
        $priority = $request->query('priority');

        $sql = 'SELECT c.*, ct.name as contact_name, ct.email as contact_email
                FROM conversations c
                LEFT JOIN contacts ct ON ct.id = c.contact_id
                WHERE c.workspace_id = ?';
        $params = [$workspaceId];

        if ($status) {
            $sql .= ' AND c.status = ?';
            $params[] = $status;
        }
        if ($priority) {
            $sql .= ' AND c.priority = ?';
            $params[] = $priority;
        }

        $sql .= ' ORDER BY c.last_message_at DESC, c.id DESC LIMIT 100';

        $pdo = Database::pdo();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $data = array_map(static function (array $row): array {
            return [
                'id' => (int)$row['id'],
                'workspace_id' => (int)$row['workspace_id'],
                'channel' => $row['channel'],
                'status' => $row['status'],
                'priority' => $row['priority'],
                'last_message_at' => $row['last_message_at'],
                'contact' => ['name' => $row['contact_name'], 'email' => $row['contact_email']],
            ];
        }, $rows);

        $response->json(['conversations' => ['data' => $data]]);
    }

    public function show(Request $request, Response $response): void
    {
        $user = $this->requireUser($request, $response);
        if (!$user) {
            return;
        }

        $workspaceId = $this->requireWorkspace($request, $response, (int)$user['id']);
        if (!$workspaceId) {
            return;
        }

        $id = (int)$request->route('id');
        $pdo = Database::pdo();

        $stmt = $pdo->prepare('SELECT c.*, ct.name as contact_name, ct.email as contact_email FROM conversations c LEFT JOIN contacts ct ON ct.id = c.contact_id WHERE c.workspace_id=? AND c.id=? LIMIT 1');
        $stmt->execute([$workspaceId, $id]);
        $conversation = $stmt->fetch();
        if (!$conversation) {
            $response->json(['message' => 'Not found'], 404);
            return;
        }

        $mstmt = $pdo->prepare('SELECT id,direction,sender,body,sent_at,created_at FROM messages WHERE conversation_id=? ORDER BY id ASC');
        $mstmt->execute([$id]);
        $messages = $mstmt->fetchAll();

        $response->json(['conversation' => [
            'id' => (int)$conversation['id'],
            'channel' => $conversation['channel'],
            'status' => $conversation['status'],
            'priority' => $conversation['priority'],
            'contact' => ['name' => $conversation['contact_name'], 'email' => $conversation['contact_email']],
            'messages' => $messages,
        ]]);
    }
}
