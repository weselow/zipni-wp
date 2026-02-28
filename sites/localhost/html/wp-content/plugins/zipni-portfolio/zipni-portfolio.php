<?php
/**
 * Plugin Name: Zipni Portfolio
 * Description: CPT portfolio с before/after генерациями, таксономиями, REST API и серверными блоками.
 * Version:     1.1.0
 * Author:      Zipni
 * Text Domain: zipni-portfolio
 *
 * @package zipni-portfolio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ───────────────────────────────────────────────
 * CPT + Taxonomies + Meta
 * ─────────────────────────────────────────────── */

function zipni_portfolio_register_post_type() {
	register_post_type( 'portfolio', array(
		'labels'       => array(
			'name'               => 'Portfolio',
			'singular_name'      => 'Portfolio',
			'add_new'            => 'Добавить',
			'add_new_item'       => 'Добавить запись portfolio',
			'edit_item'          => 'Редактировать',
			'new_item'           => 'Новая запись',
			'view_item'          => 'Смотреть',
			'search_items'       => 'Искать',
			'not_found'          => 'Не найдено',
			'not_found_in_trash' => 'В корзине пусто',
			'menu_name'          => 'Portfolio',
		),
		'public'       => true,
		'has_archive'  => true,
		'rewrite'      => array( 'slug' => 'portfolio', 'with_front' => false ),
		'show_in_rest' => true,
		'rest_base'    => 'portfolio',
		'menu_icon'    => 'dashicons-format-gallery',
		'supports'     => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
	) );
}
add_action( 'init', 'zipni_portfolio_register_post_type' );

function zipni_portfolio_register_taxonomy_category() {
	register_taxonomy( 'portfolio_category', 'portfolio', array(
		'labels'       => array(
			'name'          => 'Категории товаров',
			'singular_name' => 'Категория',
			'add_new_item'  => 'Добавить категорию',
		),
		'hierarchical' => true,
		'public'       => true,
		'rewrite'      => array( 'slug' => 'portfolio-category', 'with_front' => false ),
		'show_in_rest' => true,
		'rest_base'    => 'portfolio-category',
	) );
}
add_action( 'init', 'zipni_portfolio_register_taxonomy_category' );

function zipni_portfolio_register_taxonomy_marketplace() {
	register_taxonomy( 'marketplace', 'portfolio', array(
		'labels'       => array(
			'name'          => 'Маркетплейсы',
			'singular_name' => 'Маркетплейс',
			'add_new_item'  => 'Добавить маркетплейс',
		),
		'hierarchical' => true,
		'public'       => true,
		'rewrite'      => array( 'slug' => 'marketplace', 'with_front' => false ),
		'show_in_rest' => true,
		'rest_base'    => 'marketplace',
	) );
}
add_action( 'init', 'zipni_portfolio_register_taxonomy_marketplace' );

function zipni_portfolio_register_meta() {
	$fields = array(
		'before_image_url', 'after_image_url',
		'before_alt', 'after_alt',
		'product_name', 'scene_description',
		'rank_math_title', 'rank_math_description', 'rank_math_focus_keyword',
	);

	foreach ( $fields as $key ) {
		register_post_meta( 'portfolio', $key, array(
			'type'          => 'string',
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
		) );
	}
}
add_action( 'init', 'zipni_portfolio_register_meta' );

/* ───────────────────────────────────────────────
 * Enqueue block styles (frontend only)
 * ─────────────────────────────────────────────── */

function zipni_portfolio_enqueue_styles() {
	if ( is_singular( 'portfolio' ) || is_post_type_archive( 'portfolio' ) || is_tax( 'portfolio_category' ) || is_tax( 'marketplace' ) ) {
		wp_enqueue_style(
			'zipni-portfolio',
			plugin_dir_url( __FILE__ ) . 'assets/portfolio.css',
			array(),
			'1.1.0'
		);
	}
}
add_action( 'wp_enqueue_scripts', 'zipni_portfolio_enqueue_styles' );

/**
 * Also enqueue on any page that uses the carousel shortcode/block.
 */
