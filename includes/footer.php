<?php
// ============================================================================
// includes/footer.php - Global Footer Template
// Etalase Digital Stella - SMK Telkom Lampung
// ============================================================================

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$isSubfolder = (strpos($scriptName, '/admin/') !== false || strpos($scriptName, '/auth/') !== false);
$baseUrl = $isSubfolder ? '../' : '';
?>
    </div> <!-- /.container -->
</main> <!-- /.main-content -->

<footer class="site-footer">
    <div class="container footer-container">
        <div class="footer-top">
            <div class="footer-brand">
                <div class="brand-icon" style="width: 32px; height: 32px; font-size: 0.95rem;">
                    <i class="fa-solid fa-code"></i>
                </div>
                <div>
                    <div>Etalase Digital Stella</div>
                    <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 500;">SMK Telkom Lampung</div>
                </div>
            </div>
            <div class="footer-signature" style="font-size: 0.88rem; color: #cbd5e1;">
                <i class="fa-solid fa-graduation-cap text-primary" style="color: var(--color-primary); margin-right: 0.35rem;"></i>
                Ahmad Fadhil Anwar &bull; Kelas XI Rekayasa Perangkat Lunak
            </div>
        </div>
        <div class="footer-bottom">
            <div>&copy; <?= date('Y') ?> Etalase Digital Stella. Dibuat dengan PHP Native, PDO MySQL, &amp; Vanilla CSS Modern.</div>
            <div style="color: #64748b;">Tugas Pemrograman Web</div>
        </div>
    </div>
</footer>

<!-- Custom JavaScript -->
<script src="<?= $baseUrl ?>assets/js/script.js"></script>
</body>
</html>
