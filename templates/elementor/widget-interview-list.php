<?php
/**
 * Interview List Widget Template
 * 
 * Template for the AI interview topics list widget.
 * 
 * @package AIInterviewWidget
 * @since 2.0.0
 * 
 * Available variables:
 * @var string $widget_title
 * @var string $widget_subtitle
 * @var array $topics_list
 * @var string $layout_style
 * @var string $columns
 * @var bool $show_questions
 * @var string $primary_color
 * @var string $animation_type
 * @var string $wrapper_tag
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Set defaults
$widget_title = $widget_title ?? __('Interview Topics', 'ai-interview-widget');
$widget_subtitle = $widget_subtitle ?? __('Explore different areas you can ask about', 'ai-interview-widget');
$topics_list = $topics_list ?? [];
$layout_style = $layout_style ?? 'cards';
$columns = $columns ?? '3';
$show_questions = $show_questions ?? true;
$primary_color = $primary_color ?? '#007cba';
$animation_type = $animation_type ?? 'fadeIn';
$wrapper_tag = $wrapper_tag ?? 'div';

// Generate unique ID
$widget_id = 'aiw-list-widget-' . wp_generate_uuid4();

// CSS custom properties
$css_vars = [
    '--aiw-primary-color: ' . esc_attr($primary_color),
    '--aiw-columns: ' . esc_attr($columns),
];

$wrapper_classes = [
    'aiw-widget',
    'aiw-interview-list-widget',
    'aiw-layout-' . esc_attr($layout_style)
];

if ($layout_style === 'cards') {
    $wrapper_classes[] = 'aiw-columns-' . esc_attr($columns);
}

if ($animation_type && $animation_type !== 'none') {
    $wrapper_classes[] = 'aiw-animate';
    $wrapper_classes[] = 'aiw-animate-' . esc_attr($animation_type);
}

$wrapper_attributes = [
    'id' => $widget_id,
    'class' => implode(' ', $wrapper_classes),
    'style' => implode('; ', $css_vars),
    'data-layout' => esc_attr($layout_style),
];

$wrapper_tag = in_array($wrapper_tag, ['div', 'section', 'article']) ? $wrapper_tag : 'div';
?>

<<?php echo esc_html($wrapper_tag); ?> <?php echo build_attributes($wrapper_attributes); ?>>
    
    <?php if ($widget_title || $widget_subtitle): ?>
    <div class="aiw-header">
        <?php if ($widget_title): ?>
            <h3 class="aiw-title"><?php echo esc_html($widget_title); ?></h3>
        <?php endif; ?>
        
        <?php if ($widget_subtitle): ?>
            <p class="aiw-subtitle"><?php echo esc_html($widget_subtitle); ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="aiw-topics-container">
        <?php if (!empty($topics_list)): ?>
            <?php foreach ($topics_list as $index => $topic): ?>
                <?php
                $topic_id = $widget_id . '-topic-' . $index;
                $topic_classes = ['aiw-topic-item'];
                
                if ($layout_style === 'cards') {
                    $topic_classes[] = 'aiw-topic-card';
                }
                
                $delay_class = 'aiw-delay-' . min(($index % 5) + 1, 5);
                if ($animation_type && $animation_type !== 'none') {
                    $topic_classes[] = $delay_class;
                }
                ?>
                
                <div class="<?php echo esc_attr(implode(' ', $topic_classes)); ?>" id="<?php echo esc_attr($topic_id); ?>">
                    
                    <?php if (!empty($topic['topic_icon']['value'])): ?>
                    <div class="aiw-topic-icon">
                        <i class="<?php echo esc_attr($topic['topic_icon']['value']); ?>" aria-hidden="true"></i>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($topic['topic_title'])): ?>
                    <h4 class="aiw-topic-title">
                        <?php echo esc_html($topic['topic_title']); ?>
                    </h4>
                    <?php endif; ?>
                    
                    <?php if (!empty($topic['topic_description'])): ?>
                    <div class="aiw-topic-description">
                        <p><?php echo esc_html($topic['topic_description']); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($show_questions && !empty($topic['topic_questions'])): ?>
                    <div class="aiw-topic-questions">
                        <strong><?php esc_html_e('Sample Questions:', 'ai-interview-widget'); ?></strong>
                        <div class="aiw-questions-list">
                            <?php
                            $questions = explode("\n", $topic['topic_questions']);
                            $questions = array_filter(array_map('trim', $questions));
                            ?>
                            <?php if (!empty($questions)): ?>
                                <ul class="aiw-questions-ul">
                                    <?php foreach ($questions as $question): ?>
                                        <?php if (!empty($question)): ?>
                                            <li><?php echo esc_html($question); ?></li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($layout_style === 'cards'): ?>
                    <div class="aiw-topic-action">
                        <button type="button" class="aiw-topic-button" data-topic="<?php echo esc_attr($topic['topic_title']); ?>">
                            <?php esc_html_e('Ask About This', 'ai-interview-widget'); ?>
                        </button>
                    </div>
                    <?php endif; ?>
                    
                </div>
                
            <?php endforeach; ?>
        <?php else: ?>
            <div class="aiw-no-topics">
                <p><?php esc_html_e('No topics configured yet.', 'ai-interview-widget'); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($layout_style === 'list' || $layout_style === 'accordion'): ?>
    <div class="aiw-footer">
        <div class="aiw-topics-summary">
            <small>
                <?php 
                printf(
                    esc_html(_n('%d topic available', '%d topics available', count($topics_list), 'ai-interview-widget')),
                    count($topics_list)
                );
                ?>
            </small>
        </div>
    </div>
    <?php endif; ?>

</<?php echo esc_html($wrapper_tag); ?>>

<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
        <?php if (!empty($topics_list)): ?>
            <?php $schema_items = []; ?>
            <?php foreach ($topics_list as $topic): ?>
                <?php if (!empty($topic['topic_title']) && !empty($topic['topic_description'])): ?>
                    <?php 
                    $schema_items[] = json_encode([
                        "@type" => "Question",
                        "name" => $topic['topic_title'],
                        "acceptedAnswer" => [
                            "@type" => "Answer",
                            "text" => $topic['topic_description']
                        ]
                    ], JSON_UNESCAPED_UNICODE);
                    ?>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php echo implode(',', $schema_items); ?>
        <?php endif; ?>
    ]
}
</script>

<?php
// Add some inline CSS for layout-specific styling
if ($layout_style === 'cards' && $columns): ?>
<style>
#<?php echo esc_attr($widget_id); ?> .aiw-topics-container {
    display: grid;
    grid-template-columns: repeat(<?php echo esc_attr($columns); ?>, 1fr);
    gap: var(--aiw-spacing-md, 16px);
}

@media (max-width: 768px) {
    #<?php echo esc_attr($widget_id); ?> .aiw-topics-container {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    #<?php echo esc_attr($widget_id); ?> .aiw-topics-container {
        grid-template-columns: 1fr;
    }
}
</style>
<?php endif; ?>