function zipni_portfolio_enqueue_carousel_styles() {
	global $post;
	if ( $post && has_shortcode( $post->post_content, 'zipni_portfolio_carousel' ) ) {
		wp_enqueue_style(
			'zipni-portfolio',
			plugin_dir_url( __FILE__ ) . 'assets/portfolio.css',
			array(),
			'1.1.0'
		);
	}
}
add_action( 'wp_enqueue_scripts', 'zipni_portfolio_enqueue_carousel_styles' );

/* ───────────────────────────────────────────────
 * Server-side blocks
 * ─────────────────────────────────────────────── */

function zipni_portfolio_register_blocks() {
	register_block_type( 'zipni/portfolio-single', array(
		'render_callback' => 'zipni_render_portfolio_single',
	) );

	register_block_type( 'zipni/portfolio-archive', array(
		'attributes'      => array(
			'perPage' => array( 'type' => 'number', 'default' => 12 ),
		),
		'render_callback' => 'zipni_render_portfolio_archive',
	) );
}
add_action( 'init', 'zipni_portfolio_register_blocks' );

/* ─── Block: portfolio-single ─── */

function zipni_render_portfolio_single( $attributes, $content ) {
	$post_id = get_the_ID();
	if ( ! $post_id || get_post_type( $post_id ) !== 'portfolio' ) {
		return '';
	}

	$before_url  = get_post_meta( $post_id, 'before_image_url', true );
	$after_url   = get_post_meta( $post_id, 'after_image_url', true );
	$before_alt  = esc_attr( get_post_meta( $post_id, 'before_alt', true ) );
	$after_alt   = esc_attr( get_post_meta( $post_id, 'after_alt', true ) );
	$description = wp_kses_post( get_post_meta( $post_id, 'scene_description', true ) );

	$categories  = get_the_terms( $post_id, 'portfolio_category' );
	$marketplaces = get_the_terms( $post_id, 'marketplace' );

	if ( ! $before_url && ! $after_url ) {
		return '';
	}

	ob_start();
	?>
	<div class="zipni-ba-grid">
		<?php if ( $before_url ) : ?>
		<div class="zipni-ba-card">
			<span class="zipni-ba-label">До — белый фон</span>
			<img src="<?php echo esc_url( $before_url ); ?>"
			     alt="<?php echo $before_alt ?: 'Товар на белом фоне'; ?>"
			     loading="lazy" width="600" height="600">
		</div>
		<?php endif; ?>
		<?php if ( $after_url ) : ?>
		<div class="zipni-ba-card zipni-ba-card--after">
			<span class="zipni-ba-label">После — в интерьере</span>
			<img src="<?php echo esc_url( $after_url ); ?>"
			     alt="<?php echo $after_alt ?: 'Товар в интерьере'; ?>"
			     loading="lazy" width="600" height="600">
		</div>
		<?php endif; ?>
	</div>

	<?php if ( $categories || $marketplaces ) : ?>
	<div class="zipni-portfolio-badges">
		<?php
		if ( $categories && ! is_wp_error( $categories ) ) {
			foreach ( $categories as $term ) {
				printf(
					'<a href="%s" class="zipni-badge zipni-badge--accent">%s</a>',
					esc_url( get_term_link( $term ) ),
					esc_html( $term->name )
				);
			}
		}
		if ( $marketplaces && ! is_wp_error( $marketplaces ) ) {
			foreach ( $marketplaces as $term ) {
				if ( $term->name !== 'Общий' ) {
					printf(
						'<a href="%s" class="zipni-badge">%s</a>',
						esc_url( get_term_link( $term ) ),
						esc_html( $term->name )
					);
				}
			}
		}
		?>
	</div>
	<?php endif; ?>

	<?php if ( $description ) : ?>
	<div class="zipni-portfolio-description">
		<?php echo $description; ?>
	</div>
	<?php endif; ?>

	<div class="zipni-portfolio-cta">
		<p>Хотите такое же фото для своего товара?</p>
		<a href="https://t.me/zipni_bot">Попробуйте на своём товаре</a>
	</div>
	<?php
	return ob_get_clean();
}

/* ─── Block: portfolio-archive ─── */

