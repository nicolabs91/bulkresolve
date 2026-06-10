# Bulk Resolve Tickets

GLPI plugin to bulk close/resolve multiple tickets **with a required category** from the standard ticket list **Actions** menu.

The plugin adds a bulk action named **Resolve with category** to GLPI tickets. It lets an operator select multiple open tickets, choose a required ITIL category, optionally choose a solution type or solution template, enter a solution, and close/resolve all selected tickets in one action. Its main purpose is explicitly: **closing tickets in bulk while forcing a category to be set**.

## Features

- Bulk close/resolve tickets while forcing a category to be selected.
- Adds **Resolve with category** to the standard ticket list **Actions** dropdown.
- Requires an ITIL category before resolving tickets.
- Adds one solution text to every selected ticket.
- Optionally applies a GLPI solution type.
- Supports existing GLPI solution templates.
- Uses GLPI's native `ITILSolution` workflow so tickets are moved to solved status normally.
- Skips already solved or closed tickets.
- Respects GLPI ticket update and solution creation permissions.
- No database schema changes.

## Requirements

- GLPI `>= 11.0.0` and `< 12.0.0`
- Tested with GLPI `11.0.7`

## Installation

1. Download `bulkresolve-0.1.1.zip`.
2. Extract it into your GLPI plugins directory:

   ```bash
   unzip bulkresolve-0.1.1.zip -d /var/www/glpi/plugins/
   ```

   After extraction, the plugin must be located at:

   ```text
   /var/www/glpi/plugins/bulkresolve
   ```

3. In GLPI, go to **Setup > Plugins**.
4. Install and enable **Bulk Resolve Tickets**.

CLI alternative:

```bash
php /var/www/glpi/bin/console plugin:install bulkresolve
php /var/www/glpi/bin/console plugin:activate bulkresolve
```

## Usage

1. Go to **Assistance > Tickets**.
2. Select one or more open tickets from the list.
3. Click **Actions**.
4. Choose **Resolve with category**.
5. Select a category.
6. Optionally select a solution template or solution type.
7. Enter the solution text.
8. Click **Resolve**.

## Notes

- The separate plugin page is not added to the GLPI menu; the intended workflow is the standard ticket list **Actions** button.
- The plugin does not create or modify database tables.
- Existing GLPI categories, solution types, and solution templates are used.

## Testing

Version `0.1.1` was tested on GLPI `11.0.7` with:

- PHP syntax checks on PHP 8.2, 8.3, and 8.4.
- Standard ticket-list mass action and separate plugin-page browser flows.
- Category validation across ticket types and entities.
- Permission checks for users with and without ticket update rights.
- Solution templates, invalid templates, solution types, and rollback behavior.
- Multiple selected tickets, already solved tickets, and concurrent processing.

The plugin was created with the help of AI and has no database schema changes.

## Author

Nicolabs91

## License

GPLv3+
