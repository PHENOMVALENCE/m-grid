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
  </div>
    <?php
} elseif (in_array($mgrid_layout, ['user', 'admin'], true)) {
    ?>
        </div><!-- /.container-fluid -->
      </div><!-- /.body-wrapper-inner -->
    </div><!-- /.body-wrapper -->
  </div><!-- /.page-wrapper -->
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
<?php if ($dashScripts): ?>
  <script src="<?= e(asset('js/sidebarmenu.js')) ?>"></script>
  <script src="<?= e(asset('js/app.min.js')) ?>"></script>
  <script src="<?= e(asset('libs/simplebar/dist/simplebar.js')) ?>"></script>
<?php endif; ?>
<?php if (!$publicVanilla): ?>
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
<?php endif; ?>
</body>

</html>
