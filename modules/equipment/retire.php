<?php
/**
 * Module 4: Retire / Remove Damaged or Obsolete Equipment (Delete)
 * AMCS2194 Mini Project — Process 4.4
 * Student 3: Shivendra
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/EquipmentService.php';

$equipmentId = trim((string)($_GET['id'] ?? $_POST['id'] ?? ''));
$service     = new EquipmentService();

if (!AuthService::isAdmin()) {
    AuthService::setFlash('danger', 'Unauthorized: Only Facility Administrators can retire equipment inventory records.');
    header('Location: index.php');
    exit;
}

if (empty($equipmentId)) {
    AuthService::setFlash('danger', 'No equipment ID specified for retirement.');
    header('Location: index.php');
    exit;
}

$result = $service->retireEquipment($equipmentId, true);

if ($result['success']) {
    AuthService::setFlash('success', $result['message']);
} else {
    AuthService::setFlash('danger', $result['message']);
}

header('Location: index.php');
exit;
