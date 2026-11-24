<?php
/**
 * Module 6: Delete Invalid Review (Delete)
 * AMCS2194 Mini Project — Process 6.4
 */

declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../services/FeedbackService.php';

$feedbackId = $_GET['id'] ?? '';
$service    = new FeedbackService();

if (!AuthService::isAdmin()) {
    AuthService::setFlash('danger', 'Unauthorized: Only Facility Administrators can moderate and delete reviews (Process 6.4).');
    header('Location: index.php');
    exit;
}

if (!empty($feedbackId)) {
    $result = $service->deleteReview($feedbackId);
    if ($result['success']) {
        AuthService::setFlash('success', $result['message']);
    } else {
        AuthService::setFlash('danger', $result['message']);
    }
} else {
    AuthService::setFlash('danger', 'No feedback ID specified.');
}

header('Location: index.php');
exit;
