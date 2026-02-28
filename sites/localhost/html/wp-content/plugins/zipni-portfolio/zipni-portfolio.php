<?php
/**
 * Plugin Name: Zipni Portfolio
 * Description: CPT portfolio с before/after генерациями, таксономиями и REST API для автопостинга.
 * Version:     1.0.0
 * Author:      Zipni
 * Text Domain: zipni-portfolio
 *
 * @package zipni-portfolio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register CPT 'portfolio'.
 */
function zipni_portfolio_register_post_type() {
	$labels = array(
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
	);

	register_post_type( 'portfolio', array(
		'labels'       => $labels,
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

/**
 * Register taxonomy 'portfolio_category'.
 */
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

/**
 * Register taxonomy 'marketplace'.
 */
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

/**
 * Register custom meta fields for CPT portfolio.
 * All exposed to REST API for autoposting from Python.
 */
function zipni_portfolio_register_meta() {
	$fields = array(
		'before_image_url'  => 'string',
		'after_image_url'   => 'string',
		'before_alt'        => 'string',
		'after_alt'         => 'string',
		'product_name'      => 'string',
		'scene_description' => 'string',
	);

	foreach ( $fields as $key => $type ) {
		register_post_meta( 'portfolio', $key, array(
			'type'          => $type,
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
		) );
	}
}
add_action( 'init', 'zipni_portfolio_register_meta' );

/**
 * Seed default terms on plugin activation.
 */
function zipni_portfolio_activate() {
	// Register CPT and taxonomies first so terms can be inserted.
	zipni_portfolio_register_post_type();
	zipni_portfolio_register_taxonomy_category();
	zipni_portfolio_register_taxonomy_marketplace();

	$categories = array(
		'Мебель',
		'Одежда',
		'Обувь',
		'Косметика',
		'Электроника',
		'Посуда',
		'Текстиль',
		'Игрушки',
		'Спорт',
		'Аксессуары',
		'Сумки',
		'Ювелирные изделия',
		'Декор',
		'Освещение',
		'Продукты питания',
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

/**
 * Flush rewrite rules on deactivation.
 */
function zipni_portfolio_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'zipni_portfolio_deactivate' );
