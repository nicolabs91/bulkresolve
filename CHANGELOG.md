# Changelog

## 0.1.1 - 2026-06-10

### Fixed

- Reject categories that are not valid for a ticket's entity or incident/request type.
- Render GLPI solution template variables before saving the solution.
- Prevent concurrent bulk actions from adding duplicate solutions to the same ticket.
- Roll back the category update if adding or rendering the solution fails.
- Reuse the same validated processing path on the standard mass action and plugin page.
- Reject attempts to resolve tickets that are already solved or closed.

### Added

- Transactional ticket processing and per-ticket concurrency locking.
- Regression coverage for permissions, multiple tickets, invalid input, templates, rollback, and concurrency.
- Browser end-to-end coverage for both supported user flows.
- Automated PHP 8.2, 8.3, and 8.4 syntax checks.

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
