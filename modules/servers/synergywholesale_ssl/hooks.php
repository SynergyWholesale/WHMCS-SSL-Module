<?php

/**
 * Synergy Wholesale SSL Module
 *
 * @copyright Copyright (c) Synergy Wholesale Pty Ltd 2020
 * @license https://github.com/synergywholesale/whmcs-ssl-module/LICENSE
 */
 
add_hook('ClientAreaPrimarySidebar', 1, function (WHMCS\View\Menu\Item $primarySidebar) {
    $panel = $primarySidebar->getChild('Service Details Overview');
    if (is_a($panel, 'WHMCS\View\Menu\Item')) {
        $panel = $panel->getChild('Information');
        if (is_a($panel, 'WHMCS\View\Menu\Item')) {
            $destination = parse_url($panel->getUri());
            $current = parse_url($_SERVER['REQUEST_URI']);
            if (isset($destination['fragment']) && $destination['query'] != $current['query']) {
                $panel->setUri($destination['path'] . '?' . $destination['query']);
                $panel->setAttributes([]);
            }
        }
    }
});
