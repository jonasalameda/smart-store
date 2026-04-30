<?php
/**
 * App theme overrides — load after Bootstrap. Paths relative to /public/assets/.
 * Jaldi matches the fridge dashboard typography (see dashboard.css).
 */
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Jaldi:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= hs(public_asset_href('css/layout/customer.css')) ?>">
<link rel="stylesheet" href="<?= hs(public_asset_href('css/theme.css')) ?>">
