<?php
if (! defined('ABSPATH')) {
    exit;
}

$section = $args['section'] ?? null;
$page_slug = (string) ($args['page_slug'] ?? '');
$index = (int) ($args['index'] ?? 0);

if (! $section instanceof WP_Post || $page_slug === '') {
    return;
}

$section_slug = church_theme_get_section_anchor($section);
$section_media = church_theme_get_section_media($page_slug, $section_slug);
$elder_board = $section_slug === 'elder-board' ? church_theme_get_elder_board_cards($section, $section_media) : [];
$has_elder_cards = ($elder_board['cards'] ?? []) !== [];
?>
<section id="<?php echo esc_attr($section_slug); ?>" class="section<?php echo $index % 2 === 1 ? ' section--muted' : ''; ?>">
    <div class="wrap section-layout">
        <div class="section-heading">
            <h2><?php echo esc_html(get_the_title($section)); ?></h2>
        </div>

        <?php if ($has_elder_cards) : ?>
            <?php if (($elder_board['intro'] ?? '') !== '') : ?>
                <div class="elder-board-intro prose prose--wide">
                    <?php echo wp_kses_post((string) $elder_board['intro']); ?>
                </div>
            <?php endif; ?>

            <div class="elder-board-grid">
                <?php foreach ($elder_board['cards'] as $card) : ?>
                    <?php $image = is_array($card['image'] ?? null) ? $card['image'] : null; ?>
                    <article class="card elder-card">
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
                            <?php if (($card['family'] ?? '') !== '') : ?>
                                <p class="elder-card__family"><?php echo esc_html((string) $card['family']); ?></p>
                            <?php endif; ?>

                            <h3><?php echo esc_html((string) $card['name']); ?></h3>

                            <div class="prose prose--compact elder-card__content">
                                <?php echo wp_kses_post((string) $card['content']); ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php elseif (($section_media['layout'] ?? '') === 'gallery') : ?>
            <div class="section-media-grid">
                <?php foreach (($section_media['items'] ?? []) as $item) : ?>
                    <?php if (! is_array($item)) : ?>
                        <?php continue; ?>
                    <?php endif; ?>
                    <figure class="card person-card">
                        <?php
                        echo church_theme_render_static_image($item, [
                            'class' => 'person-card__media',
                            'sizes' => '(max-width: 720px) 100vw, (max-width: 1120px) 50vw, 33vw',
                            'loading' => 'lazy',
                        ]);
                        ?>
                        <figcaption class="person-card__caption"><?php echo esc_html((string) $item['caption']); ?></figcaption>
                    </figure>
                <?php endforeach; ?>
            </div>

            <article class="card section-card prose prose--wide">
                <?php echo apply_filters('the_content', $section->post_content); ?>
            </article>
        <?php elseif (($section_media['layout'] ?? '') === 'feature' && is_array($section_media['item'] ?? null)) : ?>
            <div class="section-story">
                <figure class="card section-visual">
                    <?php
                    echo church_theme_render_static_image($section_media['item'], [
                        'sizes' => '(max-width: 960px) 100vw, 42vw',
                        'loading' => 'lazy',
                    ]);
                    ?>
                </figure>

                <article class="card section-card prose prose--wide">
                    <?php echo apply_filters('the_content', $section->post_content); ?>
                </article>
            </div>
        <?php else : ?>
            <article class="card section-card prose prose--wide">
                <?php echo apply_filters('the_content', $section->post_content); ?>
            </article>
        <?php endif; ?>
    </div>
</section>