function zipni_render_portfolio_archive( $attributes, $content ) {
	$per_page = $attributes['perPage'] ?? 12;
	$paged    = max( 1, get_query_var( 'paged', 1 ) );

	// Category filter from URL or taxonomy archive
	$active_cat = '';
	if ( is_tax( 'portfolio_category' ) ) {
		$active_cat = get_queried_object()->slug ?? '';
	}

	$query_args = array(
		'post_type'      => 'portfolio',
		'posts_per_page' => $per_page,
		'paged'          => $paged,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

	if ( $active_cat ) {
		$query_args['tax_query'] = array( array(
			'taxonomy' => 'portfolio_category',
			'field'    => 'slug',
			'terms'    => $active_cat,
		) );
	}

	$query = new WP_Query( $query_args );

	// Get all categories for filters
	$all_cats = get_terms( array(
		'taxonomy'   => 'portfolio_category',
		'hide_empty' => true,
	) );

	ob_start();

	// Category filters
	if ( $all_cats && ! is_wp_error( $all_cats ) && count( $all_cats ) > 1 ) :
	?>
	<nav class="zipni-portfolio-filters" aria-label="Фильтр по категориям">
		<a href="<?php echo esc_url( get_post_type_archive_link( 'portfolio' ) ); ?>"
		   class="<?php echo $active_cat ? '' : 'is-active'; ?>">Все</a>
		<?php foreach ( $all_cats as $cat ) : ?>
		<a href="<?php echo esc_url( get_term_link( $cat ) ); ?>"
		   class="<?php echo $active_cat === $cat->slug ? 'is-active' : ''; ?>"><?php echo esc_html( $cat->name ); ?></a>
		<?php endforeach; ?>
	</nav>
	<?php endif; ?>

	<?php if ( $query->have_posts() ) : ?>
	<div class="zipni-portfolio-grid">
		<?php while ( $query->have_posts() ) : $query->the_post(); ?>
		<?php
			$after_url = get_post_meta( get_the_ID(), 'after_image_url', true );
			$after_alt = get_post_meta( get_the_ID(), 'after_alt', true );
			$cats      = get_the_terms( get_the_ID(), 'portfolio_category' );
			$cat_name  = ( $cats && ! is_wp_error( $cats ) ) ? $cats[0]->name : '';
		?>
		<a href="<?php the_permalink(); ?>" class="zipni-portfolio-card">
			<?php if ( $after_url ) : ?>
			<img src="<?php echo esc_url( $after_url ); ?>"
			     alt="<?php echo esc_attr( $after_alt ?: get_the_title() ); ?>"
			     loading="lazy" width="400" height="400">
			<?php endif; ?>
			<div class="zipni-portfolio-card__body">
				<h3 class="zipni-portfolio-card__title"><?php the_title(); ?></h3>
				<?php if ( $cat_name ) : ?>
				<span class="zipni-portfolio-card__badge"><?php echo esc_html( $cat_name ); ?></span>
				<?php endif; ?>
			</div>
		</a>
		<?php endwhile; ?>
	</div>

	<?php
	// Pagination
	$total_pages = $query->max_num_pages;
	if ( $total_pages > 1 ) :
		$pagination = paginate_links( array(
			'total'     => $total_pages,
			'current'   => $paged,
			'prev_text' => '&larr; Назад',
			'next_text' => 'Вперёд &rarr;',
			'type'      => 'array',
		) );
		if ( $pagination ) :
	?>
	<nav class="zipni-portfolio-pagination" aria-label="Навигация по страницам">
		<?php echo implode( "\n", $pagination ); ?>
	</nav>
	<?php
		endif;
	endif;
	?>

	<?php else : ?>
	<p>Пока нет работ в этой категории.</p>
	<?php endif; ?>

	<?php
	wp_reset_postdata();
	return ob_get_clean();
}

/* ───────────────────────────────────────────────
 * Dynamic carousel shortcode (replaces hardcoded)
 * ─────────────────────────────────────────────── */

function zipni_portfolio_carousel_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'count' => 12,
	), $atts, 'zipni_portfolio_carousel' );

	$query = new WP_Query( array(
		'post_type'      => 'portfolio',
		'posts_per_page' => intval( $atts['count'] ),
		'orderby'        => 'rand',
		'fields'         => 'ids',
	) );

	if ( ! $query->have_posts() ) {
		return zipni_portfolio_carousel_fallback();
	}

	$items = array();
	foreach ( $query->posts as $pid ) {
		$before = get_post_meta( $pid, 'before_image_url', true );
		$after  = get_post_meta( $pid, 'after_image_url', true );
		if ( $before && $after ) {
			$items[] = array(
				'id'         => $pid,
				'before'     => $before,
				'after'      => $after,
				'before_alt' => get_post_meta( $pid, 'before_alt', true ) ?: 'Товар на белом фоне',
				'after_alt'  => get_post_meta( $pid, 'after_alt', true ) ?: 'Товар в интерьере',
			);
		}
	}
	wp_reset_postdata();

	if ( empty( $items ) ) {
		return zipni_portfolio_carousel_fallback();
	}

	// Enqueue CSS
	wp_enqueue_style(
		'zipni-portfolio',
		plugin_dir_url( __FILE__ ) . 'assets/portfolio.css',
		array(),
		'1.1.0'
	);

	return zipni_portfolio_carousel_html( $items );
}
add_shortcode( 'zipni_portfolio_carousel', 'zipni_portfolio_carousel_shortcode' );

