<?php
namespace Yardlii\Core;

use Yardlii\Core\Admin\SettingsPageTabs;
use Yardlii\Core\Admin\Assets;
use Yardlii\Core\Features\Loader;
use Yardlii\Core\Services\Logger;

class Core
{
    /**
     * Main initialization method
     */
    public function init(): void
    {
        $this->load_textdomain();
        
        Logger::log('Core::init() starting...', 'INIT');

        try {
            (new Assets())->register();
            Logger::log('Admin assets registered successfully.', 'INIT');
        } catch (\Throwable $e) {
            Logger::log('Error registering assets: ' . $e->getMessage(), 'INIT');
        }

        try {
            (new SettingsPageTabs())->register();
            Logger::log('SettingsPageTabs registered successfully.', 'INIT');
        } catch (\Throwable $e) {
            Logger::log('Error initializing SettingsPageTabs: ' . $e->getMessage(), 'INIT');
        }

        try {
            (new Loader())->register();
            Logger::log('Feature loader registered successfully.', 'INIT');
        } catch (\Throwable $e) {
            Logger::log('Error initializing Loader: ' . $e->getMessage(), 'INIT');
        }

        // [FIX] Force Google Maps to 'weekly' channel to fix <gmp-pin> error
        add_filter( 'facetwp_gmaps_params', function( $params ) {
            $params['v'] = 'weekly';
            return $params;
        });

        Logger::log('Core::init() completed.', 'INIT');
    }

    /**
     * Load translations
     */
    private function load_textdomain(): void
    {
        load_plugin_textdomain(
            'yardlii-core',
            false,
            dirname(plugin_basename(__FILE__), 2) . '/languages'
        );
    }
}