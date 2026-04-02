# Zoro — Transactions Module Guide

This guide helps you verify and use the Transactions feature in your PHP + MySQL MVC app hosted in a subfolder.

Base URL:
- http://localhost/Zoro/public

---

## 1) URLs to test (always use /public)

✅ List page:
- GET  /transactions
- http://localhost/Zoro/public/transactions

✅ Create:
- GET  /transactions/create
- POST /transactions/create

✅ Edit:
- GET  /transactions/edit?id=123
- POST /transactions/edit

✅ Delete:
- POST /transactions/delete

✅ Bulk actions (Advanced mode):
- POST /transactions/bulk

⚠️ DO NOT open routes file directly:
- ❌ http://localhost/Zoro/routes/web.php  
It’s included by `public/index.php`, so `$router` will be undefined if you open it directly.

---

## 2) How to use (Simple vs Advanced)

### Simple Mode
- Open Transactions list
- Tap **+ Add**
- Fill minimal fields (Type, Amount, Category, Date)
- Save
- Edit/Delete from list

### Advanced Mode
- Switch mode using the **Switch** button in the UI
- You get:
  - Filters (search, type, category, date range)
  - Bulk select + bulk delete
  - Bulk set category

---

## 3) Database expectations

### Required table: `transactions`
Minimum columns expected by the module:
- id (INT / PK / AUTO_INCREMENT)
- user_id (INT)
- type (VARCHAR)  -> 'income' or 'expense'
- amount (DECIMAL / FLOAT)
- category (VARCHAR)
- description (TEXT / VARCHAR)
- created_at (DATETIME) recommended

### Date column (important)
Your DB error showed `occurred_on` did not exist.

So the Transaction model automatically detects the real date column in this priority order:
1) occurred_on
2) transaction_date
3) txn_date
4) date
5) occurred_at
6) created_at
(or first DATE/DATETIME/TIMESTAMP found)

Internally it aliases the detected column as:
- `occurred_on` (for controller + views)

So the UI always works even if your DB column name differs.

---

## 4) Bulk actions API (Advanced mode)

### Bulk Delete
POST /transactions/bulk
Form body:
- action = delete
- ids[] = 1
- ids[] = 2
- ...

### Bulk Set Category
POST /transactions/bulk
Form body:
- action = set-category
- category = Food
- ids[] = 1
- ids[] = 2
- ...

Response:
- JSON `{ "ok": true }` on success
- JSON `{ "ok": false, "message": "..." }` on error

---

## 5) Common issues & fixes

### A) Transactions page opens but no data shows
- Add a transaction from `/transactions/create`
- Confirm your `transactions.user_id` matches the logged-in user id

### B) Unknown column errors (like occurred_on)
- The model should handle this now via auto-detect.
- If you still see the error:
  - Confirm you replaced `app/Models/Transaction.php` properly
  - Confirm your table name is exactly `transactions`

### C) Opening `routes/web.php` gives $router undefined
✅ Normal.
Always test via:
- http://localhost/Zoro/public/...

---

## 6) Next upgrades (optional roadmap)

- Category dropdown loaded from DB (distinct categories)
- Pagination (limit + page)
- Sorting (date/amount)
- Attachments/receipt images (future)
- CSV/PDF exports under Reports
- Audit logs: record create/update/delete to `audit_logs`
- Security: session tracking + logout all devices

---