/**
 * Fallback carousel with hardcoded images (used when no portfolio posts exist yet).
 */
function zipni_portfolio_carousel_fallback() {
	$base = content_url( '/uploads/2026/02/' );

	$items = array(
		array( 'id' => '10401931',  'before' => $base . '10401931_before.jpg',  'after' => $base . '10401931_after-1.jpg',  'before_alt' => 'Товар на белом фоне', 'after_alt' => 'Товар в интерьере' ),
		array( 'id' => '11047827',  'before' => $base . '11047827_before-1.jpg', 'after' => $base . '11047827_after-1.jpg', 'before_alt' => 'Товар на белом фоне', 'after_alt' => 'Товар в интерьере' ),
		array( 'id' => '11360127',  'before' => $base . '11360127_before.jpg',  'after' => $base . '11360127_after.jpg',   'before_alt' => 'Товар на белом фоне', 'after_alt' => 'Товар в интерьере' ),
		array( 'id' => '132517220', 'before' => $base . '132517220_before.jpg', 'after' => $base . '132517220_after-1.jpg','before_alt' => 'Товар на белом фоне', 'after_alt' => 'Товар в интерьере' ),
		array( 'id' => '152025230', 'before' => $base . '152025230_before.jpg', 'after' => $base . '152025230_after.jpg',  'before_alt' => 'Товар на белом фоне', 'after_alt' => 'Товар в интерьере' ),
		array( 'id' => '172049588', 'before' => $base . '172049588_before.jpg', 'after' => $base . '172049588_after.jpg',  'before_alt' => 'Товар на белом фоне', 'after_alt' => 'Товар в интерьере' ),
		array( 'id' => '178284906', 'before' => $base . '178284906_before.jpg', 'after' => $base . '178284906_after.jpg',  'before_alt' => 'Товар на белом фоне', 'after_alt' => 'Товар в интерьере' ),
		array( 'id' => '188281665', 'before' => $base . '188281665_before.jpg', 'after' => $base . '188281665_after.jpg',  'before_alt' => 'Товар на белом фоне', 'after_alt' => 'Товар в интерьере' ),
		array( 'id' => '19952515',  'before' => $base . '19952515_before.jpg',  'after' => $base . '19952515_after.jpg',   'before_alt' => 'Товар на белом фоне', 'after_alt' => 'Товар в интерьере' ),
		array( 'id' => '28977402',  'before' => $base . '28977402_before.jpg',  'after' => $base . '28977402_after.jpg',   'before_alt' => 'Товар на белом фоне', 'after_alt' => 'Товар в интерьере' ),
		array( 'id' => '4962700',   'before' => $base . '4962700_before.jpg',   'after' => $base . '4962700_after.jpg',    'before_alt' => 'Товар на белом фоне', 'after_alt' => 'Товар в интерьере' ),
	);

	shuffle( $items );
	return zipni_portfolio_carousel_html( $items );
}

