# Feature matrix

| Feature | Included in this build | Notes |
|---|---|---|
| Global branches/departments | Yes | Country and default currency references |
| Multi-currency | Schema | Original currency is retained; exchange rates are dated |
| RBAC | Yes | Permission keys enforced in controllers |
| Vendor management | Basic CRUD | Contacts/addresses have normalized tables; UI expansion needed |
| Purchase requests/requisitions | Basic CRUD/schema | Item-line workflow and conversions require further UI/service work |
| Quotations/comparison | Schema + basic CRUD | Weighted scoring UI is a follow-on enhancement |
| Purchase orders | Basic CRUD/schema | PDF template and vendor send flow need final integration |
| Configurable approvals | Schema + action inbox | Configure workflow steps before using; any/all multi-approver rules require further test coverage |
| Delivery receiving | Basic header CRUD/schema | Line-level receiving and PO remaining-quantity enforcement need follow-on workflow |
| Invoices/payments | Basic header CRUD/schema | Payment allocation and three-way match need additional transactional UI |
| Budgets | Basic CRUD/schema | Budget reservation/consumption controls need implementation |
| Email | Queue + SMTP worker | Configure SMTP and cron; no delivery guarantees from app alone |
| CSV export | Yes | Selected report datasets |
| PDF export | Yes | Requires Dompdf |
| CSV/XLS/XLSX vendor import | Yes | Vendor records only; Excel formats require PhpSpreadsheet |
| Excel export | Yes | Supported report datasets; requires PhpSpreadsheet |
| Saved report builder | Not yet | Planned extension |
| Audit logging | Yes | Key auth and create/approval actions; expand coverage |
| Asset Management integration | Not connected | Future API integration by design |

This is a working procurement MVP and modular foundation, not a production-certified ERP. Some end-to-end processes still need their item-line forms, document delivery integrations, branch-scope enforcement, saved report builder and broader import previews. Validate all business rules, permissions, SQL migrations and workflows with MySQL 8 before production deployment.
