<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();
?>
<section class="page-hero">
    <div class="wrap">
        <h1><?php bloginfo('name'); ?></h1>
    </div>
</section>

<section class="section">
    <div class="wrap sermon-grid">
        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>
                <article class="card">
                    <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                    <p><?php echo esc_html(church_theme_get_post_preview(get_the_ID(), 24)); ?></p>
                </article>
            <?php endwhile; ?>
        <?php else : ?>
            <article class="card">
                <p><?php esc_html_e('No content found.', 'church-theme'); ?></p>
            </article>
        <?php endif; ?>
    </div>
</section>
<?php
get_footer();