/**
 * Shared HTML renderer for the before/after carousel.
 */
function zipni_portfolio_carousel_html( $items ) {
	$first = $items[0];
	$uid   = 'zc-' . wp_unique_id();

	ob_start();
	?>
	<div class="zipni-carousel" id="<?php echo $uid; ?>" style="max-width:1152px;margin:0 auto">
		<div class="zipni-carousel__main" style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-bottom:2rem">
			<div class="zipni-ba-card">
				<span class="zipni-ba-label">До — белый фон</span>
				<img class="zipni-carousel__before" src="<?php echo esc_url( $first['before'] ); ?>"
				     alt="<?php echo esc_attr( $first['before_alt'] ); ?>"
				     width="600" height="600">
			</div>
			<div class="zipni-ba-card zipni-ba-card--after">
				<span class="zipni-ba-label">После — в интерьере</span>
				<img class="zipni-carousel__after" src="<?php echo esc_url( $first['after'] ); ?>"
				     alt="<?php echo esc_attr( $first['after_alt'] ); ?>"
				     width="600" height="600">
			</div>
		</div>
		<div class="zipni-carousel__thumbs" style="display:flex;gap:0.75rem;overflow-x:auto;padding:0.5rem 0;justify-content:center">
			<?php foreach ( $items as $i => $item ) : ?>
			<button class="zipni-thumb<?php echo $i === 0 ? ' zipni-thumb--active' : ''; ?>"
			        data-before="<?php echo esc_url( $item['before'] ); ?>"
			        data-after="<?php echo esc_url( $item['after'] ); ?>"
			        data-before-alt="<?php echo esc_attr( $item['before_alt'] ); ?>"
			        data-after-alt="<?php echo esc_attr( $item['after_alt'] ); ?>">
				<img src="<?php echo esc_url( $item['after'] ); ?>" alt="" loading="lazy" width="80" height="80">
			</button>
			<?php endforeach; ?>
		</div>
	</div>
	<script>
	(function(){
		var el = document.getElementById('<?php echo $uid; ?>');
		var before = el.querySelector('.zipni-carousel__before');
		var after = el.querySelector('.zipni-carousel__after');
		el.querySelectorAll('.zipni-thumb').forEach(function(btn){
			btn.addEventListener('click', function(){
				before.src = btn.dataset.before;
				before.alt = btn.dataset.beforeAlt;
				after.src = btn.dataset.after;
				after.alt = btn.dataset.afterAlt;
				el.querySelectorAll('.zipni-thumb').forEach(function(t){ t.classList.remove('zipni-thumb--active'); });
				btn.classList.add('zipni-thumb--active');
			});
		});
	})();
	</script>
	<?php
	return ob_get_clean();
}

/* ───────────────────────────────────────────────
 * Activation / Deactivation
 * ─────────────────────────────────────────────── */

function zipni_portfolio_activate() {
	zipni_portfolio_register_post_type();
	zipni_portfolio_register_taxonomy_category();
	zipni_portfolio_register_taxonomy_marketplace();

	$categories = array(
		'Мебель', 'Одежда', 'Обувь', 'Косметика', 'Электроника',
		'Посуда', 'Текстиль', 'Игрушки', 'Спорт', 'Аксессуары',
		'Сумки', 'Ювелирные изделия', 'Декор', 'Освещение', 'Продукты питания',
	);

	foreach ( $categories as $name ) {
		if ( ! term_exists( $name, 'portfolio_category' ) ) {
			wp_insert_term( $name, 'portfolio_category' );
		}
	}

	$marketplaces = array( 'Ozon', 'Wildberries', 'Общий' );

	foreach ( $marketplaces as $name ) {
		if ( ! term_exists( $name, 'marketplace' ) ) {
			wp_insert_term( $name, 'marketplace' );
		}
	}

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'zipni_portfolio_activate' );

function zipni_portfolio_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'zipni_portfolio_deactivate' );
