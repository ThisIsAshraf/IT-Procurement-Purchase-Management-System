# Database design

The schema is relational and separates master data, transaction headers, transaction lines, approvals, documents, notifications and audit history. Monetary values use DECIMAL rather than floating-point. Currency codes reference currencies; transaction records retain their currency. Exchange rates are dated and uniquely keyed by source, target and effective date so historic purchase values remain reproducible.

## Core relationship map
- countries 1:N branches; currencies 1:N branches and transactions.
- branches/departments 1:N employees, budgets and requests.
- users M:N roles; roles M:N permissions; users M:N branch scopes.
- purchase_requests 1:N purchase_request_items; requisitions 1:N requisition_items.
- vendors 1:N contacts, addresses, quotations, purchase orders, invoices and payments.
- requisitions 1:N quotations and purchase orders; quotations 1:N quotation_items.
- purchase_orders 1:N purchase_order_items, deliveries and invoices.
- deliveries 1:N delivery_items; invoices 1:N invoice_items.
- payments M:N invoices through payment_allocations.
- approval_workflows 1:N approval_steps; approval_instances 1:N approval_actions.
- attachments M:N business records through attachment_links.

## Normalization notes
Lookup values are normalized (countries, currencies, categories, units, payment terms). Repeating item data is held in line tables. Vendor contact/address data is separated from vendor master. Payments and invoices are separate with an allocation bridge. Approval actions are immutable event rows. Avoid copying vendor or employee names into transactional tables; store foreign keys and snapshot only legal document facts where accounting rules require it.

## Future integration
Use `external_system`, `external_reference` and versioned API mappings in a future integration migration. Never make procurement foreign keys point into the separate asset database.

## Referential-integrity exception
`attachment_links` and `approval_instances` use a typed `(entity_type, entity_id)` reference to support several document types without duplicating link tables. MySQL cannot enforce a normal foreign key across multiple target tables; the application must validate these target records. If strict referential integrity is required for a regulated deployment, replace these polymorphic links with dedicated bridge tables per document type.
