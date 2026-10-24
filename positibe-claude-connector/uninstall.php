<?php
/**
 * Limpieza al desinstalar.
 *
 * @package Positibe_Claude_Connector
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'pcc_log' );
