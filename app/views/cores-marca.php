<?php /* Cores da marca como variáveis CSS. Espera $branding carregado. */ ?>
<style>
    :root {
        --primary: <?= htmlspecialchars($branding['colors']['primary']) ?>;
        --primary-dark: <?= htmlspecialchars($branding['colors']['primary_dark']) ?>;
        --background: <?= htmlspecialchars($branding['colors']['background']) ?>;
        --surface: <?= htmlspecialchars($branding['colors']['surface']) ?>;
        --text: <?= htmlspecialchars($branding['colors']['text']) ?>;
        --muted: <?= htmlspecialchars($branding['colors']['muted']) ?>;
    }
</style>
