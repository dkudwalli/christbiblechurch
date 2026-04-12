<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>
<section class="page-hero">
    <div class="wrap">
        <?php while (have_posts()) : the_post(); ?>
            <p class="eyebrow"><?php esc_html_e('Welcome', 'church-theme'); ?></p>
            <h1><?php the_title(); ?></h1>
        <?php endwhile; ?>
        <?php rewind_posts(); ?>
    </div>
</section>

<section class="section">
    <div class="wrap prose">
        <?php while (have_posts()) : the_post(); ?>
            <?php the_content(); ?>
        <?php endwhile; ?>
    </div>
</section>
<?php
get_footer();
