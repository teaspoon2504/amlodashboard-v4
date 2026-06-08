    </div> <!-- End .main-area -->
</div> <!-- End #app -->

<!-- Global JS Scripts -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sidebar nav active state logic
        document.querySelectorAll('.nav-item').forEach(item => {
            if (item.href === window.location.href) {
                item.classList.add('active');
            }
        });
        
        // Form resubmission prevention
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    });
</script>

<!-- Page Specific JS -->
<?php if (isset($page_js)): ?>
<script src="<?= htmlspecialchars($page_js, ENT_QUOTES, 'UTF-8') ?>"></script>
<?php endif; ?>

</body>
</html>
