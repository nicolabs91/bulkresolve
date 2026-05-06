<?php
if (!defined('GLPI_ROOT')) { die('Sorry. You cannot access this file directly'); }
define('PLUGIN_BULKRESOLVE_VERSION', '0.1.0');
function plugin_init_bulkresolve() {
   global $PLUGIN_HOOKS;
   Plugin::registerClass('PluginBulkresolveBulkresolve');
   $PLUGIN_HOOKS['csrf_compliant']['bulkresolve'] = true;
   $PLUGIN_HOOKS['use_massive_action']['bulkresolve'] = true;
}
function plugin_version_bulkresolve() {
   return ['name'=>'Bulk Resolve Tickets','version'=>PLUGIN_BULKRESOLVE_VERSION,'author'=>'Nicolabs91','license'=>'GPLv3+','homepage'=>'','requirements'=>['glpi'=>['min'=>'11.0.0','max'=>'11.99.99']]];
}
function plugin_bulkresolve_check_prerequisites() { return true; }
function plugin_bulkresolve_check_config($verbose = false) { return true; }
