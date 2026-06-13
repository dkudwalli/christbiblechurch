<?php
if (! defined('ABSPATH')) {
    exit;
}

$section = $args['section'] ?? null;
$index = (int) ($args['index'] ?? 0);

if (! $section instanceof WP_Post) {
    return;
}

$section_slug = church_theme_get_section_anchor($section);
$section_layout = church_theme_get_page_section_layout($section);
$section_profiles = $section_layout === 'elder_board' ? church_theme_get_page_section_profiles($section) : [];
$section_image = $section_layout === 'feature' ? church_theme_get_page_section_featured_image($section) : null;
$has_elder_cards = $section_profiles !== [];
$section_content = apply_filters('the_content', $section->post_content);
?>
<section id="<?php echo esc_attr($section_slug); ?>" class="section<?php echo $index % 2 === 1 ? ' section--muted' : ''; ?>">
    <div class="wrap section-layout">
        <div class="section-heading reveal">
            <h2><?php echo esc_html(get_the_title($section)); ?></h2>
        </div>

        <?php if ($has_elder_cards) : ?>
            <?php if (trim(wp_strip_all_tags($section->post_content)) !== '') : ?>
                <div class="elder-board-intro prose prose--wide">
                    <?php echo $section_content; ?>
                </div>
            <?php endif; ?>

            <div class="elder-board-grid reveal-stagger">
                <?php foreach ($section_profiles as $profile) : ?>
                    <?php
                    $image = church_theme_get_attachment_image_asset((int) ($profile['image_id'] ?? 0));
                    $profile_content = church_theme_render_rich_text_fragment((string) ($profile['content'] ?? ''));
                    ?>
                    <article class="card elder-card reveal">
                        <div class="elder-card__media-frame">
                            <?php
                            echo church_theme_render_static_image($image, [
                                'class' => 'elder-card__media',
                                'sizes' => '(max-width: 767px) 100vw, 280px',
                                'loading' => 'lazy',
                            ]);
                            ?>
                        </div>

                        <div class="elder-card__body">
                            <?php if (($profile['family'] ?? '') !== '') : ?>
                                <p class="elder-card__family"><?php echo esc_html((string) $profile['family']); ?></p>
                            <?php endif; ?>

                            <h3><?php echo esc_html((string) ($profile['name'] ?? '')); ?></h3>

                            <?php if ($profile_content !== '') : ?>
                                <div class="prose prose--compact elder-card__content">
                                    <?php echo $profile_content; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php elseif ($section_layout === 'feature' && is_array($section_image)) : ?>
            <div class="section-story reveal">
                <figure class="card section-visual">
                    <?php
                    echo church_theme_render_static_image($section_image, [
                        'sizes' => '(max-width: 960px) 100vw, 42vw',
                        'loading' => 'lazy',
                    ]);
                    ?>
                </figure>

                <article class="card section-card prose prose--wide">
                    <?php echo $section_content; ?>
                </article>
            </div>
        <?php else : ?>
            <article class="card section-card prose prose--wide reveal">
                <?php echo $section_content; ?>
            </article>
        <?php endif; ?>
    </div>
</section>
