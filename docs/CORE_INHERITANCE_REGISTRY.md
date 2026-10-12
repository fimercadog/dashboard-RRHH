# CORE_INHERITANCE_REGISTRY

Capabilities intended to be inherited by every new vertical. Status reflects repository evidence, not an automatic approval.

- Multi-tenancy: company scope and backend authorization. Review tests per task.
- Shared CRUD: BaseCrudController, Resources, FormRequests, DataTable and CrudModal where appropriate.
- Domain writers: StockService, PaymentService, PurchaseService, SaleService and AccountingService remain the single writers for their respective invariants.
- RBAC: manage does not imply delete; approve does not imply delete. Destructive actions require explicit permissions and cross-company tests.
- Badges, voice dictation, recruitment and pagination/performance changes are present in commit 1c7c51a49e21e4e544dfa7699d5d0c9778ef7be7, but were not re-tested during the 2026-10-11 review.
- UX-006 row numbering and UX-007 user avatar are inherited requirements; implementation was not confirmed in the inspected branch.

When a reusable change is accepted, update CORE_CONTRACT.md, MEMORY.md, DEVELOPMENT_MEMORY.md and this registry as appropriate. Never mark VERIFIED, E2E VERIFIED or DONE without evidence.