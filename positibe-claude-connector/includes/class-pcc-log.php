<?php
/**
 * Registro de auditoría de las escrituras hechas por Claude.
 *
 * Cubre las dos vías con un único registro: las abilities lo anotan ellas
 * mismas (origen "mcp") y las peticiones REST autenticadas con contraseña de
 * aplicación se anotan en rest_post_dispatch (origen "rest"). Solo se guardan
 * escrituras: las lecturas no cambian nada y llenarían el buffer.
 *
 * @package Positibe_Claude_Connector
 */

defined( 'ABSPATH' ) || exit;

/**
 * Buffer circular de las últimas escrituras en una option sin autoload.
 */
class PCC_Log {

	const OPTION = 'pcc_log';
	const MAX    = 300;

	/**
	 * Mientras una ability ejecuta una petición interna, el hook REST no debe
	 * anotarla otra vez.
	 *
	 * @var bool
	 */
	public static $inside_ability = false;

	/**
	 * Registra el hook de la vía REST directa.
	 */
	public static function init() {
		add_filter( 'rest_post_dispatch', array( __CLASS__, 'on_rest_dispatch' ), 10, 3 );
	}

	/**
	 * Anota las escrituras REST hechas con contraseña de aplicación.
	 *
	 * @param WP_HTTP_Response $response Respuesta.
	 * @param WP_REST_Server   $server   Servidor.
	 * @param WP_REST_Request  $request  Petición.
	 * @return WP_HTTP_Response
	 */
	public static function on_rest_dispatch( $response, $server, $request ) {
		if ( self::$inside_ability || 'GET' === $request->get_method() || 'HEAD' === $request->get_method() ) {
			return $response;
		}
		// Las llamadas al propio servidor MCP se anotan dentro de cada ability, con más detalle.
		if ( 0 === strpos( ltrim( $request->get_route(), '/' ), 'mcp/' ) ) {
			return $response;
		}
		if ( ! function_exists( 'rest_get_authenticated_app_password' ) || ! rest_get_authenticated_app_password() ) {
			return $response;
		}
		self::add( 'rest', $request->get_method(), $request->get_route(), $response->get_status() );
		return $response;
	}

	/**
	 * Añade una entrada al registro.
	 *
	 * @param string $source Origen: mcp o rest.
	 * @param string $method Método HTTP o nombre de la acción.
	 * @param string $target Ruta o recurso afectado.
	 * @param int    $status Código de estado resultante.
	 */
	public static function add( $source, $method, $target, $status ) {
		$user = wp_get_current_user();
		$log  = get_option( self::OPTION, array() );
		if ( ! is_array( $log ) ) {
			$log = array();
		}
		array_unshift(
			$log,
			array(
				'time'   => time(),
				'user'   => $user->user_login,
				'source' => $source,
				'method' => $method,
				'target' => substr( (string) $target, 0, 200 ),
				'status' => (int) $status,
			)
		);
		update_option( self::OPTION, array_slice( $log, 0, self::MAX ), false );
	}

	/**
	 * Devuelve las entradas más recientes.
	 *
	 * @param int $limit Máximo de entradas.
	 * @return array
	 */
	public static function recent( $limit = 50 ) {
		$log = get_option( self::OPTION, array() );
		return is_array( $log ) ? array_slice( $log, 0, $limit ) : array();
	}
}
