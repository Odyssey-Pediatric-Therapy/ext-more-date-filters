# Filter guide

[Back to the README](README.md)

## 1. Reading the ranges

The extension adds calendar-aligned filters alongside Espo's existing options. Built-in filters
keep Espo's behavior: some include today or now, and current-month/quarter/year options already
use calendar periods. They are not all rolling windows. For example, built-in Last X Days
starts at midnight X days ago, rather than exactly X times 24 hours before now.

Added ranges use **an inclusive start and an exclusive end**: `start <= value < end`.
For example, `[2025-09-08 00:00:00, 2025-09-09 00:00:00)` means all of September 8,
including midnight at its start and excluding midnight at the start of September 9.
One-sided cutoff filters have only the bound stated in their description.

The extension’s “Last X” means the previous X **complete** minutes, hours, days, weeks, months, quarters,
or years; it excludes the current period. “Next X” begins at the next period boundary and
includes X complete periods. “Current … (Past)” ends just before now, and “Current … (Future)”
starts at now. These partial-period filters do not select a complete period. At the exact start
of a period its Past part is empty, which is expected.

“Anchored” describes alignment to a period boundary, not a fixed saved date. All relative
filters are recalculated when a search runs, including saved searches.

### Timezone and calendar rules

Extension datetime filters use the timezone supplied by the search request, falling back to the
application timezone and then UTC. Examples below are local times; stored datetime query
boundaries are converted to UTC. Date-only values keep their calendar date and are not shifted
to a different date by UTC conversion.

Weeks use the application's `weekStart` setting, with Sunday as the default. **All week
examples below assume Sunday**, so September 9, 2025 is in the week September 7–13. A Monday
setting instead puts it in September 8–14. All boundaries move together with this setting.

Months and years are calendar periods. Quarters are January–March, April–June, July–September,
and October–December. These extension options do not apply a fiscal-year offset.
Last X Months on March 31 with X=1 still selects February; Next Quarter on March 31 selects
April–June. February 29 is included normally in leap years.

Day/week/month/quarter/year boundaries follow local calendar dates across daylight-saving
changes, so a day can be shorter or longer than 24 hours. Minute/hour offsets use elapsed
seconds. The two occurrences of a repeated local time during a clock rollback are different
instants, even when the displayed clock time is identical.

### Date-only and all-day fields

Minute/hour options are for datetime fields. On optional datetime fields, such as Task and
Meeting dates, those sub-day filters select timed records and exclude all-day records.
Day-sized and larger filters can match both timed and all-day values.

Date-only fields have no “earlier today” or “later today.” Current Week/Month/Quarter/Year
(Past) therefore excludes today's date; the corresponding (Future) option includes today.
For example, on September 9, Current Month (Past) selects September 1–8, while Current Month
(Future) selects September 9–30. Today (Past/Future) is offered in datetime views, not plain
date views. The unqualified built-in Past/Future options retain Espo's own rules.

A date filter compares the **field you selected**. Filtering a Meeting's Date Start for today
does not find an event that started yesterday merely because it continues today. Date End
is a separate field, and the calendar's event-overlap rules are a separate operation.
Empty values do not match these date ranges; use Is Empty to find them.

### X and zero values

For extension filters, X must be a whole number from 1 to 10,000, except for the five cutoff
filters below which also allow 0. Negative numbers, decimals, and missing values are rejected.
The limit applies to extension filters; built-in Espo options retain their own validation.

For the following examples, now is **Tuesday, September 9, 2025 at 12:22:22**, and weeks
start Sunday. A date shown as a boundary means midnight at the start of that date.

| Filter | X=0 | X=1 | X=2 |
| --- | --- | --- | --- |
| Up to Next X Weeks | Before Sep 14 | Before Sep 21 | Before Sep 28 |
| Up to Next X Weeks (Future) | Now up to Sep 14 | Now up to Sep 21 | Now up to Sep 28 |
| After Next X Weeks | Sep 14 onward | Sep 21 onward | Sep 28 onward |
| Before X Weeks Ago | Before Sep 7 | Before Aug 31 | Before Aug 24 |
| After X Weeks Ago | Sep 14 onward | Sep 7 onward | Aug 31 onward |
| After X Weeks Ago (Past) | Not allowed | Sep 7 up to now | Aug 31 up to now |

