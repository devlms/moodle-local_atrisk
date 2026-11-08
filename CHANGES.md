# Changes

## 1.1.0 (2026-11-08)

Redesigned pages, same features.

- The category page says when the list was rebuilt and when it will be next.

## 1.0.0 (2026-11-08)

First stable release.

- Only courses that are running now are checked: courses that ended or have not started no longer flag students.
- Missed deadlines leave out offline assignments and group submission assignments, and respect a student's own due date override.
- Recording a contact no longer fails when the flags were rebuilt while the form was open.
- Deleting a course removes its flags and contact notes.
- The category list counts flags with one query, so it stays fast on large sites.
- The contact note is required on the server too.
- Category names with "&" no longer show as "&amp;".
- CI covers Moodle 4.5, 5.0, 5.1, 5.2 and 5.3 on PostgreSQL and MariaDB.

## 0.1.0 (2026-11-06)

First alpha.
