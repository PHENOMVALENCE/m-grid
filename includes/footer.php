<?php

declare(strict_types=1);

/**
 * Closes layout opened in header.php and loads scripts.
 */

if (!isset($mgrid_layout)) {
    $mgrid_layout = 'public';
}

if ($mgrid_layout === 'public') {
    require __DIR__ . '/public_footer.php';
} elseif ($mgrid_layout === 'auth') {
    ?>
      </div>
    </div>
    <?php
} elseif (in_array($mgrid_layout, ['user', 'admin'], true)) {
    ?>
    </div><!-- /.mgrid-content -->
  </div><!-- /.mgrid-main -->
    <?php
}

$dashScripts = in_array($mgrid_layout, ['user', 'admin'], true);
$publicVanilla = $mgrid_layout === 'public' && !empty($mgrid_public_vanilla);
?>
<?php if (!$publicVanilla): ?>
  <script src="<?= e(asset('libs/jquery/dist/jquery.min.js')) ?>"></script>
  <script src="<?= e(asset('libs/bootstrap/dist/js/bootstrap.bundle.min.js')) ?>"></script>
<?php endif; ?>
<script src="<?= e(asset('js/mgrid-i18n.js')) ?>"></script>
<script src="<?= e(asset('js/mgrid-core.js')) ?>"></script>
<script src="<?= e(asset('js/mgrid-ui.js')) ?>"></script>
<?php if ($dashScripts): ?>
  <script src="<?= e(asset('js/sidebarmenu.js')) ?>"></script>
<?php endif; ?>
<?php if (!$publicVanilla): ?>
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
<?php endif; ?>
<?php
/** Extra HTML/scripts after jQuery (e.g. DataTables on admin pages). Set `$mgrid_footer_extra` before including this file. */
$mgrid_footer_extra = $mgrid_footer_extra ?? '';
if ($mgrid_footer_extra !== '' && is_string($mgrid_footer_extra)) {
    echo $mgrid_footer_extra;
}
?>
</body>

</html>
