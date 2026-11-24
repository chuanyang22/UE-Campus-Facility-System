/**
 * Client-Side Interactivity & Form Validation
 * AMCS2194 Mini Project — Modules 5 & 6
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Critical Urgency Warning on Defect Form (Feature 5.5 preview)
    const urgencySelect = document.getElementById('UrgencyLevel');
    const hazardWarning = document.getElementById('hazardWarningBox');

    if (urgencySelect && hazardWarning) {
        function checkUrgency() {
            if (urgencySelect.value === 'Critical') {
                hazardWarning.style.display = 'flex';
            } else {
                hazardWarning.style.display = 'none';
            }
        }
        urgencySelect.addEventListener('change', checkUrgency);
        checkUrgency();
    }

    // 2. Score calculator for feedback form (Module 6)
    const cleanInputs = document.querySelectorAll('input[name="CleanlinessRating"]');
    const condInputs = document.querySelectorAll('input[name="ConditionRating"]');
    const scoreDisplay = document.getElementById('calculatedScoreDisplay');

    function calculateOverall() {
        let cleanVal = 0;
        let condVal = 0;

        cleanInputs.forEach(i => { if (i.checked) cleanVal = parseInt(i.value, 10); });
        condInputs.forEach(i => { if (i.checked) condVal = parseInt(i.value, 10); });

        if (scoreDisplay) {
            if (cleanVal > 0 && condVal > 0) {
                const avg = ((cleanVal + condVal) / 2.0).toFixed(1);
                scoreDisplay.textContent = avg + ' / 5.0';
                scoreDisplay.className = 'badge ' + (avg >= 4 ? 'badge-completed' : (avg >= 3 ? 'badge-medium' : 'badge-high'));
            } else {
                scoreDisplay.textContent = 'Select both ratings';
                scoreDisplay.className = 'badge badge-archived';
            }
        }
    }

    cleanInputs.forEach(i => i.addEventListener('change', calculateOverall));
    condInputs.forEach(i => i.addEventListener('change', calculateOverall));
    calculateOverall();

    // 3. Confirm Delete Dialog Helper
    window.confirmAction = function (message, callbackUrl) {
        if (confirm(message)) {
            window.location.href = callbackUrl;
        }
    };
});
