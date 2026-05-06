<?php
include('../../../inc/includes.php');
Session::checkLoginUser();
Session::checkRight('ticket', UPDATE);

function plugin_bulkresolve_status_label($status) { return CommonITILObject::getStatus($status); }
function plugin_bulkresolve_get_ticket_list($limit = 100) {
   global $DB;
   $iterator = $DB->request(['SELECT'=>['id','name','status','itilcategories_id','date_mod','entities_id'],'FROM'=>Ticket::getTable(),'WHERE'=>['is_deleted'=>0,'NOT'=>['status'=>[CommonITILObject::SOLVED, CommonITILObject::CLOSED]]],'ORDER'=>['date_mod DESC'],'LIMIT'=>$limit]);
   $tickets = [];
   foreach ($iterator as $row) { $ticket = new Ticket(); if ($ticket->getFromDB((int)$row['id']) && $ticket->can((int)$row['id'], UPDATE)) { $tickets[] = $row; } }
   return $tickets;
}
function plugin_bulkresolve_process() {
   $ticket_ids = array_map('intval', $_POST['tickets'] ?? []);
   $category_id = (int)($_POST['itilcategories_id'] ?? 0);
   $solution = trim($_POST['solution'] ?? '');
   $solution_type_id = (int)($_POST['solutiontypes_id'] ?? 0);
   if (count($ticket_ids) === 0) { Session::addMessageAfterRedirect(__('Select at least one ticket.', 'bulkresolve'), false, ERROR); return; }
   if ($category_id <= 0) { Session::addMessageAfterRedirect(__('Choose a category before resolving tickets.', 'bulkresolve'), false, ERROR); return; }
   if ($solution === '') { Session::addMessageAfterRedirect(__('Enter a solution text.', 'bulkresolve'), false, ERROR); return; }
   if (!ITILSolution::canCreate()) { Session::addMessageAfterRedirect(__('You are not allowed to add solutions.', 'bulkresolve'), false, ERROR); return; }
   $category = new ITILCategory();
   if (!$category->getFromDB($category_id)) { Session::addMessageAfterRedirect(__('Selected category cannot be loaded.', 'bulkresolve'), false, ERROR); return; }
   if ($solution_type_id > 0) { $solution_type = new SolutionType(); if (!$solution_type->getFromDB($solution_type_id)) { Session::addMessageAfterRedirect(__('Selected solution type cannot be loaded.', 'bulkresolve'), false, ERROR); return; } }
   $ok = 0; $failed = [];
   foreach ($ticket_ids as $ticket_id) {
      $ticket = new Ticket();
      if (!$ticket->getFromDB($ticket_id) || !$ticket->can($ticket_id, UPDATE)) { $failed[] = sprintf(__('Ticket #%s: no permission or not found', 'bulkresolve'), $ticket_id); continue; }
      if (in_array((int)$ticket->fields['status'], [CommonITILObject::SOLVED, CommonITILObject::CLOSED], true)) { $failed[] = sprintf(__('Ticket #%s: already solved/closed', 'bulkresolve'), $ticket_id); continue; }
      if (!$ticket->update(['id'=>$ticket_id, 'itilcategories_id'=>$category_id])) { $failed[] = sprintf(__('Ticket #%s: category update failed', 'bulkresolve'), $ticket_id); continue; }
      $solution_input = ['itemtype'=>Ticket::class, 'items_id'=>$ticket_id, 'content'=>$solution];
      if ($solution_type_id > 0) { $solution_input['solutiontypes_id'] = $solution_type_id; }
      $itil_solution = new ITILSolution();
      if ($itil_solution->add($solution_input)) { $ok++; } else { $failed[] = sprintf(__('Ticket #%s: solution/resolution failed', 'bulkresolve'), $ticket_id); }
   }
   if ($ok > 0) { Session::addMessageAfterRedirect(sprintf(__('%s ticket(s) resolved.', 'bulkresolve'), $ok), true, INFO); }
   foreach ($failed as $msg) { Session::addMessageAfterRedirect($msg, false, ERROR); }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_resolve'])) { plugin_bulkresolve_process(); Html::redirect('/plugins/bulkresolve/front/bulkresolve.php'); }
$tickets = plugin_bulkresolve_get_ticket_list(100);
Html::header(__('Bulk resolve tickets', 'bulkresolve'), $_SERVER['PHP_SELF'], 'helpdesk', 'pluginbulkresolvebulkresolve');
echo "<div class='container-fluid'><div class='card'><div class='card-header d-flex align-items-center gap-2'><i class='" . PluginBulkresolveBulkresolve::getIcon() . "'></i><h2 class='card-title mb-0'>" . htmlescape(__('Bulk resolve tickets', 'bulkresolve')) . "</h2></div><div class='card-body'>";
echo "<p class='text-muted'>" . htmlescape(__('Select open tickets, choose the required category, enter one solution text, then resolve them all at once.', 'bulkresolve')) . "</p>";
global $CFG_GLPI;
echo "<form method='post' action='" . htmlescape($CFG_GLPI['root_doc'] . '/plugins/bulkresolve/front/bulkresolve.php') . "'>";
echo Html::hidden('bulk_resolve', ['value' => 1]);
echo "<div class='row g-3 mb-4'><div class='col-md-6'><label class='form-label fw-bold'>" . htmlescape(_n('Category', 'Categories', 1)) . " <span class='text-danger'>*</span></label>";
ITILCategory::dropdown(['name'=>'itilcategories_id','value'=>0,'entity'=>$_SESSION['glpiactiveentities'] ?? -1,'width'=>'100%']);
echo "<div class='form-text'>" . htmlescape(__('This category will be applied to every selected ticket before the solution is added.', 'bulkresolve')) . "</div></div>";
echo "<div class='col-md-6'><label class='form-label fw-bold'>" . htmlescape(SolutionType::getTypeName(1)) . "</label>";
SolutionType::dropdown(['name'=>'solutiontypes_id','value'=>0,'width'=>'100%']);
echo "</div></div><div class='mb-4'><label class='form-label fw-bold'>" . htmlescape(ITILSolution::getTypeName(1)) . " <span class='text-danger'>*</span></label><textarea class='form-control' name='solution' rows='5' required placeholder='" . htmlescape(__('Example: Resolved in bulk after verification.', 'bulkresolve')) . "'></textarea></div>";
if (count($tickets) === 0) {
   echo "<div class='alert alert-info'>" . htmlescape(__('No open tickets available for bulk resolve.', 'bulkresolve')) . "</div>";
} else {
   echo "<div class='d-flex justify-content-between align-items-center mb-2'><h3 class='h5 mb-0'>" . htmlescape(__('Open tickets', 'bulkresolve')) . "</h3><button type='button' class='btn btn-sm btn-outline-secondary' onclick=\"document.querySelectorAll('.bulkresolve-ticket').forEach(cb => cb.checked = true);\">" . htmlescape(__('Select all shown', 'bulkresolve')) . "</button></div>";
   echo "<div class='table-responsive'><table class='table table-hover table-sm align-middle'><thead><tr><th style='width:40px'></th><th>ID</th><th>" . htmlescape(__('Title')) . "</th><th>" . htmlescape(__('Status')) . "</th><th>" . htmlescape(_n('Category', 'Categories', 1)) . "</th><th>" . htmlescape(__('Last update')) . "</th></tr></thead><tbody>";
   foreach ($tickets as $row) {
      $cat_name = ((int)$row['itilcategories_id'] > 0) ? Dropdown::getDropdownName(ITILCategory::getTable(), (int)$row['itilcategories_id']) : '';
      echo "<tr><td><input class='form-check-input bulkresolve-ticket' type='checkbox' name='tickets[]' value='" . (int)$row['id'] . "'></td><td><a href='" . htmlescape(Ticket::getFormURLWithID((int)$row['id'])) . "'>#" . (int)$row['id'] . "</a></td><td>" . htmlescape($row['name']) . "</td><td>" . htmlescape(plugin_bulkresolve_status_label((int)$row['status'])) . "</td><td>" . htmlescape($cat_name ?: __('None')) . "</td><td>" . htmlescape($row['date_mod']) . "</td></tr>";
   }
   echo "</tbody></table></div><div class='mt-3'><button class='btn btn-primary' type='submit'>" . htmlescape(__('Resolve selected tickets', 'bulkresolve')) . "</button></div>";
}
Html::closeForm();
echo "</div></div></div>";
Html::footer();
