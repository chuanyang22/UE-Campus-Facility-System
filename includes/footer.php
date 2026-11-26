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
            <li><a href="<?= $baseUrl ?>modules/maintenance/index.php"><?= htmlspecialchars(t('footer_maint')) ?></a></li>
            <li><a href="<?= $baseUrl ?>modules/feedback/index.php"><?= htmlspecialchars(t('footer_fb')) ?></a></li>
        </ul>
    </div>
</footer>
    </div><!-- /.app-content -->
</div><!-- /.app-shell -->

<script>
function toggleUeSidebar() {
    var root = document.documentElement;
    var isCollapsed = root.classList.toggle('sidebar-collapsed-init');
    localStorage.setItem('ue_sidebar_collapsed', isCollapsed ? '1' : '0');
}
function updateThemeBtnIcon() {
    var btn = document.getElementById('themeToggleBtn');
    if (!btn) return;
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    btn.textContent = isDark ? '☀️' : '🌙';
}
function toggleUeTheme() {
    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    if (isDark) {
        document.documentElement.removeAttribute('data-theme');
        localStorage.setItem('ue_theme', 'light');
    } else {
        document.documentElement.setAttribute('data-theme', 'dark');
        localStorage.setItem('ue_theme', 'dark');
    }
    updateThemeBtnIcon();
}
document.addEventListener('DOMContentLoaded', updateThemeBtnIcon);
</script>
<script src="<?= $baseUrl ?>assets/js/app.js"></script>
</body>
</html>
