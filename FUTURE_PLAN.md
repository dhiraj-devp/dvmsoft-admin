# Dvmsoft Admin OS — Future Plan

This file is a planning reference only. None of these features are implemented in the current foundation except where the core already provides authentication, RBAC, settings, audit logs, and navigation hooks.

Keep this document updated as modules move from planned to in progress to shipped.

## Current foundation (Phase 0)

Purpose: run the internal OS safely before business modules exist.

Shipped now:

- Internal staff authentication at `/login` on the staff/admin host (`ADMIN_URL`, e.g. `admin.dvmsoft.com`)
- Dedicated Super Admin operator sign-in on the same host and `web` guard (`users.is_super_admin`), unpublished from staff navigation and the staff login page
- Separate Client Portal host (`CLIENT_URL`, e.g. `client.dvmsoft.com`) using the `client` guard — not employee auth or internal RBAC
- Dashboard shell
- Users, roles, permissions
- Settings and branding
- Audit logs
- Permission-aware navigation
- Theme support

Dependencies: Laravel, MySQL, Livewire, Tailwind.

Suggested development phase: **0 — complete**

---

## CRM & Sales

Purpose: capture demand, manage accounts, and convert opportunities into work.

Status: **implemented** (Phase 1 complete)

Shipped now:

- Leads with pipeline statuses, assignment, follow-up dates, notes, and activity history
- Clients (individual/company) with GST/PAN, account managers, and contacts
- Lead → Client conversion without duplicating an existing email
- Follow-ups with type, reminder, owner, and status
- Quotations with line items, tax/discount totals, workflow, and PDF
- Sales dashboard, granular CRM permissions, and Sales sidebar navigation
- Audit logging for CRM create/update/delete and quotation status changes
- Converted quotations can create a delivery project (Projects module)

Dependencies: users, roles, permissions, settings, audit logs, notifications.

Suggested development phase: **1 — complete**

---

## Projects & Task Management

Purpose: deliver sold work with ownership, dates, and visibility.

Status: **implemented** (Phase 2 complete)

Shipped now:

- Projects with number, client, optional quotation, manager, team, budget, dates, status, health, and priority
- Milestones, tasks (with estimated/actual hours), requirements (with attachments and client approval), and change requests
- Project dashboard and a tabbed project workspace, including a Delivery tab for stage-based execution
- Stage-based delivery workflow (Project → Stages → Tasks → Deliverables / evidence → client review → next stage)
- Project-level `client_approval_mode` (strict/flexible) with per-stage inherit/required/not-required overrides
- Stage work evidence, deliverables, versioned review submissions, and internal vs client discussion
- Quotation (Converted) → Project without duplicating client data
- Granular project and stage permissions, permission-aware Projects navigation, and audit logging

Still later: @mentions in stage discussion, auto-start of the next stage after completion, sharing arbitrary project files with clients.

Architecture: stages are a new ordered layer on the existing `projects` / `tasks` records. Milestones remain free-form checkpoints. Progress is `(completed tasks + completed deliverables) / (tasks + deliverables)` per stage, then averaged for the project when stages exist; projects without stages keep the previous task-then-milestone formula. Private evidence uses the `stages` disk. Client Portal reuses `ClientAccess` (404 IDOR) and never receives internal notes, internal discussion, employee names, or profitability data.

Dependencies: CRM clients, users, departments, notifications.

Suggested development phase: **2 — complete**

---

## Stage-Based Project Delivery Workflow

Purpose: turn project execution into an ordered stage-gate with client review, without replacing Projects, Tasks, or the Client Portal.

Status: **implemented**

Architecture:

- Existing `projects` gained `client_approval_mode` (`strict` | `flexible`)
- New `project_stages` layer (sequence, owner, members, dates, notes, review flags) on the same project
- Existing `tasks.stage_id` is optional; milestones stay independent checkpoints
- `project_stage_deliverables`, `project_stage_evidence` (private `stages` disk or external URL), `project_stage_submissions` (versioned reviews), `project_stage_messages` (internal vs client)
- `ProjectStageWorkflowService` owns gates, submission validation, approval, and completion
- Staff Delivery tab + Client Portal timeline reuse `StaffNotifier` / `ClientPortalNotifier` (`projects.stage_activity`, `projects.stage_overdue`, `portal.activity`)

Progress: stage = (completed tasks + completed deliverables) / (tasks + deliverables); empty stages are 100% only when approved or completed. Project progress averages stages when any exist.

Intentionally deferred:

