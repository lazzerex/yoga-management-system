# Yoga Management System

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
<strong>A management system for a multi-branch yoga centre.</strong><br/>
It covers the full operation: branches and rooms, class schedules and sessions with week calendars,
member booking gated by paid tuition plans and backed by a waitlist, teacher and student attendance, lesson plans with an approval workflow, tuition invoicing
and payments, a central file library, PDF documents, notifications by email and in-app, and an AI
assistant that drafts asana sequences.
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

**Centre setup.** Branches, rooms and class types. A branch switcher in the top bar scopes every list
a staff member sees.

**Scheduling.** Recurring class schedules generate concrete class sessions. Each session carries a
coach, a room, a capacity and a status (scheduled, done or cancelled), plus a short code such as
`CS-000123` that staff can read out and search by. Members, coaches and admins each get an interactive
week calendar; the admin version covers the whole branch and opens a session detail with the roster,
fill and actions to edit or cancel the session.

**Booking.** Members book and cancel their own places, and only on a date that a paid (or waived)
tuition plan covers. Each booking spends one session of that plan, preferring an unlimited pass and
otherwise the plan that expires soonest; cancelling returns it. A full session waitlists, and a
cancellation promotes the first person waiting and notifies them. When the centre cancels a whole
session, every member's plan session comes back and the booking shows as cancelled. Each booking has
a code such as `BK-000123`. The cancellation cutoff is a setting rather than a constant.

**Attendance.** Coaches check in and out of their own sessions and mark the student roster. Admins see
every branch; a coach sees only their own sessions. Monthly reports summarise both.

**Lesson plans.** A coach drafts a plan (objective, asana sequence, level, duration, up to five
attachments) and submits it; an admin approves or rejects it with a comment. Every decision is kept as
review history.

**Tuition.** Tuition plans, invoices with line items, partial payments, payment proofs, voiding rather
than deleting, and a CSV export. A paid invoice line for a plan becomes the member's entitlement:
its validity window and, for packs, a session count that bookings draw down. Plans carry an English
and an optional Vietnamese name.

**Files.** One library over every uploaded file. A file is authorised by the record it hangs off
rather than by a flat permission, so a coach cannot read a payment proof by knowing its id.

**Documents.** Invoices, monthly attendance reports and lesson plans export to PDF. The templates are
Blade views rendered by dompdf and pin a font that carries Vietnamese diacritics.

**Notifications.** Nine events across in-app and email, each with per-user channel preferences. Two
scheduled commands send tuition reminders and class reminders.

