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
<header class="site-header">
    <div class="wrap site-header__inner">
        <a class="site-brand" href="<?php echo esc_url(home_url('/')); ?>">
            <img
                class="site-brand__mark"
                src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/logo-fav.svg'); ?>"
                alt="<?php esc_attr_e('Christ Bible Church logo', 'church-theme'); ?>"
                width="44"
                height="44">
            <span class="site-brand__text">
                <span class="site-brand__name"><?php bloginfo('name'); ?></span>
                <span class="site-brand__tagline"><?php bloginfo('description'); ?></span>
            </span>
        </a>

        <button class="site-nav__toggle" type="button" data-nav-toggle aria-expanded="false" aria-controls="primary-menu">
            <span>Menu</span>
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
<main class="site-main">