- @mentions in stage discussion
- Automatically starting the next stage when one completes
- Sharing arbitrary project files (portal files remain approved-requirement attachments plus client-visible stage evidence)
- Knowledge base, WhatsApp, chatbot, payroll, e-signature, SaaS, mobile

Dependencies: projects, tasks, client portal, notifications, automations, RBAC, audit logs, settings.


## Finance & Accounting

Purpose: bill work, collect payment, and understand cash position.

Status: **implemented** (Phase 3 complete)

Shipped now:

- Invoices (`INV-{YEAR}-0001`) with client, optional project and quotation IDs, line items, GST, discounts, payment terms, and statuses (Draft → Sent → Partially Paid → Paid → Overdue → Cancelled)
- Payments that update invoice paid/balance status automatically
- Expenses with numbering, categories, optional project, receipts, and statuses
- Outstanding receivables (total, paid, balance, due date, days overdue)
- Finance dashboard (revenue, paid, outstanding, overdue, expenses, net profit, this-month figures, recent activity)
- Project profitability (budget, invoiced, paid, expenses, estimated and actual profit)
- Invoice and payment-receipt PDFs from company/finance settings (no hardcoded company data)
- Reminder architecture via `finance:send-invoice-reminders` (in-app/email records; WhatsApp not sent)
- Granular finance permissions and permission-aware Finance navigation
- Audit logging for invoice, payment, and expense create/update/delete

Not in this phase: credit notes, WhatsApp automation, or AI.

Dependencies: clients, projects, quotations, settings (currency, GST, PAN, bank/UPI), audit logs.

Suggested development phase: **3 — complete**

---

## HR & Employee Management

Purpose: manage people operations beyond login accounts.

Status: **implemented** (Phase 4 complete — employee management only; payroll is later)

Shipped now:

- Employee profiles linked 1:1 to login accounts (code, name, email, phone, department, job title, manager, joining date, employment type/status, photo, address, emergency contact, notes)
- Private employee documents (offer letter, employment agreement, NDA, IP assignment, ID, other) with upload/download/delete, status, and expiry — stored on a private `hr` disk, never public URLs
- Employment fields: Full-time / Part-time / Contract / Intern, probation period/status, confirmation date, exit date/reason
- Leave types, requests, balances, and workflow (Pending → Approved / Rejected → Cancelled)
- Employee assets with assign/return, serial number, condition, and notes
- Onboarding and offboarding checklists
- HR dashboard (totals, active, probation, pending leave, upcoming joiners/exits, missing documents, recent activity)
- Granular HR permissions; payroll/salary permissions exist as stubs only and are not granted to HR Manager or Employee roles
- Permission-aware HR navigation and audit logging for employee, document, leave, asset, and checklist changes
- HR settings: employee code prefix, default probation days, default annual leave days

Not in this phase: payroll/salary structures, attendance, or AI.

Dependencies: users, departments, roles, settings, audit logs.

Suggested development phase: **4 — complete** (payroll remains later)

---

## Support & Ticketing

Purpose: handle client and internal issues in one queue.

Status: **implemented** (Phase 5 complete)

Shipped now:

- Tickets numbered `TKT-{YEAR}-0001` with client, optional project, subject, description, category, priority, status, assignee, creator, SLA deadline, resolution, and closed date
- Status workflow: Open → In Progress → Waiting for Client → Resolved → Closed
- Priorities: Low, Normal, High, Urgent
- Conversation with replies, internal notes, attachments, and timestamps — internal notes are hidden without `tickets.internal_notes` and never shown to clients
- Configurable ticket categories
- Configurable SLA rules by priority (deadline, remaining time, breached)
- Support dashboard: open, in progress, waiting, SLA breached, resolved today, by priority/status, my assigned tickets, recent activity
- Tickets link to existing client and optional project IDs (no duplicated client/project records)
- Granular ticket permissions and permission-aware Support navigation (Overview, Tickets, Categories, SLA)
- Email/in-app notifications for created, assigned, reply, resolved, SLA approaching, and SLA breached (`support:check-sla`; WhatsApp not sent)
- Audit logging for ticket, message, category, and SLA changes
- Private attachment storage on the `support` disk

Not in this phase: AI, canned replies knowledge base, or CSAT.

Dependencies: clients, projects, users, notifications, email, audit logs.

Suggested development phase: **5 — complete**

---

## Document Management

Purpose: store company files with versioning and approval control.

Status: **implemented** (company document registry; HR employee documents and Project Files remain separate)

Shipped now:

