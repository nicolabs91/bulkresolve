# Changelog

## 0.1.0 - 2026-05-06

Initial release.

### Added

- README now mentions that the plugin was created with AI assistance and still needs additional testing before production use.
- Clarified that the plugin is intended for closing/resolving tickets in bulk with a required category.
- Bulk ticket action **Resolve with category** under the standard GLPI ticket list **Actions** dropdown.
- Required ITIL category field for bulk resolution.
- Required solution text field.
- Optional solution type field.
- Solution template selector support.
- Ticket permission checks before processing.
- Validation for missing category, missing solution text, invalid category, and invalid solution type.
- Handling for already solved or closed tickets.
- Uses GLPI `ITILSolution::add()` to resolve tickets through the normal GLPI solution workflow.
- No database schema changes.

### Tested

- Tested on GLPI `11.0.7`.
- Verified bulk action appears under ticket list **Actions**.
- Verified selected tickets receive category, solution, solved status, and solved date.
