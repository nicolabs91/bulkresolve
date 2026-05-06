<?php
if (!defined('GLPI_ROOT')) { die('Sorry. You cannot access this file directly'); }

class PluginBulkresolveBulkresolve extends CommonGLPI {
   public static $rightname = 'ticket';

   public static function getTypeName($nb = 0) { return __('Bulk resolve tickets', 'bulkresolve'); }
   public static function getMenuName() { return self::getTypeName(Session::getPluralNumber()); }
   public static function getIcon() { return 'ti ti-checkup-list'; }
   public static function canView(): bool { return Ticket::canView(); }
   public static function canCreate(): bool { return Ticket::canUpdate() && ITILSolution::canCreate(); }

   public static function getMenuContent() {
      if (!self::canView()) { return false; }
      return ['title'=>self::getMenuName(),'page'=>'/plugins/bulkresolve/front/bulkresolve.php','icon'=>self::getIcon(),'links'=>['search'=>'/plugins/bulkresolve/front/bulkresolve.php']];
   }

   public static function showMassiveActionsSubForm(MassiveAction $ma) {
      if ($ma->getAction() !== 'bulk_resolve_with_category') {
         return false;
      }

      $rand = mt_rand();
      echo '<div class="horizontal-form">';

      echo '<div class="form-row">';
      echo '<label for="dropdown_solution_template' . $rand . '">' . htmlescape(SolutionTemplate::getTypeName(1)) . '</label>';
      SolutionTemplate::dropdown([
         'name'      => 'solution_template',
         'value'     => 0,
         'rand'      => $rand,
         'width'     => '100%',
         'on_change' => "bulkresolve_solutiontemplate_update{$rand}(this.value)",
      ]);
      echo Html::hidden('_render_twig', ['value' => true]);
      $JS = <<<JAVASCRIPT
function bulkresolve_solutiontemplate_update{$rand}(value) {
   if (!value || value == 0) {
      return;
   }
   $.ajax({
      url: CFG_GLPI.root_doc + '/ajax/solution.php',
      type: 'POST',
      data: { solutiontemplates_id: value }
   }).done(function(data) {
      if (data && typeof data.content !== 'undefined') {
         $('#bulkresolve_content{$rand}').val(data.content).trigger('change');
      }
      var solutiontypes_id = data && !isNaN(parseInt(data.solutiontypes_id))
         ? parseInt(data.solutiontypes_id)
         : 0;
      $('#dropdown_solutiontypes_id{$rand}').trigger('setValue', solutiontypes_id);
   });
}
JAVASCRIPT;
      echo Html::scriptBlock($JS);
      echo '</div>';

      echo '<div class="form-row">';
      echo '<label for="dropdown_itilcategories_id' . $rand . '">' . htmlescape(_n('Category', 'Categories', 1)) . ' <span class="text-danger">*</span></label>';
      ITILCategory::dropdown([
         'name'  => 'itilcategories_id',
         'value' => 0,
         'rand'  => $rand,
         'width' => '100%',
      ]);
      echo '</div>';

      echo '<div class="form-row">';
      echo '<label for="dropdown_solutiontypes_id' . $rand . '">' . htmlescape(SolutionType::getTypeName(1)) . '</label>';
      SolutionType::dropdown([
         'name'  => 'solutiontypes_id',
         'value' => 0,
         'rand'  => $rand,
         'width' => '100%',
      ]);
      echo '</div>';

      echo '<div class="form-row-vertical">';
      echo '<label for="bulkresolve_content' . $rand . '">' . htmlescape(ITILSolution::getTypeName(1)) . ' <span class="text-danger">*</span></label>';
      Html::textarea([
         'name'              => 'content',
         'value'             => '',
         'rand'              => $rand,
         'editor_id'         => 'bulkresolve_content' . $rand,
         'enable_fileupload' => false,
         'enable_richtext'   => false,
         'cols'              => 80,
         'rows'              => 8,
      ]);
      echo '</div>';
      echo '</div>';

      echo Html::submit(__('Resolve'), [
         'name'  => 'massiveaction',
         'icon'  => 'ti ti-check',
         'class' => 'btn btn-sm btn-primary',
      ]);
      return true;
   }

   public static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids) {
      if ($ma->getAction() !== 'bulk_resolve_with_category') {
         return;
      }
      if ($item::getType() !== Ticket::getType()) {
         $ma->addMessage($item->getErrorMessage(ERROR_COMPAT));
         return;
      }
      if (!Ticket::canUpdate() || !ITILSolution::canCreate()) {
         $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
         return;
      }

      $input = $ma->getInput();
      $category_id = (int)($input['itilcategories_id'] ?? 0);
      $solution_type_id = (int)($input['solutiontypes_id'] ?? 0);
      $content = trim($input['content'] ?? '');
      if ($category_id <= 0 || $content === '') {
         $ma->addMessage(__('Category and solution are mandatory.', 'bulkresolve'));
         foreach ($ids as $id) {
            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
         }
         return;
      }

      $category = new ITILCategory();
      if (!$category->getFromDB($category_id)) {
         $ma->addMessage(__('Selected category cannot be loaded.', 'bulkresolve'));
         foreach ($ids as $id) {
            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
         }
         return;
      }

      if ($solution_type_id > 0) {
         $solution_type = new SolutionType();
         if (!$solution_type->getFromDB($solution_type_id)) {
            $ma->addMessage(__('Selected solution type cannot be loaded.', 'bulkresolve'));
            foreach ($ids as $id) {
               $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
            }
            return;
         }
      }

      $ticket = new Ticket();
      foreach ($ids as $id) {
         if (!$ticket->getFromDB($id) || !$ticket->can($id, UPDATE)) {
            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
            continue;
         }
         if (in_array((int)$ticket->fields['status'], [CommonITILObject::SOLVED, CommonITILObject::CLOSED], true)) {
            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
            $ma->addMessage(sprintf(__('Ticket #%s is already solved or closed.', 'bulkresolve'), $id));
            continue;
         }
         if (!$ticket->update(['id' => $id, 'itilcategories_id' => $category_id])) {
            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
            $ma->addMessage(sprintf(__('Ticket #%s category update failed.', 'bulkresolve'), $id));
            continue;
         }

         $solution_input = [
            'itemtype' => Ticket::getType(),
            'items_id' => $id,
            'content'  => $content,
         ];
         if ($solution_type_id > 0) {
            $solution_input['solutiontypes_id'] = $solution_type_id;
         }

         $solution = new ITILSolution();
         if ($solution->add($solution_input)) {
            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
         } else {
            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
            $ma->addMessage(sprintf(__('Ticket #%s solution failed.', 'bulkresolve'), $id));
         }
      }
   }
}