- Company documents numbered `DOC-{YEAR}-0001` with title, configurable type, description, private file, version, status, owner, creator, approver, expiry, and notes
- Status workflow: Draft → Review → Approved → Sent → Signed → Archived
- Configurable types: Company, Legal, Contract, Policy, Finance, HR, Sales, Project, Client, Other
- Version history (label, file, uploader, date, change notes) without destroying previous files
- Approval: submit for review, approve, reject (returns to Draft), approval notes, approver, timestamp
- Private `documents` disk only — no public URLs; downloads go through authorization (`documents.download`)
- Search and filters (title, number, type, owner, status, related client, expiry) with pagination
- Optional ID links to existing client, project, employee, quotation, and invoice records (no duplicated entity data)
- Documents dashboard: total, draft, pending review, approved, expiring soon, recently uploaded, recent activity
- Granular permissions, permission-aware Documents navigation (Overview, All Documents, Document Types, Expiring)
- Email/in-app notifications for submitted, approved, rejected, and expiring soon (`documents:notify-expiring`; WhatsApp not sent)
- Audit logging for create, upload, download, version creation, status changes, approval/rejection, archive, and delete
- Settings: document prefix, expiry warning days, version format (`v{n}`)

Not in this phase: AI classification, knowledge base, payroll, or advanced e-signature integration. HR Documents and Project Files are unchanged and may later link into this registry.

Dependencies: users, permissions, settings, audit logs, notifications, clients, projects, employees, quotations, invoices.

Suggested development phase: **complete** (shipped after Support)

---

## Client Portal

Purpose: give clients a separate, authenticated window into their work.

Status: **implemented** (isolated client auth over existing records; self-registration, online payments, and granular per-user portal flags remain later)

Shipped now:

- Dedicated Client Portal host (`CLIENT_URL`, locally `client.dvmsoft.test`) with canonical `/login` — not employee `/login` or internal RBAC
- Separate client authentication (guard `client`, table `client_users`) on that host; legacy `/client/...` redirects to the client origin
- Host-only sessions with separate cookie names so staff and client auth are never shared across domains
- Multiple portal users per existing Client (name, email, password, active/inactive, email verification, password reset)
- Staff manage portal users from the client record (`clients.manage_portal`); Sales Manager and Admin are granted it
- Dashboard: active projects and progress, pending quotations, outstanding invoices, open tickets, pending change requests, stages awaiting approval, recently completed stages, recent activity
- Projects: status, health, progress, stage timeline, current stage, client-visible tasks/deliverables/evidence, stage discussion (public only), review/approval, milestones, approved requirements, and files attached to approved requirements
- Company documents explicitly linked to the client (Approved/Sent/Signed/Archived) via authorized private downloads
- Quotations: view/PDF, accept/reject on Sent/Viewed using the existing quotation workflow and audit log
- Invoices and payments: read-only list, status, PDF/receipts — no finance mutations
- Tickets: create, view, reply, attachments; SLA-facing status; internal notes never shown
- Change requests: submit and view Pending/Approved/Rejected/Implemented on the existing model (clients do not approve)
- Profile: own name and password only — client master data is not editable from the portal
- Ownership scoping on every query/route, IDOR 404s, rate-limited auth and sensitive actions, audit logging for login/logout, quotation accept/reject, ticket create/reply, change-request create, and file/document downloads
- Separate client layout, branding from Settings, light/dark/system theme

Intentionally deferred:

- Client self-registration
- Granular per-user portal permission flags (all active users of a client currently share the same client-scoped access)
- Staff toggle to share arbitrary project files (portal files are those attached to approved requirements)
- Online payment / payment gateway
- Quotation comments beyond accept/reject
- AI inside the Client Portal (internal staff AI is shipped separately)
- Knowledge base, payroll, e-signature, SaaS multi-tenancy, or mobile app

Dependencies: CRM, projects, finance, support, documents, branding settings.

Suggested development phase: **complete** (shipped after Reports)

Architecture note: employee auth and client auth stay separate. They share data models, not sessions, cookies, or RBAC. Staff uses `users` + the `web` guard on `ADMIN_URL`; clients use `client_users` + the `client` guard on `CLIENT_URL`.

---

## Reports & Analytics

Purpose: show leadership what is happening across the company.

Status: **implemented** (central reports over existing records; saved reports and scheduled email delivery remain later)

Shipped now:

