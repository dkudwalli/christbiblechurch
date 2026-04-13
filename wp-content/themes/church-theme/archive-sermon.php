<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

$current_search = isset($_GET['s']) ? sanitize_text_field(wp_unslash((string) $_GET['s'])) : '';
$current_speaker = church_theme_get_sermon_active_term_slug('speaker');
$current_series = church_theme_get_sermon_active_term_slug('series');
$speakers = get_terms([
    'taxonomy' => 'speaker',
    'hide_empty' => true,
    'orderby' => 'name',
    'order' => 'ASC',
]);
$series_terms = get_terms([
    'taxonomy' => 'series',
    'hide_empty' => true,
    'orderby' => 'name',
    'order' => 'ASC',
]);
$archive_context = church_theme_get_sermon_archive_context();
$pagination_base = str_replace('999999999', '%#%', esc_url(get_pagenum_link(999999999)));

if (is_wp_error($speakers)) {
    $speakers = [];
}

if (is_wp_error($series_terms)) {
    $series_terms = [];
}
?>
<section class="page-hero">
    <div class="wrap">
        <p class="eyebrow"><?php echo esc_html($archive_context['eyebrow']); ?></p>
        <h1><?php echo esc_html($archive_context['title']); ?></h1>
        <p class="page-hero__summary"><?php echo esc_html($archive_context['summary']); ?></p>
    </div>
</section>

<section class="section section--muted">
    <div class="wrap">
        <form class="filter-bar" method="get" action="<?php echo esc_url(church_theme_get_sermon_archive_url()); ?>">
            <label>
                <span class="screen-reader-text"><?php esc_html_e('Search sermons', 'church-theme'); ?></span>
                <input type="search" name="s" value="<?php echo esc_attr($current_search); ?>" placeholder="<?php esc_attr_e('Search sermons', 'church-theme'); ?>">
            </label>

            <label>
                <span class="screen-reader-text"><?php esc_html_e('Filter by speaker', 'church-theme'); ?></span>
                <select name="speaker">
                    <option value=""><?php esc_html_e('All speakers', 'church-theme'); ?></option>
                    <?php foreach ($speakers as $speaker) : ?>
                        <option value="<?php echo esc_attr($speaker->slug); ?>" <?php selected($current_speaker, $speaker->slug); ?>>
                            <?php echo esc_html($speaker->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                <span class="screen-reader-text"><?php esc_html_e('Filter by series', 'church-theme'); ?></span>
                <select name="series">
                    <option value=""><?php esc_html_e('All series', 'church-theme'); ?></option>
                    <?php foreach ($series_terms as $series_term) : ?>
                        <option value="<?php echo esc_attr($series_term->slug); ?>" <?php selected($current_series, $series_term->slug); ?>>
                            <?php echo esc_html($series_term->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <button class="button button--secondary" type="submit"><?php esc_html_e('Filter', 'church-theme'); ?></button>
        </form>
    </div>
</section>

<section class="section">
    <div class="wrap sermon-grid">
        <?php if (have_posts()) : ?>
            <?php while (have_posts()) : the_post(); ?>
                <?php get_template_part('template-parts/sermon', 'card'); ?>
            <?php endwhile; ?>
        <?php else : ?>
            <article class="card">
                <h2><?php esc_html_e('No sermons found.', 'church-theme'); ?></h2>
                <p><?php esc_html_e('Try a different series, speaker, or search term.', 'church-theme'); ?></p>
            </article>
        <?php endif; ?>
    </div>

    <div class="wrap pagination-wrap">
        <?php
        the_posts_pagination([
            'base' => $pagination_base,
            'format' => '',
            'mid_size' => 1,
            'prev_text' => __('Previous', 'church-theme'),
            'next_text' => __('Next', 'church-theme'),
        ]);
        ?>
    </div>
</section>
<?php
get_footer();
