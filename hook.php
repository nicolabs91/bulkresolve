<?php
function plugin_bulkresolve_install() { return true; }
function plugin_bulkresolve_uninstall() { return true; }

function plugin_bulkresolve_MassiveActions($itemtype) {
   if ($itemtype !== Ticket::class && $itemtype !== 'Ticket') {
      return [];
   }
   if (!Ticket::canUpdate() || !ITILSolution::canCreate()) {
      return [];
   }
   return [
      'PluginBulkresolveBulkresolve' . MassiveAction::CLASS_ACTION_SEPARATOR . 'bulk_resolve_with_category'
         => '<i class="ti ti-checkup-list"></i>' . __('Resolve with category', 'bulkresolve'),
   ];
}
