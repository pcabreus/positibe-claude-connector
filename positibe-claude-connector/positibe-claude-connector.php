<?php
/**
 * Plugin Name:       Positibe Claude Connector
 * Plugin URI:        https://positibe.com/
 * Description:       Conecta el sitio con Claude por todas las vías: MCP (vía MCP Adapter) y REST directa con contraseñas de aplicación. Expone la REST API completa como abilities, sube medios y registra cada escritura.
 * Version:           1.0.0
 * Requires at least: 6.9
 * Requires PHP:      7.4
 * Author:            Positibe Labs
 * Author URI:        https://positibe.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       positibe-claude-connector
 *
 * @package Positibe_Claude_Connector
 */

defined( 'ABSPATH' ) || exit;

define( 'PCC_VERSION', '1.0.0' );
define( 'PCC_FILE', __FILE__ );
define( 'PCC_DIR', plugin_dir_path( __FILE__ ) );

/** Ruta del servidor MCP por defecto que registra el plugin MCP Adapter. */
define( 'PCC_MCP_ROUTE', 'mcp/mcp-adapter-default-server' );

require_once PCC_DIR . 'includes/class-pcc-log.php';
require_once PCC_DIR . 'includes/class-pcc-abilities.php';
require_once PCC_DIR . 'includes/class-pcc-admin.php';

PCC_Log::init();
PCC_Abilities::init();

if ( is_admin() ) {
	PCC_Admin::init();
}