“Before” and “up to” in this table exclude the cutoff. “Onward” includes it.
Up to Next X Weeks has **no lower bound**, so it includes older history. Its (Future) variant
starts at now. After X Weeks Ago has **no upper bound**, so it includes future values; its
(Past) variant stops just before now.

X=0 is rejected for Last/Next X Minutes/Hours/Days/Weeks/Months/Quarters/Years because that
would request zero complete periods. It is also rejected for After X Weeks Ago (Past):
the end of the current week is in the future, so there is no past interval after it.

## 2. Quick Reference Table

A categorized table of all of the filters that are available in the extension

| Filter Category | Built-in Filter(s) | Extension Filter(s) |
|-----------------|--------------------|---------------------|
| Minutes         |                    | [Current Minute](#current-minute)<br>[Current Minute (Past)](#current-minute-past)<br>[Current Minute (Future)](#current-minute-future)<br>[Last X Minutes](#last-x-minutes)<br>[Next X Minutes](#next-x-minutes) |
| Hours           | —                  | [Current Hour](#current-hour)<br>[Current Hour (Past)](#current-hour-past)<br>[Current Hour (Future)](#current-hour-future)<br>[Last X Hours](#last-x-hours)<br>[Next X Hours](#next-x-hours) |
| Days            | Today<br>Last Seven Days<br>Last X Days<br>Next X Days<br>Older Than X Days<br>After X Days | [Today (Past)](#today-past)<br>[Today (Future)](#today-future)<br>[Yesterday](#yesterday)<br>[Tomorrow](#tomorrow)<br>[Last 7 Days (excl. Today)](#last-seven-days)<br>[Last X Days (excl. Today)](#last-x-days)<br>[Next X Days (excl. Today)](#next-x-days) |
| Weeks           | —                  | [Current Week](#current-week)<br>[Current Week (Past)](#current-week-past)<br>[Current Week (Future)](#current-week-future)<br>[Last Week](#last-week)<br>[Last X Weeks](#last-x-weeks)<br>[Next Week](#next-week)<br>[Next X Weeks](#next-x-weeks)<br>[Up to Next X Weeks](#up-to-next-x-weeks)<br>[Up to Next X Weeks (Future)](#up-to-next-x-weeks-future)<br>[After Next X Weeks](#after-next-x-weeks)<br>[Before X Weeks Ago](#before-x-weeks-ago)<br>[After X Weeks Ago](#after-x-weeks-ago)<br>[After X Weeks Ago (Past)](#after-x-weeks-ago-past) |
| Months          | Current Month<br>Last Month<br>Next Month | [Current Month (Past)](#current-month-past)<br>[Current Month (Future)](#current-month-future)<br>[Last X Months](#last-x-months)<br>[Next X Months](#next-x-months) |
| Quarters        | Current Quarter<br>Last Quarter | [Current Quarter (Past)](#current-quarter-past)<br>[Current Quarter (Future)](#current-quarter-future)<br>[Last X Quarters](#last-x-quarters)<br>[Next Quarter](#next-quarter)<br>[Next X Quarters](#next-x-quarters) |
| Years           | Current Year<br>Last Year | [Current Year (Past)](#current-year-past)<br>[Current Year (Future)](#current-year-future)<br>[Last X Years](#last-x-years)<br>[Next Year](#next-year)<br>[Next X Years](#next-x-years) |
| Misc            | Ever<br>Past<br>Future<br>Is Empty<br>On<br>After<br>Before<br>Between | — |

## 3. Behavior Details per Filter

### Current Minute

Example, where now = "2025-09-09 12:00:22"

- Current Minute would get you any dates matching these conditions: `>= 2025-09-09 12:00:00 AND < 2025-09-09 12:01:00` (anchored full 1-minute block)

```sql
Current Minute:

|==================================|
^             ^                    ^
Start        NOW                  End
12:00:00   12:00:22             12:01:00
```

---

### Current Minute (Past)

Example, where now = "2025-09-09 12:00:22"

- Current Minute (Past) would get you any dates matching these conditions: `>= 2025-09-09 12:00:00 AND < 2025-09-09 12:00:22` (elapsed part of the current minute)

```sql
Current Minute (Past):

|==================================|
^             ^
Start      End (NOW)
12:00:00   12:00:22
```

---

### Current Minute (Future)

Example, where now = "2025-09-09 12:00:22"

- Current Minute (Future) would get you any dates matching these conditions: `>= 2025-09-09 12:00:22 AND < 2025-09-09 12:01:00` (remaining part of the current minute)

```sql
Current Minute (Future):

|==================================|
              ^                    ^
         Start (NOW)              End
          12:00:22              12:01:00
```

---

### Last X Minutes

Example, where now = "2025-09-09 12:00:22"

- Last X Minutes (X=1) would get you any dates matching these conditions: `>= 2025-09-09 11:59 AND < 2025-09-09 12:00` (anchored full 1-minute block ending before the current minute)
- Last X Minutes (X=8) would get you any dates matching these conditions: `>= 2025-09-09 11:52 AND < 2025-09-09 12:00` (anchored full 8-minute block ending before the current minute)

```sql
Last X Minutes X=8 (excludes current minute):

|=======|=======|=======|=======|=======|=======|=======|=======|=======|
^                                                               ^  ^
Start                                                         End  NOW
11:52:00                                                  12:00:00  12:00:22
```

---

### Next X Minutes

Example, where now = "2025-09-09 12:00:22"

- Next X Minutes (X=1) would get you any dates matching these conditions: `>= 2025-09-09 12:01 AND < 2025-09-09 12:02` (anchored full 1-minute block starting after the current minute)
- Next X Minutes (X=8) would get you any dates matching these conditions: `>= 2025-09-09 12:01 AND < 2025-09-09 12:09` (anchored full 8-minute block starting after the current minute)

```sql
Next X Minutes X=8 (excludes current minute):

|=======|=======|=======|=======|=======|=======|=======|=======|=======|
   ^    ^                                                               ^
NOW        Start                                                       End
12:00:22  12:01:00                                                  12:09:00
```

---

### Current Hour

Example, where now = "2025-09-09 12:22:22"

- Current Hour would get you any dates matching these conditions: `>= 2025-09-09 12:00:00 AND < 2025-09-09 13:00:00` (anchored full 1-hour block)

```sql
Current Hour:

|==================================|
^             ^                    ^
Start        NOW                  End
12:00:00   12:22:22             13:00:00
```

---

### Current Hour (Past)

Example, where now = "2025-09-09 12:22:22"

- Current Hour (Past) would get you any dates matching these conditions: `>= 2025-09-09 12:00:00 AND < 2025-09-09 12:22:22` (elapsed part of the current hour)

```sql
Current Hour (Past):

|==================================|
^             ^
Start      End (NOW)
12:00:00   12:22:22
```

---

### Current Hour (Future)

Example, where now = "2025-09-09 12:22:22"

- Current Hour (Future) would get you any dates matching these conditions: `>= 2025-09-09 12:22:22 AND < 2025-09-09 13:00:00` (remaining part of the current hour)

```sql
Current Hour (Future):

|==================================|
              ^                    ^
         Start (NOW)              End
           12:22:22            13:00:00
```

---

### Last X Hours

Example, where now = "2025-09-09 12:22:22"

- Last X Hours (X=1) would get you any dates matching these conditions: `>= 2025-09-09 11:00 AND < 2025-09-09 12:00`
- Last X Hours (X=3) would get you any dates matching these conditions: `>= 2025-09-09 09:00 AND < 2025-09-09 12:00`

```sql
Last X Hours X=3 (excludes current hour):

|=======|=======|=======|=======|
^                       ^   ^
Start                  End  NOW
09:00:00          12:00:00  12:22:22
```

---

### Next X Hours

Example, where now = "2025-09-09 12:22:22"

- Next X Hours (X=1) would get you any dates matching these conditions: `>= 2025-09-09 13:00 AND < 2025-09-09 14:00`
- Next X Hours (X=3) would get you any dates matching these conditions: `>= 2025-09-09 13:00 AND < 2025-09-09 16:00`

```sql
Next X Hours X=3 (excludes current hour):

|=======|=======|=======|=======|
   ^    ^                       ^
NOW       Start                End
12:22:22  13:00:00          16:00:00
```

---

### Today (Past)

Example, where now = "2025-09-09 12:22:22"

- Today (Past) would get you any dates matching these conditions: `2025-09-09 00:00 AND < 2025-09-09 12:22:22`

```sql
Today (Past):

|======================================================|
^                            ^
Start                     End (NOW)
2025-09-08 00:00:00  2025-09-09 12:22:22
```

---

### Today (Future)

Example, where now = "2025-09-09 12:22:22"

- Today (Future) would get you any dates matching these conditions: `2025-09-09 12:22:22 AND < 2025-09-10 00:00:00`

```sql
Today (Future):

|======================================================|
                            ^                          ^
                       Start (NOW)                    End
                   2025-09-09 12:22:22        2025-09-10 00:00:00
```

---

### Yesterday

You could search for yesterday by using lastXDays to specify 1 day, but the result is not necessarily "Yesterday"

Example, where today = "2025-09-09 12:22:22"

- Last X Days (X=1) would get you any dates matching these conditions: `>= 2025-09-08 AND <= 2025-09-09 12:22:22` (includes today)
- Yesterday would get you any dates matching these conditions: `>= 2025-09-08 AND < 2025-09-09` (excludes today)

```sql
Last X Days X=1 (includes today and NOW):

|============================|============================|
^                                             ^
Start                                     End (NOW)
2025-09-08 00:00:00                 2025-09-09 12:22:22



Yesterday (yesterday only):

|============================|
^                            ^
Start                       End
2025-09-08 00:00:00  2025-09-09 00:00:00

```

---

### Tomorrow

Example, where today = "2025-09-09 12:22:22"

- Next X Days (X=1) would get you any dates matching these conditions: `>= 2025-09-09 12:22:22 AND <= 2025-09-10 23:59:59` (includes today)
- Tomorrow would get you any dates matching these conditions: `>= 2025-09-10 AND < 2025-09-11` (excludes today)

```sql
Next X Days X=1 (includes today + tomorrow):

|============================|============================|
                ^                                         ^
            Start (NOW)                                  End
        2025-09-09 12:22:22                      2025-09-10 23:59:59




                              Tomorrow (tomorrow only):

                             |============================|
                             ^                            ^
                           Start                         End
                    2025-09-10 00:00:00           2025-09-11 00:00:00
```

---

### Last Seven Days

The menu distinguishes Espo's built-in Last Seven Days from the extension's
**Last 7 Days (excl. Today)**.

With now = **2025-09-09 12:22:22**:

| Field type | Built-in Last Seven Days | Last 7 Days (excl. Today) |
| --- | --- | --- |
| Datetime | Sep 2 at 00:00:00 through Sep 9 at 12:22:22, including now | Sep 2 at 00:00:00 up to Sep 9 at 00:00:00, excluding today |
| Date only | Sep 2 through Sep 9, both dates included (eight calendar dates) | Sep 2 through Sep 8 (seven complete dates) |

The built-in datetime range does not include later today after now. Neither option means
exactly the last 168 hours measured backward from the current time.

---

### Last X Days

Last X Days already exists as a filter offered by the base application.

Example, where today = "2025-09-09 12:22:22"

- Last X Days (Base) (X=3) would get you any dates matching these conditions: `>= 2025-09-06 00:00:00 AND <= 2025-09-09 12:22:22` (rolling day range, where the end date is inclusive of NOW)
- Last X Days (Extension) (X=3) would get you any dates matching these conditions: `>= 2025-09-06 00:00:00 AND < 2025-09-09 00:00:00` (full day, not including today or NOW)

```sql
Last X Days (Base) X=3 (includes today and NOW):

|==================|==================|==================|==================|
^                                                                  ^
Start                                                           End (NOW)
2025-09-06 00:00:00                                        2025-09-09 12:22:22




Last X Days (Extension) X=3 (excludes today and NOW):

|==================|==================|==================|
^                                                        ^
Start                                                   End
2025-09-06 00:00:00                              2025-09-09 00:00:00
```

---

### Next X Days

Next X Days already exists as a filter offered by the base application.

Example, where today = "2025-09-09 12:22:22"

- Next X Days (Base) (X=3) would get you any dates matching these conditions: >= 2025-09-09 12:22:22 AND <= 2025-09-12 23:59:59 (range including today, starting from NOW)
- Next X Days (Extension) (X=3) would get you any dates matching these conditions: >= 2025-09-10 00:00:00 AND < 2025-09-13 00:00:00 (anchored full-day blocks, excluding today)

```sql
Next X Days (Base) X=3 (includes today and NOW):

|==================|==================|==================|==================|
      ^                                                                     ^
  Start (NOW)                                                              End
   12:22:22                                                        2025-09-12 23:59:59


Next X Days (Extension) X=3 (excludes today and NOW):

                   |==================|==================|==================|
                   ^                                                        ^
                 Start                                                     End
          2025-09-10 00:00:00                                       2025-09-13 00:00:00
```

### Current Week

Example, where today = "2025-09-09 12:22:22"

- Current Week would get you any dates matching these conditions: `>= 2025-09-07 00:00:00 AND < 2025-09-14 00:00:00` (anchored full week)

```sql
Current Week (full week, anchored):

    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
^                   ^                                   ^
|           Today (2025-09-09)                          |
Start (Sunday)                                         End
2025-09-07 00:00:00                            2025-09-14 00:00:00
```

---

### Current Week (Past)

Example, where today = "2025-09-09 12:22:22"

- Current Week (Past) would get you any dates matching these conditions: `>= 2025-09-07 00:00:00 AND < 2025-09-09 12:22:22` (elapsed part of the current week)

```sql
Current Week (Past):

    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
^                   ^
|                  End (NOW) 2025-09-09 12:22:22
Start (Sunday)
2025-09-07 00:00:00
```

---

### Current Week (Future)

Example, where today = "2025-09-09 12:22:22"

- Current Week (Future) would get you any dates matching these conditions: `>= 2025-09-09 12:22:22 AND < 2025-09-14 00:00:00` (remaining part of the current week)

```sql
Current Week (Future):

    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                    ^                                   ^
    Start (NOW) 2025-09-09 12:22:22                     |
                                                       End
                                               2025-09-14 00:00:00
```

---

### Last Week

Example, where today = "2025-09-09"

- Last Week would get you any dates matching these conditions: `>= 2025-08-31 AND < 2025-09-07` (anchored full week)

```sql
Last Week (full week, anchored):

    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
^                                                       ^
Start (Sunday)                                         End
2025-08-31 00:00:00                            2025-09-07 00:00:00


    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                    ^
            Today (2025-09-09)
```

---

### Last X Weeks

Example, where today = "2025-09-09"

- Last X Weeks (X=1) would get you any dates matching these conditions: `>= 2025-08-31 AND < 2025-09-07` (anchored full week)
- Last X Weeks (X=2) would get you any dates matching these conditions: `>= 2025-08-24 AND < 2025-09-07` (anchored full 2-week period)

```sql
Last X Weeks X=2 (full 2-week period, anchored):

X=2 Weeks Ago:
    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
^
Start of X=2
2025-08-24 00:00:00


X=1 Weeks Ago:
    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
^                                                       ^
Start of X=1                                    End of X=1 and X=2
2025-08-31 00:00:00                             2025-09-07 00:00:00


Current Week:

    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                    ^
            Today (2025-09-09)
```

---

### Next Week

Example, where today = "2025-09-09"

- Next Week would get you any dates matching these conditions: `>= 2025-09-14 AND < 2025-09-21` (anchored full week)

```sql
Next Week (full week, anchored):

    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                    ^
            Today (2025-09-09)



    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
^                                                       ^
Start                                                  End
2025-09-14 00:00:00                            2025-09-21 00:00:00

```

---

### Next X Weeks

Example, where today = "2025-09-09"

- Next X Weeks (X=1) would get you any dates matching these conditions: `>= 2025-09-14 AND < 2025-09-21` (next full week)
- Next X Weeks (X=2) would get you any dates matching these conditions: `>= 2025-09-14 AND < 2025-09-28` (next full 2-week period)

```sql
Next X Weeks X=2 (full 2-week period, anchored):

Current Week:

    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                    ^
            Today (2025-09-09)



X=1 Weeks In the Future:
    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
^                                                       ^
Start of X=1 and X=2                                End of X=1
2025-09-14 00:00:00                            2025-09-21 00:00:00



X=2 Weeks In the Future:
    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                                                        ^
                                                    End of X=2
                                               2025-09-28 00:00:00
```

---

### Up To Next X Weeks

Example, where today = "2025-09-09"

- Up To Next X Weeks (X=1) would get you any dates matching this condition: `< 2025-09-21` (everything up until the end of the next full week)
- Up To Next X Weeks (X=2) would get you any dates matching this condition: `< 2025-09-28` (everything up until the end of the next 2 full weeks)

```sql
Up To Next X Weeks X=2 (full 2-week period, anchored):

Start of X=1 and X=2: -∞

Current Week:

    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                    ^
               (2025-09-09)



X=1 Weeks In the Future:
    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                                                        ^
                                                    End of X=1
                                                2025-09-21 00:00:00



X=2 Weeks In the Future:
    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                                                        ^
                                                    End of X=2
                                                2025-09-28 00:00:00
```

---

### Up To Next X Weeks (Future)

Example, where today = "2025-09-09"

- Up To Next X Weeks (Future) (X=1) would get you any dates matching these conditions: `>= 2025-09-09 AND < 2025-09-21` (everything from now up until the end of the next full week)
- Up To Next X Weeks (Future) (X=2) would get you any dates matching these condition: `>= 2025-09-09 AND < 2025-09-28` (everything from now up until the end of the next 2 full weeks)

```sql
Up To Next X Weeks (Future) X=2 (full 2-week period, anchored):

Current Week:

    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                    ^
            Start of X=1 and X=2
               (2025-09-09)



X=1 Weeks In the Future:
    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                                                        ^
                                                    End of X=1
                                                2025-09-21 00:00:00



X=2 Weeks In the Future:
    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                                                        ^
                                                    End of X=2
                                                2025-09-28 00:00:00
```

---

### After Next X Weeks

Example, where today = "2025-09-09"

- After Next X Weeks (X=1) would get you any dates matching this condition: `>= 2025-09-21` (everything after the end of the next full week)
- After Next X Weeks (X=2) would get you any dates matching this condition: `>= 2025-09-28` (everything after the end of the next 2 full weeks)

```sql
After Next X Weeks X=2 (full 2-week period, anchored):

Current Week:

    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                    ^
            Today (2025-09-09)



X=1 Weeks In the Future:
    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                                                        ^
                                                   Start of X=1
                                                2025-09-21 00:00:00



X=2 Weeks In the Future:
    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                                                        ^
                                                   Start of X=2
                                                2025-09-28 00:00:00


End of X=1 and X=2: +∞

```

---

### Before X Weeks Ago

Example, where today = "2025-09-09"

- Before X Weeks Ago (X=1) would get you any dates matching this condition: `< 2025-08-31` (everything up until the start of the last full week)
- Before X Weeks Ago (X=2) would get you any dates matching this condition: `< 2025-08-24` (everything up until the start of the last 2 full weeks)


```sql
Before X Weeks Ago X=2 (full 2-week period, anchored):

Start of X=1 and X=2: -∞


X=2 Weeks Ago:
    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
^
End of X=2
2025-08-24 00:00:00


X=1 Weeks Ago:
    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
^
End of X=1
2025-08-31 00:00:00


Current Week:

    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                    ^
            Today (2025-09-09)
```

---

### After X Weeks Ago

Example, where today = "2025-09-09"

- After X Weeks Ago (X=1) would get you any dates matching this condition: `>= 2025-09-07` (everything after the end of the last full week)
- After X Weeks Ago (X=2) would get you any dates matching this condition: `>= 2025-08-31` (everything after the end of the last 2 full weeks)

```sql
After X Weeks Ago X=2 (full 2-week period, anchored):

X=2 Weeks Ago:
    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                                                        ^
                                                   Start of X=2
                                                2025-08-31 00:00:00


X=1 Weeks Ago:
    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                                                        ^
                                                   Start of X=1
                                                2025-09-07 00:00:00


Current Week:

    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                    ^
            Today (2025-09-09)


End of X=1 and X=2: +∞

```

---

### After X Weeks Ago (Past)

Example, where today = "2025-09-09"

- After X Weeks Ago (X=1) would get you any dates matching these conditions: `>= 2025-09-07 AND < 2025-09-09` (everything after the end of the last full week up until now)
- After X Weeks Ago (X=2) would get you any dates matching this condition: `>= 2025-08-31 AND < 2025-09-09` (everything after the end of the last 2 full weeks up until now)

```sql
After X Weeks Ago (Past) X=2 (full 2-week period, anchored):

X=2 Weeks Ago:
    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                                                        ^
                                                   Start of X=2
                                                2025-08-31 00:00:00


X=1 Weeks Ago:
    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                                                        ^
                                                   Start of X=1
                                                2025-09-07 00:00:00


Current Week:

    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                    ^
            End of X=1 and X=2
               (2025-09-09)
```

---

### Current Month (Past)

Example, where today = "2025-09-09 12:22:22"

- Current Month (Past) would get you any dates matching these conditions: `>= 2025-09-01 00:00:00 AND < 2025-09-09 12:22:22`

```sql
Current Month (Past):

|========================================================|
^               ^
|      End (NOW) 2025-09-09 12:22:22
Start
2025-09-01 00:00:00
```

---

### Current Month (Future)

Example, where today = "2025-09-09 12:22:22"

- Current Month (Future) would get you any dates matching these conditions: `>= 2025-09-09 12:22:22 AND < 2025-10-01 00:00:00`

```sql
Current Month (Future):

|========================================================|
                ^                                        ^
       Start (NOW) 2025-09-09 12:22:22                   |
                                                        End
                                               2025-10-01 00:00:00
```

---

### Last X Months

Example, where today = "2025-09-09"

- Last X Months (X=1) would get you any dates matching these conditions: `>= 2025-08-01 AND < 2025-09-01` (anchored full month)
- Last X Months (X=2) would get you any dates matching these conditions: `>= 2025-07-01 AND < 2025-09-01` (anchored full 2-month period)

```sql
Last X Months X=2 (full month, anchored):


X=2 Months Ago:

|========================================================|
^
Start of X=2
2025-07-01 00:00:00



X=1 Months Ago:

|========================================================|
^                                                        ^
Start X=1                                        End of X=1 and X=2
2025-08-01 00:00:00                              2025-09-01 00:00:00


Current Month:

|========================================================|
                 ^
         Today (2025-09-09)
```

---

### Next X Months

Example, where today = "2025-09-09"

- Next X Months (X=1) would get you any dates matching these conditions: `>= 2025-10-01 AND < 2025-11-01` (anchored full month)
- Next X Months (X=2) would get you any dates matching these conditions: `>= 2025-10-01 AND < 2025-12-01` (anchored full 2-month period)

```sql
Next X Months X=2 (full month, anchored):

Current Month:

|========================================================|
                 ^
         Today (2025-09-09)



X=1 Months In the Future:

|========================================================|
^                                                        ^
Start of X=1 and X=2                                 End of X=1
2025-10-01 00:00:00                              2025-11-01 00:00:00



X=2 Months In the Future:

|========================================================|
                                                         ^
                                                     End of X=2
                                                 2025-12-01 00:00:00
```

---

### Current Quarter (Past)

Example, where today = "2025-09-09 12:22:22"

- Current Quarter (Past) would get you any dates matching these conditions: `>= 2025-07-01 00:00:00 AND < 2025-09-09 12:22:22`

```sql
Current Quarter (Past):

 Jul          Aug           Sep
|===========|===========|===========|
^                              ^
|                      End (NOW) 2025-09-09 12:22:22
Start
2025-07-01 00:00:00
```

---

### Current Quarter (Future)

Example, where today = "2025-09-09 12:22:22"

- Current Quarter (Future) would get you any dates matching these conditions: `>= 2025-09-09 12:22:22 AND < 2025-10-01 00:00:00`

```sql
Current Quarter (Future):

Jul          Aug           Sep
|===========|===========|===========|
                               ^    ^
   Start (NOW) 2025-09-09 12:22:22  |
                                   End
                            2025-10-01 00:00:00
```

---

### Last X Quarters

Example, where today = "2025-09-09"

- Last X Quarters (X=1) would get you any dates matching these conditions: `>= 2025-04-01 AND < 2025-07-01` (anchored full quarter)
- Last X Quarters (X=2) would get you any dates matching these conditions: `>= 2025-01-01 AND < 2025-07-01` (anchored full 2-quarter period)

```sql
Last X Quarters X=2 (anchored quarters, divided into months):

X=2 Quarters Ago:

 Jan          Feb           Mar
|===========|===========|===========|
^
Start of X=2
2025-01-01 00:00:00



X=1 Quarters Ago:

 Apr          May           Jun
|===========|===========|===========|
^                                   ^
Start of X=1                End of X=1 and X=2
2025-04-01 00:00:00         2025-07-01 00:00:00



Current Quarter:

 Jul          Aug           Sep
|===========|===========|===========|
                              ^
                      Today (2025-09-09)
```

---

### Next Quarter

Selects the entire next calendar quarter; it is equivalent to Next X Quarters with X=1.
With now = **2025-09-09 12:22:22**, the range is `>= 2025-10-01 00:00:00 AND < 2026-01-01 00:00:00`.
It excludes the remainder of the current quarter.

---

### Next X Quarters

Example, where today = "2025-09-09"

- Next X Quarters (X=1) would get you any dates matching these conditions: `>= 2025-10-01 AND < 2026-01-01` (anchored full quarter)
- Next X Quarters (X=2) would get you any dates matching these conditions: `>= 2025-10-01 AND < 2026-04-01` (anchored full 2-quarter period)

```sql
Next X Quarters X=2 (anchored quarters, divided into months):

Current Quarter:

 Jul          Aug           Sep
|===========|===========|===========|
                              ^
                      Today (2025-09-09)



X=1 Quarters In the Future:

 Oct          Nov           Dec
|===========|===========|===========|
^                                   ^
Start of X=1 and X=2            End of X=1
2025-10-01 00:00:00         2026-01-01 00:00:00



X=2 Quarters In the Future:

 Jan          Feb           March
|===========|===========|===========|
                                    ^
                                End of X=2
                            2026-04-01 00:00:00
```

---

### Current Year (Past)

Example, where today = "2025-09-09 12:22:22"

- Current Year (Past) would get you any dates matching these conditions: `>= 2025-01-01 00:00:00 AND < 2025-09-09 12:22:22`

```sql
Current Year (Past):

|========================================================|
^                                      ^
Start                              End (NOW)
2025-01-01 00:00:00            2025-09-09 12:22:22
```

---

### Current Year (Future)

Example, where today = "2025-09-09 12:22:22"

- Current Year (Future) would get you any dates matching these conditions: `>= 2025-09-09 12:22:22 AND < 2026-01-01 00:00:00`

```sql
Current Year (Past):

|========================================================|
                                       ^                 ^
                               Start (NOW)              End
                       2025-09-09 12:22:22      2026-01-01 00:00:00
```

---

### Last X Years

Example, where today = "2025-09-09"

- Last X Years (X=1) would get you any dates matching these conditions: `>= 2024-01-01 AND < 2025-01-01`
- Last X Years (X=2) would get you any dates matching these conditions: `>= 2023-01-01 AND < 2025-01-01`

```sql
Last X Years X=2 (full year, anchored):


X=2 Years Ago:

|========================================================|
^
Start of X=2
2023-01-01 00:00:00



X=1 Years Ago:

|========================================================|
^                                                        ^
Start of X=1                                    End of X=1 and X=2
2024-01-01 00:00:00                             2025-01-01 00:00:00


Current Year:

|========================================================|
                                      ^
                              Today (2025-09-09)
```

---

### Next Year

Selects the entire next calendar year; it is equivalent to Next X Years with X=1.
With now = **2025-09-09 12:22:22**, the range is `>= 2026-01-01 00:00:00 AND < 2027-01-01 00:00:00`.
It excludes the remainder of the current year.

---

### Next X Years

Example, where today = "2025-09-09"

- Next X Years (X=1) would get you any dates matching these conditions: `>= 2026-01-01 AND < 2027-01-01`
- Next X Years (X=2) would get you any dates matching these conditions: `>= 2026-01-01 AND < 2028-01-01`

```sql
Next X Years X=2 (full year, anchored):

Current Year:

|========================================================|
                                      ^
                              Today (2025-09-09)



X=1 Years In the Future:

|========================================================|
^                                                        ^
Start of X=1 and X=2                                 End of X=1
2026-01-0 00:00:00                               2027-01-01 00:00:00



X=2 Years In the Future:

|========================================================|
                                                         ^
                                                     End of X=2
                                                 2028-01-01 00:00:00
```
