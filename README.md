# Serenity

<p align="center">

<img width="703" height="355" alt="image-removebg-preview (4)" src="https://github.com/user-attachments/assets/7ab95c3b-a35b-4b73-8849-584c21489f09" />

</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel_13-FF2D20?style=flat&logo=laravel&logoColor=white"/>
  <img src="https://img.shields.io/badge/PHP_8.3-777BB4?style=flat&logo=php&logoColor=white"/>
  <img src="https://img.shields.io/badge/Vue_3-4FC08D?style=flat&logo=vuedotjs&logoColor=white"/>
  <img src="https://img.shields.io/badge/Inertia.js-155dfc?style=flat&logo=inertia&logoColor=fff"/>
  <img src="https://img.shields.io/badge/Tailwind_v4-06B6D4?style=flat&logo=tailwindcss&logoColor=white"/>
  <img src="https://img.shields.io/badge/Vite_8-646CFF?style=flat&logo=vite&logoColor=white"/>
  <img src="https://img.shields.io/badge/MySQL-4479A1?style=flat&logo=mysql&logoColor=white"/>
  <img src="https://img.shields.io/badge/PNPM-F69220?style=flat&logo=pnpm&logoColor=white"/>
</p>

<p align="center">
<strong>Serenity is a management system for a multi-branch yoga centre.</strong><br/>
It runs the whole operation: branches and rooms, class schedules and sessions, member booking with a
waitlist, teacher and student attendance, lesson plans with an approval workflow, tuition invoicing
and payments, a central file library, notifications by email and in-app, and an AI assistant that
drafts asana sequences.
</p>

---

## Table of Contents

