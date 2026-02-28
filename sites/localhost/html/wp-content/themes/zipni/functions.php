<?php
/**
 * Zipni theme functions.
 *
 * @package zipni
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue custom styles for blocks not covered by theme.json.
 */
function zipni_enqueue_styles() {
	wp_enqueue_style(
		'zipni-custom',
		get_theme_file_uri( 'assets/custom.css' ),
		array(),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'zipni_enqueue_styles' );

/**
 * Hide admin bar on mobile for CTA button visibility.
 */
function zipni_hide_on_mobile_css() {
	echo '<style>@media(max-width:767px){.hide-on-mobile{display:none!important}}</style>';
}
add_action( 'wp_head', 'zipni_hide_on_mobile_css' );

/**
 * Register block patterns category.
 */
function zipni_register_pattern_categories() {
	register_block_pattern_category( 'zipni', array(
		'label' => __( 'Zipni', 'zipni' ),
	) );
}
add_action( 'init', 'zipni_register_pattern_categories' );

/**
 * Portfolio before/after carousel shortcode.
 * Renders a main before/after display + scrollable thumbnail strip.
 * Thumbnails are shuffled on each page load (server-side).
 */
function zipni_portfolio_carousel_shortcode() {
	$base = content_url( '/uploads/2026/02/' );

	$items = array(
		array( 'id' => '10401931',  'before' => $base . '10401931_before.jpg',  'after' => $base . '10401931_after-1.jpg',  'thumb' => $base . '10401931_thumb.jpg' ),
		array( 'id' => '11047827',  'before' => $base . '11047827_before-1.jpg', 'after' => $base . '11047827_after-1.jpg',  'thumb' => $base . '11047827_thumb.jpg' ),
		array( 'id' => '11360127',  'before' => $base . '11360127_before.jpg',  'after' => $base . '11360127_after.jpg',  'thumb' => $base . '11360127_thumb.jpg' ),
		array( 'id' => '132517220', 'before' => $base . '132517220_before.jpg', 'after' => $base . '132517220_after-1.jpg', 'thumb' => $base . '132517220_thumb.jpg' ),
		array( 'id' => '152025230', 'before' => $base . '152025230_before.jpg', 'after' => $base . '152025230_after.jpg', 'thumb' => $base . '152025230_thumb.jpg' ),
		array( 'id' => '172049588', 'before' => $base . '172049588_before.jpg', 'after' => $base . '172049588_after.jpg', 'thumb' => $base . '172049588_thumb.jpg' ),
		array( 'id' => '178284906', 'before' => $base . '178284906_before.jpg', 'after' => $base . '178284906_after.jpg', 'thumb' => $base . '178284906_thumb.jpg' ),
		array( 'id' => '188281665', 'before' => $base . '188281665_before.jpg', 'after' => $base . '188281665_after.jpg', 'thumb' => $base . '188281665_thumb.jpg' ),
		array( 'id' => '19952515',  'before' => $base . '19952515_before.jpg',  'after' => $base . '19952515_after.jpg',  'thumb' => $base . '19952515_thumb.jpg' ),
		array( 'id' => '28977402',  'before' => $base . '28977402_before.jpg',  'after' => $base . '28977402_after.jpg',  'thumb' => $base . '28977402_thumb.jpg' ),
		array( 'id' => '4962700',   'before' => $base . '4962700_before.jpg',   'after' => $base . '4962700_after.jpg',   'thumb' => $base . '4962700_thumb.jpg' ),
	);

	shuffle( $items );
	$first = $items[0];

	ob_start();
	?>
	<div class="zipni-carousel" style="max-width:1152px;margin:0 auto">
		<div class="zipni-carousel__main" style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-bottom:2rem">
			<div style="background:#fff;border-radius:1rem;padding:1.5rem;text-align:center">
				<h3 style="color:var(--wp--preset--color--text-secondary);font-size:var(--wp--preset--font-size--lg);margin-bottom:1rem">До — белый фон</h3>
				<img id="zipni-before" src="<?php echo esc_url( $first['before'] ); ?>" alt="Товар на белом фоне" style="width:100%;border-radius:0.5rem;aspect-ratio:1/1;object-fit:contain;background:#fafafa">
			</div>
			<div style="background:#fff;border:2px solid var(--wp--preset--color--accent);border-radius:1rem;padding:1.5rem;text-align:center">
				<h3 style="color:var(--wp--preset--color--accent);font-size:var(--wp--preset--font-size--lg);margin-bottom:1rem">После — в интерьере</h3>
				<img id="zipni-after" src="<?php echo esc_url( $first['after'] ); ?>" alt="Товар в реальном интерьере" style="width:100%;border-radius:0.5rem;aspect-ratio:1/1;object-fit:cover">
			</div>
		</div>
		<div class="zipni-carousel__thumbs" style="display:flex;gap:0.75rem;overflow-x:auto;padding:0.5rem 0;scrollbar-width:thin;justify-content:center">
			<?php foreach ( $items as $i => $item ) : ?>
			<button
				class="zipni-thumb<?php echo $i === 0 ? ' zipni-thumb--active' : ''; ?>"
				data-before="<?php echo esc_url( $item['before'] ); ?>"
				data-after="<?php echo esc_url( $item['after'] ); ?>"
				style="flex-shrink:0;width:80px;height:80px;border-radius:0.75rem;overflow:hidden;border:2px solid <?php echo $i === 0 ? 'var(--wp--preset--color--accent)' : 'transparent'; ?>;padding:0;cursor:pointer;background:none;transition:border-color 0.2s"
			>
				<img src="<?php echo esc_url( $item['thumb'] ); ?>" alt="" style="width:100%;height:100%;object-fit:cover">
			</button>
			<?php endforeach; ?>
		</div>
	</div>
	<script>
	(function(){
		var thumbs = document.querySelectorAll('.zipni-thumb');
		var before = document.getElementById('zipni-before');
		var after = document.getElementById('zipni-after');
		thumbs.forEach(function(btn){
			btn.addEventListener('click', function(){
				before.src = btn.dataset.before;
				after.src = btn.dataset.after;
				thumbs.forEach(function(t){ t.style.borderColor = 'transparent'; t.classList.remove('zipni-thumb--active'); });
				btn.style.borderColor = 'var(--wp--preset--color--accent)';
				btn.classList.add('zipni-thumb--active');
			});
		});
	})();
	</script>
	<?php
	return ob_get_clean();
}
add_shortcode( 'zipni_portfolio_carousel', 'zipni_portfolio_carousel_shortcode' );