**Admin.** User management, role assignment, login logs, an audit trail of sensitive actions
(including every view of a student's medical notes), and a settings page that persists to the
database.

**Search.** One throttled, permission-gated search box across students, coaches, invoices, lesson
plans and users. Results are row-scoped, so a coach searching a name finds only students booked into
their own sessions.

**Dashboard.** Live figures rather than mock data: revenue by month, sessions taught, attendance rate,
new members, plans awaiting review and class fill rate.

## Roles and authorization

Three roles, and each user holds exactly one: `admin`, `coach`, `member`.

Every access decision reads a permission, never the role column. There are 35 permissions (admin holds
30, coach 10, member 4), seeded by `database/seeders/PermissionSeeder.php` and enforced in three
places:

- Routes: `Route::middleware('permission:operations.plans.review')`
- Menu items: `AppMenuItem::make(...)->permissions('operations.plans.view')`
- Vue: named ability flags shared from `HandleInertiaRequests`, never the full permission list

Two roles can share some permissions and differ in others, so a permission check survives a change of
role where a role check does not. `users.role` is used only to assign permissions, to render a label,
and in audit metadata.

Permissions also come in `.any` variants, such as `operations.plans.view` against
`operations.plans.view.any`. That distinction is what narrows a coach to their own records while an
admin sees the whole centre.

## Tech stack

| Layer | Choice |
|---|---|
| Backend | PHP 8.3, Laravel 13 |
| Auth | Laravel Fortify |
| Permissions | spatie/laravel-permission 8 |
| Files | spatie/laravel-medialibrary 11 |
| PDF | barryvdh/laravel-dompdf 3 |
| Hooks | tormjens/eventy (menu registry) |
| Routes in JS | Ziggy |
| Frontend | Inertia.js 3 and Vue 3 (Blade renders the shell, the landing page, the mail templates and the PDF documents) |
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

For a production-style build, use `pnpm build` instead of `pnpm dev`.

Sign in at `http://127.0.0.1:8000/cms/login`. The whole app lives under `/cms`.

> **After pulling changes that add a permission**, re-seed and clear the permission cache, or the new
> grant will not exist in your database:
>
> ```sh
> php artisan db:seed --class=PermissionSeeder
> php artisan permission:cache-reset
> ```

> **Do not leave a cached config in place while running the test suite.** A cached config overrides
> every `<env>` pin in `phpunit.xml`, which points the suite at your development database. Run
> `php artisan config:clear` first. See `AGENTS.md` section 6.

## Demo accounts

`php artisan migrate --seed` creates the accounts below along with branches, rooms, class types,
coaches, students, twenty weeks of sessions, enrolments, attendance, lesson plans, invoices and a
current paid plan for every active member. `coach2` to `coach6` and `member2` to `member43` exist
too, with the same passwords as their role.

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

Both are idempotent for the day, so running twice sends once. To exercise them end to end:

```sh
php artisan queue:work      # terminal 1
php artisan schedule:work   # terminal 2
```

`MAIL_ALWAYS_TO` in `.env` redirects every outgoing message to a single inbox, which allows the mail
path to be rehearsed without writing to real members.

## AI lesson-plan assistant

Two entry points. Both are advisory, and neither writes to a lesson plan:

- **Coach.** "Suggest a sequence" on the plan form returns a structured asana sequence that the coach
  edits and saves themselves.
- **Admin.** "Check this plan" on a pending plan returns a critique under four headings, each rated
  and paired with a concrete fix, plus an advisory score. It is not wired to approve or reject; a
  human always decides.

Both replies come back in the viewer's language, English or Vietnamese.

The prompt for the coach's request is built from exactly four whitelisted fields: class type, level,
duration and objective. No student record, name, medical note or attendance figure is sent.

An admin may optionally attach one image from the plan under review, behind a checkbox that starts
unticked on every visit. PDFs cannot be sent, the image must belong to that plan, and each send writes
an `ai_attachment_sent` audit row naming who sent what. Prompts and responses are stored in
`ai_suggestions` as evidence.

Configuration is four keys in `.env`:

```
AI_DRIVER=fake                    # the shipped default
GEMINI_API_KEY=
GEMINI_MODEL=gemini-3.5-flash
GEMINI_TIMEOUT=30
```

`AI_DRIVER=fake` returns a canned sequence and critique, so a fresh clone has a working demo with no
key and no network. Set `AI_DRIVER=gemini` with a free key from
[Google AI Studio](https://aistudio.google.com/apikey), run `php artisan config:clear`, and the same
buttons call the real API. With `gemini` set and no key present, the buttons are not rendered and the
endpoints return 403, so there is no partially enabled state.

## Testing

```sh
php artisan test        # 375 tests, 1228 assertions
vendor/bin/pint         # code style
```

Tests run against sqlite in memory and never open a socket: mail uses the array transport, the AI
driver is pinned to `fake`, HTTP calls are faked, and Inertia SSR is disabled for the suite. Feature
tests that create users need `$seed = true` with `$seeder = PermissionSeeder::class`.

Two manual scripts sit alongside the suite: `tasks/smoke-test-core-flow.md` is a twenty-step happy
path for use before a demo, and `tasks/e2e-test-flow.md` walks all three roles with the negative
checks inline.

## Architecture

Features are organised as modules rather than a flat `app/Http/Controllers`. Each feature owns its
controllers, actions and form requests under `app/Modules/<Area>/<Feature>/`:

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
`CancelEnrollmentAction` or `ReviewLessonPlanAction` owns the transaction and the notification that
follows it.

**Shared support** lives in `app/Support/`:

| Class | Purpose |
|---|---|
| `Menu/MenuRegistry` | The sidebar, registered by each module's provider through Eventy and filtered against the viewer's permissions |
| `Settings` | Key-value settings with one cached read per request, forgotten on write |
| `Week` | Resolves a `?week=` query to its Monday for the week calendars |
| `Table/SortsQueries` | Server-side sorting from a whitelist map, so no request value reaches `orderBy` |
| `Ai/GeminiClient` | The single HTTP call, schema-constrained JSON, `null` on any failure |
| `Pdf/DocumentPdf` | One helper behind the three PDF endpoints |
| `LoginAttemptLogger` | Login log entries |

**Middleware** lives in `app/Http/Middleware/`: `SetCurrentBranch` for the branch switcher, `SetLocale`
(cookie, then the user's stored locale, then the centre default), `HandleInertiaRequests` for shared
props and ability flags, and `PreventPageCaching`, which marks authenticated responses `no-store` so
that pressing Back after a logout cannot redisplay the previous user's screen.

**Derived, never stored.** A plan's remaining sessions are counted from the bookings that point at it,
so a cancellation returns the session with no refund code. Booking and session codes are formatted
from the primary key by the `HasReference` model trait, and searching matches any part of the code.

**Design system.** `resources/css/ui.css` holds the tokens, the button system, cards, tables, tags,
the split layout and the empty states. Pages follow one of three patterns: index (head, metric strip,
filter band, table), form (cards grouped by concern, actions in the foot), or detail (content on the
left, the page's primary action in a sticky right rail).

## Project structure

```
app/
  Console/Commands/     scheduled notification commands
  Http/Middleware/      branch, locale, Inertia props, cache headers
  Models/               20 Eloquent models
  Modules/              feature modules (see above)
  Notifications/        nine events on one abstract base
  Support/              menu, settings, sorting, AI client, PDF
database/
  migrations/           37 migrations
  seeders/              permissions, demo data
  factories/
resources/
  css/                  ui.css design system plus per-area stylesheets
  js/Pages/             50 Inertia pages
  js/Components/        form controls, UI primitives, charts
  js/Layouts/           the CMS shell
  views/                the Inertia root template, mail views and PDF templates
lang/en, lang/vi/       14 namespaces each
routes/web.php          146 routes
tests/Feature/          36 test files
```

## Internationalisation

English and Vietnamese, in 14 namespaces per locale. PHP translation files are compiled into the
bundle by `laravel-vue-i18n`, so `$t('operations.planTitle')` resolves identically in Blade, in Vue
and in a queued email.

The locale resolves in this order: a valid cookie, then the user's stored locale, then the centre
default setting, then English. A coach whose account is set to Vietnamese therefore gets a Vietnamese
interface and a Vietnamese email on any browser. Notifications render in the recipient's language
rather than the sender's, and a stored in-app notification keeps a message key rather than words
translated at send time, so it reads correctly in whichever language the recipient uses later.
Laravel's own mail strings (greeting, sign-off, footer) are translated in `lang/vi.json`.

## Project status

A graduation project built to a twelve-week plan (`PLAN.md`). Weeks 1 to 11 are delivered: the
technical debt clean-up, the schema, coach and student profiles, sessions and recurring schedules,
booking and the waitlist, both kinds of attendance, lesson plans with review, tuition, the file
library, a dashboard on real data, notifications with email delivery, persisted settings, global
search and the AI assistant. PDF export was added on top of that set. Week 11 connected tuition to
booking, so a member books only on a paid plan, and closed with three rounds of manual acceptance
testing whose fixes added the week calendars, booking and session codes and bilingual plan names.

Week 12 is the remaining work: query and index optimisation, a security pass, deployment with a
queue worker, cron and database backups, and the written report. Continuous integration is not yet
configured. Two-factor sign-in is enabled in Fortify's config but has no setup screen yet, so no
account can turn it on and sign-in never asks for a code.

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/YourFeature`)
3. Run `vendor/bin/pint` and `php artisan test` before committing
4. Commit your changes (`git commit -am 'Add some feature'`)
5. Push to the branch (`git push origin feature/YourFeature`)
6. Open a pull request

For a substantial change, open an issue first to discuss it. `AGENTS.md` records the conventions this
codebase is held to, in particular that every access decision is a permission check and never a role
check.

## License

This project is open source and available under the [MIT License](LICENSE).
