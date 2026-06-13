<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main-content"><?php esc_html_e('Skip to content', 'church-theme'); ?></a>
<header class="site-header">
    <?php
    $brand_logo = church_theme_get_brand_logo_asset();
    ?>
    <div class="wrap site-header__inner">
        <a class="site-brand" href="<?php echo esc_url(home_url('/')); ?>">
            <?php
            echo church_theme_render_static_image($brand_logo, [
                'class' => 'site-brand__mark',
                'loading' => 'eager',
                'decoding' => 'sync',
                'fetchpriority' => 'high',
                'sizes' => '(max-width: 720px) 150px, 180px',
            ]);
            ?>
        </a>

        <button class="site-nav__toggle" type="button" data-nav-toggle aria-expanded="false" aria-controls="primary-menu">
            <span class="site-nav__icon" aria-hidden="true"><span></span><span></span><span></span></span>
            <span class="screen-reader-text"><?php esc_html_e('Menu', 'church-theme'); ?></span>
        </button>

        <nav class="site-nav" data-nav aria-label="<?php esc_attr_e('Primary navigation', 'church-theme'); ?>">
            <?php
            wp_nav_menu([
                'theme_location' => 'primary',
                'container' => false,
                'menu_id' => 'primary-menu',
                'menu_class' => 'site-nav__list',
                'fallback_cb' => 'church_theme_fallback_menu',
            ]);
            ?>
        </nav>
    </div>
</header>
<main id="main-content" class="site-main" tabindex="-1">