- [Screenshots](#screenshots)
- [What it does](#what-it-does)
- [Roles and authorization](#roles-and-authorization)
- [Tech stack](#tech-stack)
- [Getting started](#getting-started)
- [Demo accounts](#demo-accounts)
- [Notifications, queue and scheduler](#notifications-queue-and-scheduler)
- [AI lesson-plan assistant](#ai-lesson-plan-assistant)
- [Testing](#testing)
- [Architecture](#architecture)
- [Project structure](#project-structure)
- [Internationalisation](#internationalisation)
- [Project status](#project-status)
- [Contributing](#contributing)
- [License](#license)

## Screenshots

<p align="center">
    <img width="1910" height="911" alt="image" src="https://github.com/user-attachments/assets/c086ced8-aa16-44d2-8db9-8155a72a8819" />
</p>

<p align="center">
    <img width="1883" height="859" alt="image" src="https://github.com/user-attachments/assets/da6b114a-822c-461f-90ca-69847226bc11" />
</p>

<p align="center">
    <img width="1890" height="856" alt="image" src="https://github.com/user-attachments/assets/54845fff-fefd-4730-87e9-da6b286d4d51" />
</p>

<p align="center">
    <img width="1892" height="854" alt="image" src="https://github.com/user-attachments/assets/c88858f8-e868-4caf-8f46-748de92a606b" />
</p>

<p align="center">
    <img width="1904" height="860" alt="image" src="https://github.com/user-attachments/assets/c9e556b5-7cfe-4893-a84e-8d30c42a3968" />
</p>

## What it does

**Centre setup** — branches, rooms and class types, with a branch switcher in the top bar that scopes
every list a staff member sees.

**Scheduling** — recurring class schedules generate concrete class sessions. Sessions carry a coach, a
room, a capacity and a status (scheduled, done, cancelled).

**Booking** — members book and cancel their own places. A full session waitlists, and a cancellation
promotes the first person waiting and notifies them. The cancellation cutoff is a setting, not a
constant.

**Attendance** — coaches check in and out of their own sessions, and mark the student roster. Admins
see every branch; a coach sees only their own sessions. Monthly reports summarise both.

**Lesson plans** — a coach drafts a plan (objective, asana sequence, level, duration, up to five
attachments), submits it, and an admin approves or rejects it with a comment. Every decision is kept
as review history.

**Tuition** — tuition plans, invoices with line items, partial payments, payment proofs, voiding
rather than deleting, and a CSV export. Membership entitlements appear once an invoice is paid.

**Files** — one library over every uploaded file. A file is authorised by the record it hangs off, not
by a flat permission, so a coach cannot read a payment proof by knowing its id.

**Notifications** — nine events across in-app and email, each with per-user channel preferences. Two
scheduled commands send tuition reminders and class reminders.

**Admin** — user management, role assignment, login logs, an audit trail of sensitive actions
(including every view of a student's medical notes), and a settings page that actually persists.

**Search** — one throttled, permission-gated search box across students, coaches, invoices, lesson
plans and users. Results are row-scoped, so a coach searching a name only finds students booked into
their own sessions.

**Dashboard** — real figures, not mock data: revenue by month, sessions taught, attendance rate, new
members, plans awaiting review, class fill rate.

## Roles and authorization

Three roles, and **each user holds exactly one**: `admin`, `coach`, `member`.

**Every access decision reads a permission, never the role column.** There are 35 permissions —
admin holds 30, coach 10, member 4 — seeded by `database/seeders/PermissionSeeder.php` and enforced
three ways:

- Routes: `Route::middleware('permission:operations.plans.review')`
- Menu items: `AppMenuItem::make(...)->permissions('operations.plans.view')`
- Vue: named ability flags shared from `HandleInertiaRequests`, never the full permission list

Two roles can share some permissions and differ in others, so a permission check survives a change of
role where a role check does not. `users.role` is used only to assign permissions, to show a label,
and in audit metadata.

Permissions also come in `.any` variants — `operations.plans.view` versus `operations.plans.view.any`
— which is what narrows a coach to their own records while an admin sees the centre.

## Tech stack

| Layer | Choice |
|---|---|
| Backend | PHP 8.3, Laravel 13 |
| Auth | Laravel Fortify |
| Permissions | spatie/laravel-permission 8 |
| Files | spatie/laravel-medialibrary 11 |
| Hooks | tormjens/eventy (menu registry) |
| Routes in JS | Ziggy |
| Frontend | Inertia.js 3 + Vue 3 (Blade renders only the shell, the landing page and the mail templates) |
| Styling | Tailwind v4 and a hand-written design system in `resources/css/ui.css` |
| Charts | ApexCharts |
| i18n | laravel-vue-i18n (English and Vietnamese) |
| Build | Vite 8, pnpm |
| Database | MySQL (sqlite in-memory for tests) |
| AI | Google Gemini, called over HTTP with no SDK |
| Quality | Pint, PHPUnit 12 |

## Getting started

### Prerequisites

- PHP 8.3 or newer
- Composer
- Node.js with **pnpm** (the repo blocks npm in a `preinstall` hook)
- MySQL 8 or compatible

### Installation

```sh
git clone <repo-url>
cd yoga-management-system

composer install
pnpm install

cp .env.example .env
# set DB_DATABASE, DB_USERNAME, DB_PASSWORD
php artisan key:generate

php artisan migrate --seed
```

Then run the app with two terminals:

```sh
pnpm dev          # Vite dev server
php artisan serve # http://127.0.0.1:8000
```

For a production-style build, `pnpm build` instead of `pnpm dev`.

Sign in at `http://127.0.0.1:8000/cms/login`. The whole app lives under `/cms`.

> **After pulling changes that add a permission**, re-seed and clear the permission cache, or the new
> grant will not exist in your database:
>
> ```sh
> php artisan db:seed --class=PermissionSeeder
> php artisan permission:cache-reset
> ```

## Demo accounts

`php artisan migrate --seed` creates three accounts along with branches, rooms, class types, coaches,
students, twenty weeks of sessions, enrolments, attendance, lesson plans and invoices.

| Role | Username | Password |
|---|---|---|
| Admin | `admin` | `Admin@12345` |
| Coach | `coach` | `Coach@12345` |
| Member | `member` | `Member@12345` |

## Notifications, queue and scheduler

Nine events are defined in `config/notifications.php`, each with its own channels and the permission
that decides who can see it on their preferences card. In-app notifications always work. Email
requires a mail transport in `.env`.

Two commands are scheduled in `routes/console.php`:

```
notify:tuition-due        daily at 08:00   overdue and due-soon invoices
notify:upcoming-classes   daily at 18:00   tomorrow's classes, to members and coaches
```

Both are idempotent for the day, so running twice sends once. To see them end to end:

```sh
php artisan queue:work      # terminal 1
php artisan schedule:work   # terminal 2
```

`MAIL_ALWAYS_TO` in `.env` redirects every outgoing message to one inbox, which is how the mail path
is rehearsed without writing to real members.

## AI lesson-plan assistant

Two entry points, both advisory, neither of which ever writes to a lesson plan:

- **Coach** — "Suggest a sequence" on the plan form. Returns a structured asana sequence the coach
  edits and saves themselves.
- **Admin** — "Check this plan" on a pending plan. Returns a critique under four headings. **It is
  never wired to approve or reject.** A human always decides.

The prompt for the coach's request is built from exactly four whitelisted fields — class type, level,
duration, objective. No student record, name, medical note or attendance figure is ever sent.

An admin may optionally attach **one image** from the plan under review, behind a checkbox that is
unticked on every visit; PDFs cannot be sent, the image must belong to that plan, and each send writes
an `ai_attachment_sent` audit row naming who sent what. Prompts and responses are stored in
`ai_suggestions` as evidence.

Configuration is four keys in `.env`:

```
AI_DRIVER=fake                    # the shipped default
GEMINI_API_KEY=
GEMINI_MODEL=gemini-3.5-flash
GEMINI_TIMEOUT=20
```

`AI_DRIVER=fake` returns a canned sequence and critique, so **a fresh clone has a working demo with no
key and no network**. Set `AI_DRIVER=gemini` plus a free key from
[Google AI Studio](https://aistudio.google.com/apikey), run `php artisan config:clear`, and the same
buttons call the real API. With `gemini` set and no key, the buttons are not rendered and the
endpoints return 403 — there is no half-state.

## Testing

```sh
php artisan test        # 328 tests, 1063 assertions
vendor/bin/pint         # code style
```

Tests run against sqlite in memory and **never open a socket**: mail uses the array transport, the AI
driver is pinned to `fake`, HTTP calls are faked, and Inertia SSR is disabled for the suite. Feature
tests that create users need `$seed = true` with `$seeder = PermissionSeeder::class`.

## Architecture

**Modular, not a flat `app/Http/Controllers`.** Each feature owns its controllers, actions and form
requests under `app/Modules/<Area>/<Feature>/`:

```
app/Modules/
  Admin/        AuditLog, LoginLog, Settings, User
  Dashboard/
  Operations/   Attendance, Branch, ClassSchedule, ClassSession, ClassType,
                CoachProfile, Enrollment, LessonPlan, Media, Room,
                StudentProfile, Tuition
  Profile/
  Search/
```

**Actions hold the work.** A controller validates, authorises and delegates; an action such as
`CancelEnrollmentAction` or `ReviewLessonPlanAction` owns the transaction and the notification.

**Shared support** in `app/Support/`:

| Class | Purpose |
|---|---|
| `Menu/MenuRegistry` | The sidebar, registered by each module's provider through Eventy and filtered per viewer's permissions |
| `Settings` | Key-value settings with one cached read per request, forgotten on write |
| `Table/SortsQueries` | Server-side sorting from a **whitelist map**, so no request value ever reaches `orderBy` |
| `Ai/GeminiClient` | The one HTTP call, schema-constrained JSON, `null` on any failure |
| `LoginAttemptLogger` | Login log entries |

**Middleware** in `app/Http/Middleware/`: `SetCurrentBranch` (the branch switcher),
`SetLocale` (cookie, then the user's stored locale, then the centre default),
`HandleInertiaRequests` (shared props and ability flags), and `PreventPageCaching`, which marks
authenticated responses `no-store` so pressing Back after a logout cannot redisplay the previous
user's screen.

**Design system.** `resources/css/ui.css` holds the tokens, the single button system, cards, tables,
tags, the split layout and the empty states. Pages follow one of three patterns — index (head,
metric strip, filter band, table), form (cards by concern, actions in the foot), or detail (content
left, the page's actual job in a sticky right rail).

## Project structure

```
app/
  Console/Commands/     scheduled notification commands
  Http/Middleware/      branch, locale, Inertia props, cache headers
  Models/               20 Eloquent models
  Modules/              feature modules (see above)
  Notifications/        nine events on one abstract base
  Support/              menu, settings, sorting, AI client
database/
  migrations/           35 migrations
  seeders/              permissions, demo data
  factories/
resources/
  css/                  ui.css design system plus per-area stylesheets
  js/Pages/             50 Inertia pages
  js/Components/        form controls, UI primitives, charts
  js/Layouts/           the CMS shell
  views/                the Inertia root template and mail views
lang/en, lang/vi/       13 namespaces each
routes/web.php          141 routes
tests/Feature/          34 test files
```

## Internationalisation

English and Vietnamese, in 13 namespaces per locale. PHP translation files are compiled into the
bundle by `laravel-vue-i18n`, so `$t('operations.planTitle')` works identically in Blade, in Vue and
in a queued email.

The locale resolves as **valid cookie → the user's stored locale → the centre default setting →
English**, which means a coach whose account is set to Vietnamese gets a Vietnamese interface and a
Vietnamese email on any browser. Notifications render in the recipient's language, not the sender's.

## Project status

A graduation project on a twelve-week plan (`PLAN.md`), currently in **Week 11 of 12**. Weeks 1–10
are delivered: the technical debt clean-up, the schema, profiles, sessions and recurring schedules,
booking, attendance, lesson plans, tuition, the file library and a dashboard on real data.

Week 11 has shipped notifications, email delivery, real settings, global search and the AI assistant.
Week 12 is testing, optimisation, deployment and documentation. Continuous integration is not yet
set up and is part of that final week.

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/YourFeature`)
3. Run `vendor/bin/pint` and `php artisan test` before committing
4. Commit your changes (`git commit -am 'Add some feature'`)
5. Push to the branch (`git push origin feature/YourFeature`)
6. Open a pull request

For major changes, open an issue first to discuss what you would like to change. `AGENTS.md` records
the conventions this codebase is held to — in particular, that every access decision is a permission
check and never a role check.

## License

This project is open source and available under the [MIT License](LICENSE).
