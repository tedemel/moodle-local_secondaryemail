# Secondary email notifications (local_secondaryemail)

[![Moodle Plugin CI](https://github.com/tedemel/moodle-local_secondaryemail/actions/workflows/moodle-ci.yml/badge.svg)](https://github.com/tedemel/moodle-local_secondaryemail/actions/workflows/moodle-ci.yml)

A Moodle plugin that lets each user store a **verified secondary email
address** and forwards selected notifications to it — for example to a
parent, guardian or employer.

## Features

- **Managed profile field** — the plugin creates a custom profile field
  `secondaryemail` (category "Additional email") on installation and protects
  it against renaming, deletion and type changes, both server-side (event
  observers + hooks) and in the profile field admin UI (AMD module).
- **Double opt-in verification** — every new or changed secondary address
  receives a confirmation email with an expiring token link. Only verified
  addresses ever receive notification copies.
- **Notification whitelist** — nothing is forwarded by default. Admins select
  exactly which message providers (forum posts, personal messages, …) are
  copied to the secondary address.
- **User opt-out** — optionally, users may disable individual providers that
  the admin has enabled.
- **Admin report** — a report builder based overview of all users with
  status badges (verified / pending / not verified / blocked), resend, edit,
  block, delete and relationship-tag actions.
- **Relationship tags** — optionally tag each secondary address (mother,
  father, guardian, employer, …) with configurable tag lists.
- **GDPR compliant** — full privacy provider: metadata declaration, export
  (tokens masked), and deletion for all stored data.
- **Web services** — `local_secondaryemail_get_status` and
  `local_secondaryemail_resend_confirmation`, both available to the mobile
  service.

## Requirements

- Moodle 4.5 or later (tested up to Moodle 5.2)
- PHP 8.2 or later

## Installation

1. Copy the plugin to `local/secondaryemail` (Moodle 4.5) or
   `public/local/secondaryemail` (Moodle 5+), or install the zip via
   *Site administration → Plugins → Install plugins*.
2. Complete the upgrade. The profile field and its category are created
   automatically.

## Configuration

*Site administration → Plugins → Local plugins → Secondary email
notifications*

| Setting | Purpose |
| --- | --- |
| Verification link expiry | Hours a confirmation link stays valid (0 = no expiry). |
| Enable tagging | Turn relationship tags on/off. |
| Available tags | One tag per line. |
| Enable notifications | Whitelist of message providers forwarded to the secondary address. **Empty by default — nothing is sent.** |
| Allow users to customize notifications | Let users disable individual admin-enabled providers. |

The user report lives under *Site administration → Users → Users with
secondary email*.

## Capabilities

| Capability | Default | Purpose |
| --- | --- | --- |
| `local/secondaryemail:manage` | Manager | Manage other users' secondary emails. |
| `local/secondaryemail:viewreport` | Manager | View the report. |
| `local/secondaryemail:configureown` | Authenticated user | Configure own notification preferences. |

## How it works

When a user profile is created or updated, an event observer checks the
`secondaryemail` profile field. A new address triggers a confirmation email
to that address; the recipient confirms via token link (no login required).
When Moodle sends a notification, the `notification_sent` observer forwards a
copy to the verified secondary address — but only for providers on the admin
whitelist and not disabled by the user.

## Support

Please report issues and feature requests via
[GitHub issues](https://github.com/tedemel/moodle-local_secondaryemail/issues).

## License

2026 Tessa Demel

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version. See [LICENSE](LICENSE).
