<?php
/**
 * Module 5: Close / Delete Voided Ticket (Delete)
 * AMCS2194 Mini Project — Process 5.4
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/MaintenanceService.php';

$ticketId = $_GET['id'] ?? '';
$service  = new MaintenanceService();

if (!empty($ticketId)) {
    $result = $service->deleteTicket($ticketId);
    if ($result['success']) {
        AuthService::setFlash('success', $result['message']);
    } else {
        AuthService::setFlash('danger', $result['message']);
    }
} else {
    AuthService::setFlash('danger', 'No ticket ID specified for deletion.');
}

header('Location: index.php');
exit;