- Company overview at `/reports` with revenue, expenses, profit, outstanding, active projects, open tickets, employees, leads, and pipeline value — each KPI only if the user has that report permission
- Sales: leads by status, conversion rate, pipeline value, won revenue, lost opportunities, sales by source, quotations sent/accepted/rejected, performance by user
- Projects: by status and health, completion, delayed projects, task and milestone completion, budget vs actual and profitability via the existing profitability service
- Finance: revenue, expenses, profit, outstanding, overdue, monthly revenue/expense trends, payment collection, revenue by client and project — same billed/expense rules as Finance
- HR: totals, active, by department and employment type, leave statistics, joiners/exits — no salary or payroll fields
- Support: tickets by status, priority, category, and assignee; SLA breaches; average resolution time
- Date range: today, this week, this month, this quarter, this year, custom
- CSV export for authorized users, audited as `reports` / `exported`
- Granular permissions and Reports navigation (Overview, Sales, Projects, Finance, HR, Support)
- Existing Finance → Reports (project profitability) is unchanged

Not in this phase: knowledge base, payroll, saved/scheduled reports, or new business records.

Dependencies: CRM, projects, finance, HR, support, permissions, audit logs.

Suggested development phase: **complete** (shipped after Company Documents)

---

## Notifications & Email

Purpose: tell the right person when something needs attention.

Status: **implemented** (in-app center, email channel, and per-user preferences; email digests remain later)

Shipped now:

- In-app notification center (staff and client portal) with unread count, mark read, and preferences
- Email + in-app channels through Laravel notifications, company defaults in Settings, and per-user enable/disable
- Duplicate prevention via existing reminder flags, SLA/document timestamps, invoice reminder rows, and automation dispatch fingerprints
- Client portal event notifications that never include internal notes or internal-only fields

Intentionally deferred:

- Email digest / batched summary mail
- Per-automation mute matrix beyond email and in-app toggles
- WhatsApp delivery

Dependencies: notifications table, settings email/notifications groups, queues, mail configuration, automations engine.

Suggested development phase: **complete** (shipped with Automation & Workflow Engine)

---

## Automation & Workflow Engine

Purpose: make Dvmsoft Admin OS proactive from a single catalog instead of scattered reminder jobs.

Status: **implemented** (central engine over existing CRM, Projects, Finance, HR, Support, Documents, and Client Portal records)

Shipped now:

- Catalog + `automations` state (name, module, trigger, channels, enabled, last run, status, error) and `automation_runs` history — not one row per business event
- Laravel scheduler `automations:run` every 15 minutes; existing artisan commands remain as wrappers (`crm:send-follow-up-reminders`, `finance:send-invoice-reminders`, `support:check-sla`, `documents:notify-expiring`)
- CRM: due follow-ups (reuses `reminded_at`), upcoming/overdue lead follow-ups, quotation follow-up
- Projects: upcoming/overdue milestones, overdue and blocked tasks, approaching deadlines, yellow/red health, overdue stages, event-driven stage activity
- Finance: invoice due, overdue, and outstanding — reuses `InvoiceReminderService` / `invoice_reminders` (no WhatsApp)
- HR: pending leave, upcoming joining/exit, missing or expiring employee documents (no salary data)
- Support SLA approaching/breached — reuses `TicketSlaService` notification flags
- Company documents: expiry approaching (reuses `expiry_notified_at`) and expired
- Client Portal event hooks: tickets/replies (public only), quotation decisions, change-request status, invoice send, client-linked document approval, stage start/ready-for-review/completed/discussion (never internal)
- Internal `/automations` dashboard with filters, enable/disable, channels, and execution history
- Permissions `automations.view`, `automations.manage`, `automations.history.view`; Settings → Automations for global enable and reminder timing
- Audit of enable/disable, channel changes, and failed executions without storing notification bodies

Intentionally deferred:

- WhatsApp, chatbot, knowledge base
- Email digest
- Auto-send of quotations or invoices
- Payroll, e-signature, SaaS multi-tenancy, mobile app
- Generic workflow builder / custom automation rules beyond the catalog

Dependencies: CRM, projects, finance, HR, support, documents, client portal, notifications, settings, permissions, audit logs, queues.

Suggested development phase: **complete** (shipped after AI)

Guardrail: scheduled jobs are idempotent. Client portal users never access `/automations`. Internal notes and sensitive HR/finance internals are not sent to clients.

---

## WhatsApp Automation

Purpose: send and receive operational messages where clients already are.

Key features:

- Template messages for quotations, invoices, and ticket updates
- Opt-in / opt-out tracking
- Conversation assignment to support or sales
- Delivery and failure logs

Dependencies: clients, notifications, queues, provider credentials in settings, audit logs.

Suggested development phase: **7**

---

## AI Automation

Purpose: reduce repetitive internal work without replacing ownership.

Status: **implemented** (assistance layer over existing records; chatbot, email/WhatsApp automation, knowledge base, and document classification remain later)

Shipped now:

