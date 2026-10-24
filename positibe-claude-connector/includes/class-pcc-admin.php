<?php
/**
 * Página Herramientas → Claude: estado, configuración lista para copiar y registro.
 *
 * @package Positibe_Claude_Connector
 */

defined( 'ABSPATH' ) || exit;

/**
 * Pantalla de administración.
 */
class PCC_Admin {

	/**
	 * Engancha el menú.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( PCC_FILE ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Añade la página bajo Herramientas.
	 */
	public static function menu() {
		add_management_page(
			__( 'Conexión con Claude', 'positibe-claude-connector' ),
			__( 'Claude', 'positibe-claude-connector' ),
			'manage_options',
			'positibe-claude',
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * Enlace a la página desde la lista de plugins.
	 *
	 * @param array $links Enlaces.
	 * @return array
	 */
	public static function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'tools.php?page=positibe-claude' ) ) . '">' . esc_html__( 'Configurar', 'positibe-claude-connector' ) . '</a>' );
		return $links;
	}

	/**
	 * Comprobaciones de requisitos para cada vía.
	 *
	 * @return array Lista de [etiqueta, ok, ayuda].
	 */
	private static function checks() {
		$routes = rest_get_server()->get_routes();

		return array(
			array(
				__( 'WordPress 6.9 o superior (Abilities API)', 'positibe-claude-connector' ),
				function_exists( 'wp_register_ability' ),
				__( 'Sin él no hay vía MCP. La REST directa sigue funcionando.', 'positibe-claude-connector' ),
			),
			array(
				__( 'Plugin MCP Adapter activo', 'positibe-claude-connector' ),
				isset( $routes[ '/' . PCC_MCP_ROUTE ] ),
				__( 'Instálalo desde Plugins → Añadir nuevo, buscando «MCP Adapter». Solo hace falta para la vía MCP.', 'positibe-claude-connector' ),
			),
			array(
				__( 'HTTPS', 'positibe-claude-connector' ),
				'https' === wp_parse_url( home_url(), PHP_URL_SCHEME ),
				__( 'Las contraseñas de aplicación viajan en cada petición: sin HTTPS no es seguro.', 'positibe-claude-connector' ),
			),
			array(
				__( 'Enlaces permanentes distintos de «Simple»', 'positibe-claude-connector' ),
				'' !== (string) get_option( 'permalink_structure' ),
				__( 'Ajustes → Enlaces permanentes.', 'positibe-claude-connector' ),
			),
			array(
				__( 'Contraseñas de aplicación disponibles', 'positibe-claude-connector' ),
				function_exists( 'wp_is_application_passwords_available' ) && wp_is_application_passwords_available(),
				__( 'Algún plugin de seguridad puede estar desactivándolas.', 'positibe-claude-connector' ),
			),
		);
	}

	/**
	 * Pinta la página.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$mcp_url  = rest_url( PCC_MCP_ROUTE );
		$rest_url = rest_url();
		$host     = (string) wp_parse_url( home_url(), PHP_URL_HOST );
		$slug     = sanitize_title( str_replace( '.', '-', $host ) );

		$desktop = wp_json_encode(
			array(
				'mcpServers' => array(
					$slug => array(
						'command' => 'npx',
						'args'    => array( '-y', '@automattic/mcp-wordpress-remote@latest' ),
						'env'     => array(
							'WP_API_URL'      => $mcp_url,
							'WP_API_USERNAME' => 'USUARIO',
							'WP_API_PASSWORD' => 'CONTRASEÑA_DE_APLICACIÓN',
						),
					),
				),
			),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		);

		$code = sprintf(
			"claude mcp add --scope user --transport http %s %s \\\n  --header \"Authorization: Basic $(printf 'USUARIO:CONTRASEÑA_DE_APLICACIÓN' | base64)\"",
			$slug,
			$mcp_url
		);

		$env = sprintf(
			"WP_URL=%s\nWP_USER=USUARIO\nWP_APP_PASSWORD=\"CONTRASEÑA_DE_APLICACIÓN\"\n\n# Ejemplo\ncurl -s -u \"\$WP_USER:\$WP_APP_PASSWORD\" \"\$WP_URL/wp-json/wp/v2/pages?per_page=5\"",
			untrailingslashit( home_url() )
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Conexión con Claude', 'positibe-claude-connector' ); ?></h1>
			<p><?php esc_html_e( 'Claude puede trabajar con este sitio por dos vías que comparten credenciales: MCP (Claude Desktop, Cowork y Claude Code) y REST directa (Claude Code o cualquier terminal). Usa un usuario dedicado con una contraseña de aplicación: su rol es el límite real de lo que Claude puede hacer.', 'positibe-claude-connector' ); ?></p>

			<h2><?php esc_html_e( 'Estado', 'positibe-claude-connector' ); ?></h2>
			<table class="widefat striped" style="max-width:900px">
				<tbody>
				<?php foreach ( self::checks() as $check ) : ?>
					<tr>
						<td style="width:2em"><?php echo $check[1] ? '✅' : '❌'; ?></td>
						<td><strong><?php echo esc_html( $check[0] ); ?></strong><?php if ( ! $check[1] ) : ?><br><span class="description"><?php echo esc_html( $check[2] ); ?></span><?php endif; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( '1. Credenciales', 'positibe-claude-connector' ); ?></h2>
			<p>
				<?php esc_html_e( 'Crea la contraseña en el perfil del usuario que usará Claude, en la sección «Contraseñas de aplicación». Sustituye USUARIO y CONTRASEÑA_DE_APLICACIÓN en los bloques de abajo.', 'positibe-claude-connector' ); ?>
				<a href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>"><?php esc_html_e( 'Ir a Usuarios', 'positibe-claude-connector' ); ?></a>
			</p>
			<p><code><?php echo esc_html( 'MCP: ' . $mcp_url ); ?></code><br><code><?php echo esc_html( 'REST: ' . $rest_url ); ?></code></p>

			<h2><?php esc_html_e( '2. Claude Desktop y Cowork', 'positibe-claude-connector' ); ?></h2>
			<p><?php esc_html_e( 'Claude → Ajustes → Desarrollador → Editar configuración. Requiere Node.js en el ordenador. Cowork usa los servidores configurados aquí a través del enlace con tu ordenador.', 'positibe-claude-connector' ); ?></p>
			<textarea readonly rows="14" class="large-text code" onclick="this.select()"><?php echo esc_textarea( $desktop ); ?></textarea>

			<h2><?php esc_html_e( '3. Claude Code: MCP por HTTP', 'positibe-claude-connector' ); ?></h2>
			<p><?php esc_html_e( 'Sin npx: Claude Code habla con el endpoint directamente.', 'positibe-claude-connector' ); ?></p>
			<textarea readonly rows="3" class="large-text code" onclick="this.select()"><?php echo esc_textarea( $code ); ?></textarea>

			<h2><?php esc_html_e( '4. REST directa', 'positibe-claude-connector' ); ?></h2>
			<p><?php esc_html_e( 'Guárdalo en un .env fuera de cualquier repositorio público. La skill de Claude lo usa cuando tiene una terminal que llega al sitio.', 'positibe-claude-connector' ); ?></p>
			<textarea readonly rows="7" class="large-text code" onclick="this.select()"><?php echo esc_textarea( $env ); ?></textarea>

			<h2><?php esc_html_e( 'Últimas escrituras', 'positibe-claude-connector' ); ?></h2>
			<?php $log = PCC_Log::recent( 50 ); ?>
			<?php if ( ! $log ) : ?>
				<p class="description"><?php esc_html_e( 'Todavía no hay cambios registrados.', 'positibe-claude-connector' ); ?></p>
			<?php else : ?>
				<table class="widefat striped" style="max-width:900px">
					<thead><tr>
						<th><?php esc_html_e( 'Fecha', 'positibe-claude-connector' ); ?></th>
						<th><?php esc_html_e( 'Usuario', 'positibe-claude-connector' ); ?></th>
						<th><?php esc_html_e( 'Vía', 'positibe-claude-connector' ); ?></th>
						<th><?php esc_html_e( 'Acción', 'positibe-claude-connector' ); ?></th>
						<th><?php esc_html_e( 'Recurso', 'positibe-claude-connector' ); ?></th>
						<th><?php esc_html_e( 'Estado', 'positibe-claude-connector' ); ?></th>
					</tr></thead>
					<tbody>
					<?php foreach ( $log as $row ) : ?>
						<tr>
							<td><?php echo esc_html( wp_date( 'Y-m-d H:i', $row['time'] ) ); ?></td>
							<td><?php echo esc_html( $row['user'] ); ?></td>
							<td><?php echo esc_html( strtoupper( $row['source'] ) ); ?></td>
							<td><?php echo esc_html( $row['method'] ); ?></td>
							<td><code><?php echo esc_html( $row['target'] ); ?></code></td>
							<td><?php echo esc_html( (string) $row['status'] ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}
}
