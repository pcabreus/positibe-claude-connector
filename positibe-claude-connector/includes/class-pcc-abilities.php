<?php
/**
 * Abilities que se publican por MCP.
 *
 * En vez de una ability por cada cosa que se pueda hacer (y quedarse siempre
 * corto), se expone la REST API entera a través de rest-request. Los permisos
 * no se relajan: cada ruta REST sigue comprobando las capacidades del usuario
 * autenticado, así que el rol de ese usuario es el verdadero límite.
 *
 * @package Positibe_Claude_Connector
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registro y ejecución de las abilities.
 */
class PCC_Abilities {

	const CATEGORY = 'positibe-claude';

	/**
	 * Engancha el registro en el Abilities API (WordPress 6.9+).
	 */
	public static function init() {
		add_action( 'wp_abilities_api_categories_init', array( __CLASS__, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( __CLASS__, 'register_abilities' ) );
	}

	/**
	 * Categoría que agrupa las abilities del plugin.
	 */
	public static function register_category() {
		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'Claude: gestión del sitio', 'positibe-claude-connector' ),
				'description' => __( 'Acceso genérico al sitio a través de la REST API.', 'positibe-claude-connector' ),
			)
		);
	}

	/**
	 * Meta común: visible por MCP y por la REST del Abilities API.
	 *
	 * @param bool $readonly    Si no modifica nada.
	 * @param bool $destructive Si puede borrar o sobrescribir.
	 * @return array
	 */
	private static function meta( $readonly, $destructive ) {
		return array(
			'annotations'  => array(
				'readonly'    => $readonly,
				'destructive' => $destructive,
				'idempotent'  => $readonly,
			),
			'show_in_rest' => true,
			'mcp'          => array( 'public' => true ),
		);
	}

	/**
	 * Registra las abilities.
	 */
	public static function register_abilities() {
		$logged_in = function () {
			return is_user_logged_in();
		};

		wp_register_ability(
			'positibe-claude/site-context',
			array(
				'label'               => __( 'Contexto del sitio', 'positibe-claude-connector' ),
				'description'         => 'Returns everything needed to orient yourself before changing the site: WordPress/PHP versions, locale, active theme (and whether it is a block theme), active plugins, WooCommerce basics, REST namespaces and the current user role. Call this first in a new session.',
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => new stdClass(),
					'additionalProperties' => false,
				),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => array( __CLASS__, 'site_context' ),
				'permission_callback' => $logged_in,
				'meta'                => self::meta( true, false ),
			)
		);

		wp_register_ability(
			'positibe-claude/list-routes',
			array(
				'label'               => __( 'Listar rutas REST', 'positibe-claude-connector' ),
				'description'         => 'Lists REST API routes and their HTTP methods, optionally filtered by namespace (wp/v2, wc/v3, rankmath/v1...) or by a substring of the route.',
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'namespace' => array( 'type' => 'string' ),
						'search'    => array( 'type' => 'string' ),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => array( __CLASS__, 'list_routes' ),
				'permission_callback' => $logged_in,
				'meta'                => self::meta( true, false ),
			)
		);

		wp_register_ability(
			'positibe-claude/rest-request',
			array(
				'label'               => __( 'Petición REST', 'positibe-claude-connector' ),
				'description'         => 'Runs an internal WordPress REST API request as the current user and returns status, pagination totals and data. Covers posts, pages, media metadata, menus, templates, global styles, users, settings, plugins, WooCommerce (wc/v3) and any plugin with REST routes. Use positibe-claude/list-routes to discover paths. Create content as status "draft" unless told otherwise.',
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'method' => array(
							'type'    => 'string',
							'enum'    => array( 'GET', 'POST', 'PUT', 'PATCH', 'DELETE' ),
							'default' => 'GET',
						),
						'path'   => array(
							'type'        => 'string',
							'description' => 'Route without /wp-json, e.g. /wp/v2/pages/42 or /wc/v3/products',
						),
						'query'  => array(
							'type'                 => 'object',
							'description'          => 'Query string parameters (per_page, page, search, status, context...).',
							'additionalProperties' => true,
						),
						'body'   => array(
							'type'                 => 'object',
							'description'          => 'JSON body for POST, PUT, PATCH or DELETE.',
							'additionalProperties' => true,
						),
					),
					'required'             => array( 'path' ),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'status'      => array( 'type' => 'integer' ),
						'total'       => array( 'type' => array( 'integer', 'null' ) ),
						'total_pages' => array( 'type' => array( 'integer', 'null' ) ),
						'data'        => array( 'type' => array( 'object', 'array', 'string', 'number', 'integer', 'boolean', 'null' ) ),
					),
				),
				'execute_callback'    => array( __CLASS__, 'rest_request' ),
				// Cada ruta REST aplica sus propias comprobaciones de capacidad.
				'permission_callback' => $logged_in,
				'meta'                => self::meta( false, true ),
			)
		);

		wp_register_ability(
			'positibe-claude/upload-media',
			array(
				'label'               => __( 'Subir medio', 'positibe-claude-connector' ),
				'description'         => 'Uploads an image or file to the Media Library from a public URL, or from base64 for small files. Optionally sets title, alt text and caption, attaches it to a post, page or product and sets it as featured image.',
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'url'          => array( 'type' => 'string' ),
						'base64'       => array( 'type' => 'string' ),
						'filename'     => array(
							'type'        => 'string',
							'description' => 'Required with base64, e.g. anillo-oro.webp',
						),
						'title'        => array( 'type' => 'string' ),
						'alt_text'     => array( 'type' => 'string' ),
						'caption'      => array( 'type' => 'string' ),
						'post_id'      => array( 'type' => 'integer' ),
						'set_featured' => array(
							'type'    => 'boolean',
							'default' => false,
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'  => array( 'type' => 'integer' ),
						'url' => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( __CLASS__, 'upload_media' ),
				'permission_callback' => function () {
					return current_user_can( 'upload_files' );
				},
				'meta'                => self::meta( false, false ),
			)
		);
	}

	/**
	 * Contexto del sitio.
	 *
	 * @return array
	 */
	public static function site_context() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = array();
		$all     = get_plugins();
		foreach ( (array) get_option( 'active_plugins', array() ) as $file ) {
			if ( isset( $all[ $file ] ) ) {
				$plugins[] = array(
					'file'    => $file,
					'name'    => $all[ $file ]['Name'],
					'version' => $all[ $file ]['Version'],
				);
			}
		}

		$theme = wp_get_theme();
		$user  = wp_get_current_user();

		$context = array(
			'site'       => array(
				'name'     => get_bloginfo( 'name' ),
				'url'      => home_url( '/' ),
				'rest_url' => rest_url(),
				'locale'   => get_locale(),
				'timezone' => wp_timezone_string(),
			),
			'versions'   => array(
				'wordpress' => get_bloginfo( 'version' ),
				'php'       => PHP_VERSION,
				'connector' => PCC_VERSION,
			),
			'theme'      => array(
				'name'        => $theme->get( 'Name' ),
				'stylesheet'  => $theme->get_stylesheet(),
				'template'    => $theme->get_template(),
				'is_block'    => function_exists( 'wp_is_block_theme' ) && wp_is_block_theme(),
				'version'     => $theme->get( 'Version' ),
			),
			'plugins'    => $plugins,
			'namespaces' => array_values( rest_get_server()->get_namespaces() ),
			'user'       => array(
				'login' => $user->user_login,
				'roles' => array_values( $user->roles ),
			),
		);

		if ( function_exists( 'WC' ) ) {
			$context['woocommerce'] = array(
				'version'  => WC()->version,
				'currency' => get_woocommerce_currency(),
				'country'  => get_option( 'woocommerce_default_country' ),
				'shop_url' => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '',
			);
		}

		return $context;
	}

	/**
	 * Lista de rutas REST.
	 *
	 * @param array $input Entrada.
	 * @return array
	 */
	public static function list_routes( $input ) {
		$input  = (array) $input;
		$server = rest_get_server();
		$ns     = isset( $input['namespace'] ) ? trim( $input['namespace'], '/' ) : '';
		$search = isset( $input['search'] ) ? (string) $input['search'] : '';
		$routes = array();

		foreach ( $server->get_routes() as $route => $handlers ) {
			if ( '' !== $ns && 0 !== strpos( ltrim( $route, '/' ), $ns ) ) {
				continue;
			}
			if ( '' !== $search && false === stripos( $route, $search ) ) {
				continue;
			}
			$methods = array();
			foreach ( $handlers as $handler ) {
				$methods = array_merge( $methods, array_keys( array_filter( (array) $handler['methods'] ) ) );
			}
			$routes[ $route ] = array_values( array_unique( $methods ) );
		}

		return array(
			'namespaces' => array_values( $server->get_namespaces() ),
			'routes'     => (object) $routes,
		);
	}

	/**
	 * Ejecuta una petición REST interna.
	 *
	 * @param array $input Entrada.
	 * @return array
	 */
	public static function rest_request( $input ) {
		$input  = (array) $input;
		$method = strtoupper( isset( $input['method'] ) ? $input['method'] : 'GET' );
		$path   = '/' . ltrim( (string) $input['path'], '/' );
		$path   = preg_replace( '#^/wp-json#', '', $path );

		$request = new WP_REST_Request( $method, wp_parse_url( $path, PHP_URL_PATH ) );

		// Admite tanto query en la ruta (?per_page=5) como en el campo query.
		$query = array();
		$qs    = wp_parse_url( $path, PHP_URL_QUERY );
		if ( $qs ) {
			parse_str( $qs, $query );
		}
		if ( ! empty( $input['query'] ) ) {
			$query = array_merge( $query, (array) $input['query'] );
		}
		if ( $query ) {
			$request->set_query_params( $query );
		}
		if ( ! empty( $input['body'] ) ) {
			$request->set_header( 'Content-Type', 'application/json' );
			$request->set_body( wp_json_encode( $input['body'] ) );
		}

		PCC_Log::$inside_ability = true;
		$response                = rest_do_request( $request );
		PCC_Log::$inside_ability = false;

		$status = (int) $response->get_status();
		if ( 'GET' !== $method ) {
			PCC_Log::add( 'mcp', $method, $request->get_route(), $status );
		}

		$headers = $response->get_headers();

		return array(
			'status'      => $status,
			'total'       => isset( $headers['X-WP-Total'] ) ? (int) $headers['X-WP-Total'] : null,
			'total_pages' => isset( $headers['X-WP-TotalPages'] ) ? (int) $headers['X-WP-TotalPages'] : null,
			'data'        => rest_get_server()->response_to_data( $response, false ),
		);
	}

	/**
	 * Sube un medio a la biblioteca.
	 *
	 * @param array $input Entrada.
	 * @return array|WP_Error
	 */
	public static function upload_media( $input ) {
		$input = (array) $input;

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		if ( ! empty( $input['url'] ) ) {
			$tmp = download_url( esc_url_raw( $input['url'] ) );
			if ( is_wp_error( $tmp ) ) {
				return $tmp;
			}
			$name = ! empty( $input['filename'] ) ? $input['filename'] : basename( (string) wp_parse_url( $input['url'], PHP_URL_PATH ) );
		} elseif ( ! empty( $input['base64'] ) && ! empty( $input['filename'] ) ) {
			$bytes = base64_decode( preg_replace( '#^data:[^;]+;base64,#', '', $input['base64'] ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
			if ( false === $bytes ) {
				return new WP_Error( 'pcc_invalid_base64', __( 'No se pudo decodificar el base64.', 'positibe-claude-connector' ) );
			}
			$tmp = wp_tempnam( $input['filename'] );
			file_put_contents( $tmp, $bytes ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			$name = $input['filename'];
		} else {
			return new WP_Error( 'pcc_missing_source', __( 'Indica url, o base64 junto con filename.', 'positibe-claude-connector' ) );
		}

		$post_id = isset( $input['post_id'] ) ? (int) $input['post_id'] : 0;
		$file    = array(
			'name'     => sanitize_file_name( $name ),
			'tmp_name' => $tmp,
		);
		$id      = media_handle_sideload( $file, $post_id, isset( $input['title'] ) ? $input['title'] : null );

		if ( is_wp_error( $id ) ) {
			wp_delete_file( $tmp );
			PCC_Log::add( 'mcp', 'upload-media', $name, 500 );
			return $id;
		}
		if ( ! empty( $input['alt_text'] ) ) {
			update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( $input['alt_text'] ) );
		}
		if ( ! empty( $input['caption'] ) ) {
			wp_update_post(
				array(
					'ID'           => $id,
					'post_excerpt' => wp_kses_post( $input['caption'] ),
				)
			);
		}
		if ( $post_id && ! empty( $input['set_featured'] ) ) {
			set_post_thumbnail( $post_id, $id );
		}

		PCC_Log::add( 'mcp', 'upload-media', 'attachment/' . $id, 201 );

		return array(
			'id'  => (int) $id,
			'url' => (string) wp_get_attachment_url( $id ),
		);
	}
}