- Provider-agnostic AI layer: `AiServiceInterface`, `AiManager`, OpenAI + Fake providers, structured JSON responses, timeouts, sanitization of secrets, and env-only API credentials
- Settings → AI: enable/disable, provider, model, timeout, max tokens — API keys are never stored or shown in the UI
- AI Lead Assistant on lead detail: summary, requirement/need/urgency/risks, suggested status, next follow-up, draft message — never changes status or sends
- AI Requirement Assistant: clarified requirement, acceptance criteria, questions, risks, suggested priority — saved only after explicit Apply
- AI Quotation Assistant: title, line-item descriptions, missing pricing questions, payment-term wording — never invents prices or changes totals
- AI Project Assistant: status, overdue/blocked tasks, milestone and change-request risks, next actions
- AI Support Assistant: conversation summary, likely category/priority, troubleshooting steps, client-safe draft reply — never sent automatically; internal notes stay out of client drafts
- AI Finance Insights at `/ai/finance`: overdue patterns, unusual expenses when history exists, receivables, attention list, collection suggestions — not accounting/legal advice; records unchanged
- Company risk summary at `/ai/overview`: sales, project, finance, support, operational items, suggested actions from existing reports/records
- Permissions (`ai.*`), Client Portal isolation, audit logs without storing prompts/responses, rate limiting

Intentionally deferred:

- Generic AI chatbot / knowledge base
- WhatsApp or email automation
- Document classification
- Auto-send of messages, quotations, or invoices
- Invented pricing or silent finance mutations
- Payroll, e-signature, SaaS multi-tenancy, mobile app, advanced integrations

Dependencies: CRM, projects, finance, support, reports, settings, permissions, audit logs.

Suggested development phase: **complete** (shipped after Client Portal)

Guardrail: AI output must be reviewable. Never auto-send financial or legal documents without a permissioned approve action.

---

## Vendor Management

Purpose: track agencies, freelancers, and suppliers.

Key features:

- Vendor records, contracts, and contacts
- Purchase orders and bills
- Performance notes
- Link vendors to projects and expenses

Dependencies: finance, projects, documents, users.

Suggested development phase: **6**

---

## Subscription / SaaS features

Purpose: only if Dvmsoft later productizes Admin OS for other companies.

Key features:

- Tenants, plans, billing, and usage limits
- Per-tenant branding and isolation
- Seat management
- Admin of admins

Dependencies: strong tenancy design, billing provider, isolated file and data storage.

Suggested development phase: **later / optional**

Do not introduce multi-tenancy until the internal OS is complete. The current app is a single-company system.

---

## Mobile App

Purpose: give employees dashboard, approvals, and tickets on the phone.

Key features:

- Auth against the same employee accounts
- Approvals, timesheets, tickets, and notifications
- Read-only dashboards
- Secure token storage

Dependencies: API layer (Sanctum or equivalent), existing permissions, notifications.

Suggested development phase: **9**

---

## Security & Compliance

Purpose: raise the floor as more sensitive modules go live.

Key features:

- Two-factor authentication
- Device / session management
- Data retention and export
- Access reviews and permission recertification
- Backup and restore runbooks
- Field-level encryption for secrets (GST documents, salary data)

Dependencies: current audit log, users, settings/security group.

Suggested development phase: **ongoing from Phase 1**

---

## Advanced integrations

Purpose: connect the OS to the rest of the stack.

Key features:

- Accounting exports
- Calendar and email sync
- Payment gateways
- Git / deployment status for project delivery
- Storage providers for large documents

Dependencies: settings, queues, audit logs, module-specific APIs.

Suggested development phase: **7–9**, as each module needs them

---

## Suggested sequence

| Phase | Focus |
| --- | --- |
| 0 | Foundation (current) |
| 1 | CRM & Sales + basic notifications |
| 2 | Projects & tasks |
| 3 | Finance (complete) |
| 4 | HR (complete) — payroll still later |
| 5 | Support (complete) |
| 6 | Company documents (complete) |
| 7 | Reports & analytics (complete) — saved/scheduled reports still later |
| 8 | Client portal (complete) — self-registration, online pay, and per-user portal flags still later |
| 9 | AI automation (complete) — chatbot, WhatsApp/email automation, knowledge base, and document classification still later |
| 10 | Notifications + Automation & Workflow Engine (complete) — email digests, WhatsApp, custom workflow builder still later |
| 10b | Stage-based project delivery (complete) — @mentions and auto-start next stage still later |
| 11 | Vendors, richer email |
| 12 | WhatsApp and integrations |
| 13 | Mobile app |

When a module starts, update this file with owner, status, and any new dependencies discovered during build.
