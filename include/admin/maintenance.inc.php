<?php

$data = array();

// do not move to end of switch, since this will change smarty assignment scope
ob_start();
include S9Y_INCLUDE_PATH . 'include/admin/import.inc.php';
$data['importMenu'] = ob_get_contents();
ob_end_clean();

switch($serendipity['GET']['adminAction']) {
    case 'integrity':
        $data['action'] = "integrity";

        if (!is_readable(S9Y_INCLUDE_PATH . 'checksums.inc.php') || 0 == filesize(S9Y_INCLUDE_PATH . 'checksums.inc.php') ) {
            $data['noChecksum'] = true;
            break;
        }
        $data['badsums'] = serendipity_verifyFTPChecksums();
        break;

    case 'runcleanup':
        // The smarty method clearCompiledTemplate() clears all compiled smarty template files in templates_c
        // Since there may be other compiled template files in templates_c too, we have to restrict this call() to clear the blogs template only,
        // to not have the following automated recompile, force the servers memory to get exhausted,
        // when using plugins like serendipity_event_gravatar plugin, which can eat up some MB...
        // Restriction to template means: leave the page we are on: ../admin/index.tpl and all others, which are set, included and compiled by runtime. (plugins, etc. this can be quite some..!)
        if(method_exists($serendipity['smarty'], 'clearCompiledTemplate')) {
            $data['cleanup_finish']   = (int)$serendipity['smarty']->clearCompiledTemplate(null, $serendipity['template']);
            $data['cleanup_template'] = $serendipity['template'];
        }
        break;
}

$data['unusedTables'] = array();

$plugins = serendipity_plugin_api::get_installed_plugins();
$classes = serendipity_plugin_api::enum_plugin_classes(true);

foreach ($classes as $class_data) {
    // we only want to show the tables not currently in use by plugins
    if (! in_array($class_data['true_name'], $plugins)) {
        $pluginFile =  serendipity_plugin_api::probePlugin($class_data['name'], $class_data['classname'], $class_data['pluginPath']);
        // spartacus is only set becasue getPluginInfo needs a type parameter
        $plugin     =& serendipity_plugin_api::getPluginInfo($pluginFile, $class_data, 'spartacus');
        if (is_object($plugin)) {
            $bag = new serendipity_property_bag;
            $plugin->introspect($bag);
            if ($bag->is_set('tables')) {
                foreach ($bag->get('tables') AS $table => $definition) {
                    if ($table != 'version') {
                        $data['unusedTables'][] = $table;
                    }
                }
            }
        }
    }
}

echo serendipity_smarty_show('admin/maintenance.inc.tpl', $data);

/* vim: set sts=4 ts=4 expandtab : */
