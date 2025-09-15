## 1. Introduction

Main difference between existing filters and the ones in this extension:

- The built-in filters use **rolling ranges** (relative to now), while the extension (`Ext`) filters use **anchored static ranges** (full days/weeks/months/etc)
- There are also a few that fill in gaps where built-in filters didn't exist (ex. lastXDays and nextXDays exist, but there wasn't a similar functionality for weeks, months, quarters, years, etc.)
- Most of the built-in filters are inclusive of the start and end dates, while the extension filters are inclusive of the start dates and **exclusive** of the end dates:
    - Built-in: `>= 2025-09-08 00:00 AND <= 2025-09-08 23:59:59`
    - Extension: `>= 2025-09-08 00:00 AND < 2025-09-09 00:00:00`
- Some of the built-in filters use NOW as the start/end date. The extension filters rely on anchors based on NOW to determine the start/end dates:
    - Built-in: `>= NOW (exact date and time) AND <= 2025-09-08 23:59:59`
    - Extension: `>= 2025-09-08 00:00 AND < 2025-09-09 00:00:00`

## 2. Quick Reference Table

A categorized table of all of the filters that are available in the extension (includes some, but not all, built-in filters)

| Filter Category | Built-in Filter(s) | Extension Filter(s) |
|-----------------|--------------------|---------------------|
| Minutes         |                    | [Last X Minutes](#last-x-minutes)<br>[Next X Minutes](#next-x-minutes) |
| Hours           | —                  | [Last X Hours](#last-x-hours)<br>[Next X Hours](#next-x-hours) |
| Days            | Today<br>Last Seven Days<br>Last X Days<br>Next X Days<br>Older Than X Days<br>After X Days | [Yesterday](#yesterday)<br>[Tomorrow](#tomorrow)<br>[Last Seven Days](#last-seven-days)<br>[Last X Days](#last-x-days)<br>[Next X Days](#next-x-days) |
| Weeks           | —                  | [Current Week](#current-week)<br>[Last Week](#last-week)<br>[Last X Weeks](#last-x-weeks)<br>[Next Week](#next-week)<br>[Next X Weeks](#next-x-weeks)<br>[Up to Next X Weeks](#up-to-next-x-weeks)<br>[After Next X Weeks](#after-next-x-weeks)<br>[Up To Last X Weeks](#up-to-last-x-weeks)<br>[After Last X Weeks](#after-last-x-weeks) |
| Months          | Current Month<br>Last Month<br>Next Month | [Last X Months](#last-x-months)<br>[Next X Months](#next-x-months) |
| Quarters        | Current Quarter<br>Last Quarter | [Last X Quarters](#last-x-quarters)<br>[Next Quarter](#next-quarter)<br>[Next X Quarters](#next-x-quarters) |
| Years           | CurrentYear<br>Last Year | [Last X Years](#last-x-years)<br>[Next Year](#next-year)<br>[Next X Years](#next-x-years) |
| Misc            | Ever<br>Past<br>Future<br>Is Empty<br>On<br>Not On<br>After<br>Before<br>Between | — |

## 3. Behavior Details per Filter

### Last X Minutes

Example, where now = "2025-09-09 12:00:22"

- lastXMinutesExt X=1 would get you any dates matching these conditions: `>= 2025-09-09 11:59 AND < 2025-09-09 12:00` (anchored full 1-minute block ending before the current minute)
- lastXMinutesExt X=8 would get you any dates matching these conditions: `>= 2025-09-09 11:52 AND < 2025-09-09 12:00` (anchored full 8-minute block ending before the current minute)

```yml
lastXMinutesExt X=8 (excludes current minute):

|=======|=======|=======|=======|=======|=======|=======|=======|=======|
^                                                               ^  ^
Start                                                         End  NOW
11:52:00                                                  2:00:00  12:00:22
```

---

### Next X Minutes

Example, where now = "2025-09-09 12:00:22"

- lastXMinutesExt X=1 would get you any dates matching these conditions: `>= 2025-09-09 12:01 AND < 2025-09-09 12:02` (anchored full 1-minute block starting after the current minute)
- lastXMinutesExt X=8 would get you any dates matching these conditions: `>= 2025-09-09 12:01 AND < 2025-09-09 12:09` (anchored full 8-minute block starting after the current minute)

```yml
nextXMinutes X=8 (excludes current minute):

|=======|=======|=======|=======|=======|=======|=======|=======|=======|
   ^    ^                                                               ^
NOW        Start                                                       End
(12:00:22) (12:01:00)                                               (12:09:00)
```

---

### Last X Hours

Example, where now = "2025-09-09 12:22:22"

- lastXHoursExt X=1 would get you any dates matching these conditions: `>= 2025-09-09 11:00 AND < 2025-09-09 12:00`
- lastXHoursExt X=3 would get you any dates matching these conditions: `>= 2025-09-09 09:00 AND < 2025-09-09 12:00`

```yml
lastXHoursExt X=3 (excludes current hour):

|=======|=======|=======|=======|
^                       ^   ^
Start                  End  NOW
(09:00:00)       (12:00:00) (12:22:22)
```

---

### Next X Hours

Example, where now = "2025-09-09 12:22:22"

- nextXHoursExt X=1 would get you any dates matching these conditions: `>= 2025-09-09 13:00 AND < 2025-09-09 14:00`
- nextXHoursExt X=3 would get you any dates matching these conditions: `>= 2025-09-09 13:00 AND < 2025-09-09 16:00`

```yml
nextXHoursExt X=3 (excludes current hour):

|=======|=======|=======|=======|
   ^    ^                       ^
NOW        Start               End
(12:22:22) (13:00:00)       (16:00:00)
```

### Yesterday

You could search for yesterday by using lastXDays to specify 1 day, but the result is not necessarily "Yesterday"

Example, where today = "2025-09-09 12:22:22"

- lastXDays with X=1 would get you any dates matching these conditions: `>= 2025-09-08 AND <= 2025-09-09 12:22:22` (includes today)
- yesterdayExt would get you any dates matching these conditions: `>= 2025-09-08 AND < 2025-09-09` (excludes today)

```yml
lastXDays X=1 (includes today and NOW):

|============================|=========|==================|
^                                          ^
Start                                     End (NOW)
(2025-09-08 00:00:00)              (2025-09-09 12:22:22)



yesterdayExt (yesterday only):

|============================|
^                            ^
Start                       End
(2025-09-08 00:00:00)      (2025-09-09 00:00:00)

```

---

### Tomorrow

Example, where today = "2025-09-09 12:22:22"

- nextXDays with X=1 would get you any dates matching these conditions: `>= 2025-09-09 12:22:22 AND <= 2025-09-10 23:59:59` (includes today)
- tomorrowExt would get you any dates matching these conditions: `>= 2025-09-10 AND < 2025-09-11` (excludes today)

```yml
nextXDays X=1 (includes today + tomorrow):

|=========|==================|============================|
          ^                                               ^
      Start (NOW)                                        End
(2025-09-09 12:22:22)                           (2025-09-10 23:59:59)




                              tomorrowExt (tomorrow only):

                             |============================|
                             ^                            ^
                           Start                         End
                     (2025-09-10 00:00)           (2025-09-11 00:00)
```

---

### Last Seven Days

Example, where today = "2025-09-09"

- lastSevenDays would get you any dates matching these conditions: `>= 2025-09-02 AND <= 2025-09-09` (includes today)
- lastSevenDaysExt would get you any dates matching these conditions: `>= 2025-09-02 AND < 2025-09-09` (excludes today)

```yml
lastSevenDays (includes today):

|=======|=======|=======|=======|=======|=======|=======|=======|
^                                                               ^
Start                                                          End
(2025-09-02 00:00)                                      (2025-09-09 23:59)




lastSevenDaysExt (excludes today):

|=======|=======|=======|=======|=======|=======|=======|
^                                                       ^
Start                                                  End
(2025-09-02 00:00)                              (2025-09-09 00:00)

```

---

### Last X Days

LastXDays already exists as a default filter.

Example, where today = "2025-09-09 12:22:22"

- lastXDays X=3 would get you any dates matching these conditions: `>= 2025-09-06 00:00:00 AND <= 2025-09-09 12:22:22` (rolling day range, where the end date is inclusive of NOW)
- lastXDaysExt X=3 would get you any dates matching these conditions: `>= 2025-09-06 00:00:00 AND < 2025-09-09 00:00:00` (full day, not including today or NOW)

```yml
lastXDays X=3 (includes today and NOW):

|==================|==================|==================|==================|
^                                                                  ^
Start                                                           End (NOW)
(2025-09-06 00:00:00)                                     (2025-09-09 12:22:22)




lastXDaysExt X=3 (excludes today and NOW):

|==================|==================|==================|==================|
^                                                        ^
Start                                                   End
(2025-09-06 00:00:00)                          (2025-09-09 00:00:00)
```

---

### Next X Days

NextXDays already exists as a default filter.

Example, where today = "2025-09-09 12:22:22"

- nextXDays X=3 would get you any dates matching these conditions: >= 2025-09-09 12:22:22 AND <= 2025-09-12 23:59:59 (range including today, starting from NOW)
- nextXDaysExt X=3 would get you any dates matching these conditions: >= 2025-09-10 00:00:00 AND < 2025-09-13 00:00:00 (anchored full-day blocks, excluding today)

```yml
nextXDays X=3 (includes today and NOW):

|==================|==================|==================|==================|
      ^                                                                     ^
  Start (NOW)                                                              End
  (12:22:22)                                                      (2025-09-12 23:59:59)


nextXDaysExt X=3 (excludes today and NOW):

|==================|==================|==================|==================|
                   ^                                                        ^
                 Start                                                     End
           (2025-09-10 00:00)                                    (2025-09-13 00:00:00)
```

### Current Week

Example, where today = "2025-09-09"

- currentWeekExt would get you any dates matching these conditions: `>= 2025-09-07 AND < 2025-09-14` (anchored full week)

```yml
currentWeekExt (full week, anchored):

    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
^                     ^                                 ^
|           Today (2025-09-09)                          |
Start (Sunday)                                         End
(2025-09-07 00:00)                              (2025-09-14 00:00)
```

---

### Last Week

Example, where today = "2025-09-09"

- lastWeekExt would get you any dates matching these conditions: `>= 2025-08-31 AND < 2025-09-07` (anchored full week)

```yml
lastWeekExt (full week, anchored):

    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
^                                                       ^
Start (Sunday)                                         End
(2025-08-31 00:00)                              (2025-09-07 00:00)


    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                    ^
            Today (2025-09-09)
```

---

### Last X Weeks

Example, where today = "2025-09-09"

- lastXWeeksExt X=1 would get you any dates matching these conditions: `>= 2025-08-31 AND < 2025-09-07` (anchored full week)
- lastXWeeksExt X=2 would get you any dates matching these conditions: `>= 2025-08-24 AND < 2025-09-07` (anchored full 2-week period)

```yml
lastXWeeksExt X=2 (full 2-week period, anchored):

X=2 Weeks Ago:
    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
^
Start of X=2
(2025-08-24 00:00)


X=1 Weeks Ago:
    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
^                                                       ^
Start of X=1                                    End of X=1 and X=2
(2025-08-31 00:00)                              (2025-09-07 00:00)


Current Week:

    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                    ^
            Today (2025-09-09)
```

---

### Next Week

Example, where today = "2025-09-09"

- nextWeekExt would get you any dates matching these conditions: `>= 2025-09-14 AND < 2025-09-21` (anchored full week)

```yml
nextWeekExt (full week, anchored):

    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                    ^
            Today (2025-09-09)



    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
^                                                       ^
Start                                                  End
2025-09-14 00:00)                               (2025-09-21 00:00)

```

---

### Next X Weeks

Example, where today = "2025-09-09"

- nextXWeeksExt X=1 would get you any dates matching these conditions: `>= 2025-09-14 AND < 2025-09-21` (next full week)
- nextXWeeksExt X=2 would get you any dates matching these conditions: `>= 2025-09-14 AND < 2025-09-28` (next full 2-week period)

```yml
nextXWeeksExt X=2 (full 2-week period, anchored):

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
(2025-09-14 00:00)                              (2025-09-21 00:00)



X=2 Weeks In the Future:
    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                                                        ^
                                                    End of X=2
                                                (2025-09-28 00:00)
```

---

### Up To Next X Weeks

Example, where today = "2025-09-09"

- upToNextXWeeksExt X=1 would get you any dates matching this condition: `< 2025-09-21` (everything up until the end of the next full week)
- upToNextXWeeksExt X=2 would get you any dates matching this condition: `< 2025-09-28` (everything up until the end of the next 2 full weeks)

```yml
upToNextXWeeksExt X=2 (full 2-week period, anchored):

Start: -∞


Current Week:

    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                    ^
            Today (2025-09-09)



X=1 Weeks In the Future:
    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                                                        ^
                                                    End of X=1
                                                (2025-09-21 00:00)



X=2 Weeks In the Future:
    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                                                        ^
                                                    End of X=2
                                                (2025-09-28 00:00)
```

---

### After Next X Weeks

Example, where today = "2025-09-09"

- afterNextXWeeksExt X=1 would get you any dates matching this condition: `>= 2025-09-21` (everything after the end of the next full week)
- afterNextXWeeksExt X=2 would get you any dates matching this condition: `>= 2025-09-28` (everything after the end of the next 2 full weeks)

```yml
afterNextXWeeksExt X=2 (full 2-week period, anchored):

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
                                                (2025-09-21 00:00)



X=2 Weeks In the Future:
    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                                                        ^
                                                   Start of X=2
                                                (2025-09-28 00:00)


End: +∞

```

---

### Up To Last X Weeks

Example, where today = "2025-09-09"

- upToLastXWeeksExt X=1 would get you any dates matching this condition: `< 2025-08-31` (everything up until the start of the last full week)
- upToLastXWeeksExt X=2 would get you any dates matching this condition: `< 2025-08-24` (everything up until the start of the last 2 full weeks)


```yml
upToLastXWeeksExt X=2 (full 2-week period, anchored):

Start: -∞


X=2 Weeks Ago:
    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
^
End of X=2
(2025-08-24 00:00)


X=1 Weeks Ago:
    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
^
End of X=1
(2025-08-31 00:00)


Current Week:

    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                    ^
            Today (2025-09-09)
```

---

### After Last X Weeks

Example, where today = "2025-09-09"

- afterLastXWeeksExt X=1 would get you any dates matching this condition: `>= 2025-09-07` (everything after the end of the last full week)
- afterLastXWeeksExt X=2 would get you any dates matching this condition: `>= 2025-08-31` (everything after the end of the last 2 full weeks)

```yml
afterLastXWeeksExt X=2 (full 2-week period, anchored):

X=2 Weeks Ago:
    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                                                        ^
                                                   Start of X=2
                                                (2025-08-31 00:00)


X=1 Weeks Ago:
    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                                                        ^
                                                   Start of X=1
                                                (2025-09-07 00:00)


Current Week:

    S       M       T       W      Th       F      Sa
|=======|=======|=======|=======|=======|=======|=======|
                    ^
            Today (2025-09-09)


End: +∞

```

---

### Last X Months

Example, where today = "2025-09-09"

- lastXMonthsExt X=1 would get you any dates matching these conditions: `>= 2025-08-01 AND < 2025-09-01` (anchored full month)
- lastXMonthsExt X=2 would get you any dates matching these conditions: `>= 2025-07-01 AND < 2025-09-01` (anchored full 2-month period)

```yml
lastXMonthsExt X=2 (full month, anchored):


X=2 Months Ago:

|========================================================|
^
Start of X=2
(2025-07-01 00:00)



X=1 Months Ago:

|========================================================|
^                                                        ^
Start X=1                                        End of X=1 and X=2
(2025-08-01 00:00)                               (2025-09-01 00:00)


Current Month:

|========================================================|
                 ^
         Today (2025-09-09)
```

---

### Next X Months

Example, where today = "2025-09-09"

- nextXMonthsExt X=1 would get you any dates matching these conditions: `>= 2025-10-01 AND < 2025-11-01` (anchored full month)
- nextXMonthsExt X=2 would get you any dates matching these conditions: `>= 2025-10-01 AND < 2025-12-01` (anchored full 2-month period)

```yml
nextXMonthsExt X=2 (full month, anchored):

Current Month:

|========================================================|
                 ^
         Today (2025-09-09)



X=1 Months In the Future:

|========================================================|
^                                                        ^
Start of X=1 and X=2                                 End of X=1
(2025-10-01 00:00)                               (2025-11-01 00:00)



X=2 Months In the Future:

|========================================================|
                                                         ^
                                                     End of X=2
                                                 (2025-12-01 00:00)
```

---

### Last X Quarters

Example, where today = "2025-09-09"

- lastXQuartersExt X=1 would get you any dates matching these conditions: `>= 2025-04-01 AND < 2025-07-01` (anchored full quarter)
- lastXQuartersExt X=2 would get you any dates matching these conditions: `>= 2025-01-01 AND < 2025-07-01` (anchored full 2-quarter period)

```yml
lastXQuartersExt X=2 (anchored quarters, divided into months):

X=2 Quarters Ago:

 Jan          Feb           Mar
|===========|===========|===========|
^
Start of X=2
(2025-01-01)



X=1 Quarters Ago:

 Apr          May           Jun
|===========|===========|===========|
^                                   ^
Start of X=1                End of X=1 and X=2
(2025-04-01)                   (2025-07-01)



Current Quarter:

 Jul          Aug           Sep
|===========|===========|===========|
^                             ^
Start of quarter      Today (2025-09-09)
(2025-07-01)
```

---

### Next X Quarters

Example, where today = "2025-09-09"

- nextXQuartersExt X=1 would get you any dates matching these conditions: `>= 2025-10-01 AND < 2026-01-01` (anchored full quarter)
- nextXQuartersExt X=2 would get you any dates matching these conditions: `>= 2025-10-01 AND < 2026-04-01` (anchored full 2-quarter period)

```yml
nextXQuartersExt X=2 (anchored quarters, divided into months):

Current Quarter:

 Jul          Aug           Sep
|===========|===========|===========|
^                             ^
Start of quarter      Today (2025-09-09)
(2025-07-01)



X=1 Quarters In the Future:

 Oct          Nov           Dec
|===========|===========|===========|
^                                   ^
Start of X=1 and X=2            End of X=1
 (2025-10-01)                  (2026-01-01)



X=2 Quarters In the Future:

 Jan          Feb           March
|===========|===========|===========|
                                    ^
                               End of X=2
                              (2026-04-01)
```

---

### Last X Years

Example, where today = "2025-09-09"

- lastXYearsExt X=1 would get you any dates matching these conditions: `>= 2024-01-01 AND < 2025-01-01`
- lastXYearsExt X=2 would get you any dates matching these conditions: `>= 2023-01-01 AND < 2025-01-01`

```yml
lastXYearsExt X=2 (full year, anchored):


X=2 Years Ago:

|========================================================|
^
Start of X=2
(2023-01-01 00:00)



X=1 Years Ago:

|========================================================|
^                                                        ^
Start of X=1                                    End of X=1 and X=2
(2024-01-01 00:00)                              (2025-01-01 00:00)


Current Year:

|========================================================|
                                      ^
                                    Today (2025-09-09)
```

---

### Next X Years

Example, where today = "2025-09-09"

- nextXYearsExt X=1 would get you any dates matching these conditions: `>= 2026-01-01 AND < 2027-01-01`
- nextXYearsExt X=2 would get you any dates matching these conditions: `>= 2026-01-01 AND < 2028-01-01`

```yml
nextXYearsExt X=2 (full year, anchored):

Current Year:

|========================================================|
                                      ^
                                    Today (2025-09-09)



X=1 Years In the Future:

|========================================================|
^                                                        ^
Start of X=1 and X=2                                 End of X=1
(2026-01-0 00:00)                                (2027-01-01 00:00)



X=2 Years In the Future:

|========================================================|
                                                         ^
                                                     End of X=2
                                                 (2028-01-01 00:00)
```
