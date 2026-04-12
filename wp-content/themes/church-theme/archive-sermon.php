<?php
if (! defined('ABSPATH')) {
    exit;
}

get_header();

$current_search = isset($_GET['s']) ? sanitize_text_field(wp_unslash((string) $_GET['s'])) : '';
$current_speaker = isset($_GET['speaker']) ? sanitize_title(wp_unslash((string) $_GET['speaker'])) : '';
$speakers = get_terms([
    'taxonomy' => 'speaker',
    'hide_empty' => true,
]);

if (is_wp_error($speakers)) {
    $speakers = [];
}
?>
<section class="page-hero">
    <div class="wrap">
        <p class="eyebrow"><?php esc_html_e('Sermons', 'church-theme'); ?></p>
        <h1><?php post_type_archive_title(); ?></h1>
        <p class="page-hero__summary"><?php esc_html_e('Browse recent teaching by topic, preacher, or keyword.', 'church-theme'); ?></p>
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
                <p><?php esc_html_e('Try a different speaker or search term.', 'church-theme'); ?></p>
            </article>
        <?php endif; ?>
    </div>

    <div class="wrap pagination-wrap">
        <?php
        the_posts_pagination([
            'base' => add_query_arg('paged', '%#%', church_theme_get_sermon_archive_url()),
            'format' => '',
            'mid_size' => 1,
            'prev_text' => __('Previous', 'church-theme'),
            'next_text' => __('Next', 'church-theme'),
            'add_args' => array_filter([
                's' => $current_search,
                'speaker' => $current_speaker,
            ]),
        ]);
        ?>
    </div>
</section>
<?php
get_footer();
