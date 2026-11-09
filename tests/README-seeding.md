# Manual date-filter fixtures

Run these commands from the repository root against a local **test/development Espo instance**.
The seeder and its helper live entirely under `tests`; they are not included in the extension package.

`npm run seed` and `npm run unit-tests` automatically install the locked test dependencies,
including Faker, into `tests/vendor` before running. Composer must be installed and available
on your PATH; no separate dependency-install command is needed.

Preview without booting Espo, connecting to a database, or creating records:

```sh
npm run seed -- --dry-run
```

Populate the database configured by this repository's `site` installation:

```sh
npm run seed
```

The seeder automatically assigns records to an active admin account (preferring the
`admin` login), or an active regular user if no admin exists. It reuses that account for
assigned/created/modified user fields, and explicitly adds that user as an accepted attendee
on Meetings and Calls so they appear in the calendar. No user ID or account creation is needed.

The default creates at least **2,000 of each**: Tasks, Meetings and Calls. Counts randomize independently for each entity and each run. The randomly distributed
records span execution time minus two calendar years through execution time plus two calendar
years. A compact set of extra records covers overlapping filter windows for X=1 and X=2.
There is no dense minute-by-minute cluster around today. Most dates have 1–3 records per
entity; a few additional records exercise short minute/hour windows and explicit calendar boundaries.
About **3%** of Tasks and Meetings are all-day by default; 20 Tasks have no start/end dates for `is empty`.
Faker generates readable event titles using people, companies and business activities.

Ordinary timed events start and finish between **08:00 and 18:00 in local time** by default.
Use `--start-time` and `--end-time` to change this daily window. Both accept `HHMM` or `HH:MM`
in 24-hour format, and the end must be later on the same day. Event durations are shortened
when needed so they finish by the closing time. Local hours follow the selected timezone,
including its seasonal DST offsets.

The small set of explicit filter-window, DST, leap-day/midnight, and four-year endpoint
fixtures remains exempt from the daily window: those exact times are required to exercise
the boundary cases. All-day records also retain their full local calendar day. A custom local
window that falls inside a nonexistent spring-forward time is skipped for ordinary records
on that date, rather than silently moving the events outside the requested hours.

Dedicated fixtures surround **every timezone offset transition** inside the four-year window:
one second before, exactly at, and one second after the change, plus nearby hour/minute values.
Both occurrences of repeated local times and events spanning the transition are represented.
Transitions come from the chosen timezone's rules, including half-hour changes; a timezone
without DST/offset changes gets no invented transitions.

For every **February 29** in the window, fixtures cover the start and end of the leap day
(one second before, at, and after each midnight) and noon. All-day Tasks/Meetings on DST and
leap-day dates are prioritized within the existing all-day quota; `--all-day-percent=0` still
disables them. Century rules are respected (2100 is not a leap year), and no records are
created outside the requested four-year span.
Consequently, the final count is greater than 2,000 per entity and varies with execution time.

Useful options:

```sh
npm run seed -- --start-time=0800 --end-time=1800
npm run seed -- --start-time=09:30 --end-time=16:30
npm run seed -- --count=3000 --timezone=America/Denver --week-start=1
npm run seed -- --run-id=manual-filter-check --dry-run
npm run seed -- --all-day-percent=1 --dry-run
npm run seed -- --help
```

`--count` controls the minimum spread records per entity (minimum 2,000). A random increment
is added per entity, along with the required boundary fixtures. `--all-day-percent` sets the
target all-day share for Tasks/Meetings from 0 to 10; use 0 for timed-only records.
Timezone and week start default to this repository's site configuration, then UTC
and Sunday. February 29 anniversaries clamp to February 28 in non-leap years.

All inserts occur in one transaction. Hooks and additional save processing are disabled so
reminders, notifications, stream entries and workflows are not triggered and synthetic
`createdAt`/`modifiedAt` timestamps are preserved. Future creation timestamps are intentional
fixtures for future-date filters. No schema or existing records are changed.

Each invocation appends a batch. Names contain only readable titles. The run identifier
is recorded in the description as `Run: <run-id>.`; the script prints this marker, final counts,
all-day percentages, calendar fixture counts, and daily distribution (days covered, today,
and peak per day).

The regression suite checks every registered extension range for X=1 and X=2 on start, end,
created and modified dates. A finite four-year dataset cannot match every arbitrary X or
absolute date. Some combinations are naturally empty, such as `is empty` on required event
dates or a past portion of a minute at its exact start.

Run the fixture regression tests with:

```sh
npm run unit-tests
```

## Empty the populated tables

Preview row counts, then empty the test instance's Task, Meeting and Call tables:

```sh
npm run empty -- --dry-run
npm run empty
```

Both commands always use this repository's `site` installation; alternate site targets are not supported.
The script permanently deletes **all rows** in these three tables, including non-seeded and
soft-deleted records. It also empties the MeetingUser and CallUser attendee tables so
calendar links do not become orphaned. User accounts are preserved. Deletes run in one transaction without hooks. It does not reset IDs
or drop the tables. The dry run connects to the database to count rows but performs no writes.
