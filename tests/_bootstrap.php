<?php

/**
 * Synergy Wholesale SSL Module
 *
 * @copyright Copyright (c) Synergy Wholesale Pty Ltd 2020
 * @license https://github.com/synergywholesale/whmcs-ssl-module/LICENSE
 */

if (!defined('WHMCS')) {
    define('WHMCS', true);
}

// Include the module.
require_once __DIR__ . '/../modules/servers/synergywholesale_ssl/synergywholesale_ssl.php';

/**
 * Mock logModuleCall function for testing purposes.
 *
 * Inside of WHMCS, this function provides logging of module calls for debugging
 * purposes. The module log is accessed via Utilities > Logs.
 *
 * @param string $module
 * @param string $action
 * @param string|array $request
 * @param string|array $response
 * @param string|array $data
 * @param array $variablesToMask
 *
 * @return void|false
 */
function logModuleCall(
    $module,
    $action,
    $request,
    $response,
    $data = '',
    $variablesToMask = []
) {
    // do nothing during tests
}