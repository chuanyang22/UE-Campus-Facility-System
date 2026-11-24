<?php
/**
 * Master Page: Footer
 * AMCS2194 Mini Project — UE Campus Facility Booking System
 */

declare(strict_types=1);
?>
</main>

<footer class="footer-master">
    <div class="footer-container">
        <div class="footer-info">
            <p><strong>AMCS2194 Mini Project — UE Campus Facility Booking System</strong></p>
            <p>Tunku Abdul Rahman University of Management and Technology (TAR UMT)</p>
            <p>&copy; <?= date('Y') ?> Assignment Team. All rights reserved.</p>
        </div>
        <ul class="footer-modules">
            <li><a href="<?= $baseUrl ?>modules/maintenance/index.php">Module 5: Maintenance & Damage Reporting</a></li>
            <li><a href="<?= $baseUrl ?>modules/feedback/index.php">Module 6: User Feedback & Reviews</a></li>
        </ul>
    </div>
</footer>

<script src="<?= $baseUrl ?>assets/js/app.js"></script>
</body>
</html>
