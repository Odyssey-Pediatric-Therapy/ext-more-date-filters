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
|-----------------|------------------|-------------------|
| Minutes         |                  | Last X Minutes<br>Next X Minutes  |
| Hours           | — | [`lastXHoursExt`](#last-x-hours), [`nextXHoursExt`](#next-x-hours) |
| Days            | `today`, `lastSevenDays`, `lastXDays`, `nextXDays`, `olderThanXDays`, `afterXDays` | [`yesterdayExt`](#yesterday), [`tomorrowExt`](#tomorrow), [`lastSevenDaysExt`](#last-seven-days), [`lastXDaysExt`](#last-x-days), [`nextXDaysExt`](#next-x-days) |
| Weeks           | — | [`currentWeekExt`](#current-week), [`lastWeekExt`](#last-week), [`lastXWeeksExt`](#last-x-weeks), [`nextWeekExt`](#next-week), [`nextXWeeksExt`](#next-x-weeks), [`upToNextXWeeksExt`](#up-to-next-x-weeks), [`afterNextXWeeksExt`](#after-next-x-weeks), [`upToLastXWeeksExt`](#up-to-last-x-weeks), [`afterLastXWeeksExt`](#after-last-x-weeks) |
| Months          | `currentMonth`, `lastMonth`, `nextMonth` | [`lastXMonthsExt`](#last-x-months), [`nextXMonthsExt`](#next-x-months) |
| Quarters        | `currentQuarter`, `lastQuarter` | [`lastXQuartersExt`](#last-x-quarters), [`nextQuarterExt`](#next-quarter), [`nextXQuartersExt`](#next-x-quarters) |
| Years           | `currentYear`, `lastYear` | [`lastXYearsExt`](#last-x-years), [`nextYearExt`](#next-year), [`nextXYearsExt`](#next-x-years) |
| Misc            | `ever`, `past`, `future`, `isEmpty`, `on`, `notOn`, `after`, `before`, `between` | — |

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

[=======][=======][=======][=======][=======][=======][=======][=======][=======])
   ^     ^                                                                       ^
NOW        Start of next minute                                     Start of the 9th minute
(12:00:22)  (12:01:00)                                                      (12:09:00)
```

---

### Last X Hours

Example, where now = "2025-09-09 12:22:22"

- lastXHoursExt X=1 would get you any dates matching these conditions: `>= 2025-09-09 11:00 AND < 2025-09-09 12:00`
- lastXHoursExt X=3 would get you any dates matching these conditions: `>= 2025-09-09 09:00 AND < 2025-09-09 12:00`

```yml
lastXHoursExt X=3 (excludes current hour):

[=======][=======][=======])[=======]
^                          ^    ^
Start of           Start of      NOW
3 hours ago      current hour    (12:22:22)
(09:00:00)          (12:00:00)
```

---

### Next X Hours

Example, where now = "2025-09-09 12:22:22"

- nextXHoursExt X=1 would get you any dates matching these conditions: `>= 2025-09-09 13:00 AND < 2025-09-09 14:00`
- nextXHoursExt X=3 would get you any dates matching these conditions: `>= 2025-09-09 13:00 AND < 2025-09-09 16:00`

```yml
nextXHoursExt X=3 (excludes current hour):

[=======][=======][=======][=======])
   ^     ^                          ^
NOW     Start of next hour        Start of the 4th hour
(12:22:22)   (13:00:00)                   (16:00:00)
```

### Yesterday

You could search for yesterday by using lastXDays to specify 1 day, but the result is not necessarily "Yesterday"

Example, where today = "2025-09-09 12:22:22"

- lastXDays with X=1 would get you any dates matching these conditions: `>= 2025-09-08 AND <= 2025-09-09 12:22:22` (includes today)
- yesterdayExt would get you any dates matching these conditions: `>= 2025-09-08 AND < 2025-09-09` (excludes today)

```yml
lastXDays X=1 (includes today and NOW):

[============================][=========|==================]
^                             ^         ^
Start of day          Start of today   NOW
(2025-09-08)          (2025-09-09)    (2025-09-09)
(00:00:00)             (00:00:00)      (12:22:22)



yesterdayExt (yesterday only):

[============================])
^                             ^
Start of yesterday       Start of today
(2025-09-08 00:00)      (2025-09-09 00:00)

```

---

### Tomorrow

Example, where today = "2025-09-09 12:22:22"

- nextXDays with X=1 would get you any dates matching these conditions: `>= 2025-09-09 12:22:22 AND <= 2025-09-10 23:59:59` (includes today)
- tomorrowExt would get you any dates matching these conditions: `>= 2025-09-10 AND < 2025-09-11` (excludes today)

```yml
nextXDays X=1 (includes today + tomorrow):

[=========|==================][============================]
          ^                   ^                            ^
        NOW               Start of Tomorrow            End of Tomorrow
      (12:22:22)          (2025-09-10 00:00)       (2025-09-10 23:59:59)




                              tomorrowExt (tomorrow only):

                              [============================])
                              ^                             ^
                          Start of Tomorrow             Start of day after Tomorrow
                         (2025-09-10 00:00)               (2025-09-11 00:00)
```

---

### Last Seven Days

Example, where today = "2025-09-09"

- lastSevenDays would get you any dates matching these conditions: `>= 2025-09-02 AND <= 2025-09-09` (includes today)
- lastSevenDaysExt would get you any dates matching these conditions: `>= 2025-09-02 AND < 2025-09-09` (excludes today)

```yml
lastSevenDays (includes today):

[=======][=======][=======][=======][=======][=======][=======][=======]
^                                                                      ^
Start 7 days ago                                                    End of today
(2025-09-02 00:00)                                                  (2025-09-09 23:59)




lastSevenDaysExt (excludes today):

[=======][=======][=======][=======][=======][=======][=======])
^                                                              ^
Start 7 days ago                                               Start of today
(2025-09-02 00:00)                                             (2025-09-09 00:00)

```

---

### Last X Days

LastXDays already exists as a default filter.

Example, where today = "2025-09-09 12:22:22"

- lastXDays X=3 would get you any dates matching these conditions: `>= 2025-09-06 00:00:00 AND <= 2025-09-09 12:22:22` (rolling day range, where the end date is inclusive of NOW)
- lastXDaysExt X=3 would get you any dates matching these conditions: `>= 2025-09-06 00:00:00 AND < 2025-09-09 00:00:00` (full day, not including today or NOW)

```yml
lastXDays X=3 (includes today and NOW):

[==================][==================][==================][=========|=======]
^                                                           ^         ^
Start of day                                      Start of today      NOW
(2025-09-06 00:00:00)                         2025-09-09 00:00:00)    (2025-09-09 12:22:22)




lastXDaysExt X=3 (excludes today and NOW):

[==================][==================][==================])[=========|=======]
^                                                           ^          ^
Start of day                                      Start of today       NOW
(2025-09-06 00:00:00)                         2025-09-09 00:00:00)     (2025-09-09 12:22:22)
```

---

### Next X Days

NextXDays already exists as a default filter.

Example, where today = "2025-09-09 12:22:22"

- nextXDays X=3 would get you any dates matching these conditions: >= 2025-09-09 12:22:22 AND <= 2025-09-12 23:59:59 (range including today, starting from NOW)
- nextXDaysExt X=3 would get you any dates matching these conditions: >= 2025-09-10 00:00:00 AND < 2025-09-13 00:00:00 (anchored full-day blocks, excluding today)

```yml
nextXDays X=3 (includes today and NOW):

[=========|==================][==================][==================][==================]
          ^                   ^                                                          ^
        NOW               Start of Next Day                                    End of 3rd Day
      (12:22:22)          (2025-09-10 00:00)                             (2025-09-12 23:59:59)


nextXDaysExt X=3 (excludes today and NOW):

[=========|==================][==================][==================][==================])
          ^                   ^                                                           ^
        NOW               Start of Next Day                                    Start of 4th Day
      (12:22:22)          (2025-09-10 00:00)                             (2025-09-13 00:00:00)
```

### Current Week

Example, where today = "2025-09-09"

- currentWeekExt would get you any dates matching these conditions: `>= 2025-09-07 AND < 2025-09-14` (anchored full week)

```yml
currentWeekExt (full week, anchored):

   S        M        T        W        Th        F        Sa
[=======][=======][=======][=======][=======][=======][=======])
^                     ^                                        ^
|           Today (2025-09-09)                                 |
Start of week (Sunday)                            Start of next week (Sunday)
(2025-09-07 00:00)                                 (2025-09-14 00:00)
```

---

### Last Week

Example, where today = "2025-09-09"

- lastWeekExt would get you any dates matching these conditions: `>= 2025-08-31 AND < 2025-09-07` (anchored full week)

```yml
lastWeekExt (full week, anchored):

   S        M        T        W        Th       F        Sa
[=======][=======][=======][=======][=======][=======][=======])
^                                                              ^
Start of last week (Sunday)                                 Start of this week (Sunday)
(2025-08-31 00:00)                                         (2025-09-07 00:00)


   S        M        T
[=======][=======][=======]
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
   S        M        T        W        Th       F        Sa
[=======][=======][=======][=======][=======][=======][=======])
^                                                              ^
Start of 2-week period (Sunday)                              Start of last week (Sunday)
(2025-08-24 00:00)                                         (2025-08-31 00:00)


X=1 Weeks Ago:
   S        M        T        W        Th       F        Sa
[=======][=======][=======][=======][=======][=======][=======])
^                                                              ^
Start of 1-week period (Sunday)                                 Start of this week (Sunday)
(2025-08-31 00:00)                                         (2025-09-07 00:00)


Current Week:

   S        M        T
[=======][=======][=======]
                     ^
               Today (2025-09-09)
```

---

### Next Week

Example, where today = "2025-09-09"

- nextWeekExt would get you any dates matching these conditions: `>= 2025-09-14 AND < 2025-09-21` (anchored full week)

```yml
nextWeekExt (full week, anchored):

   S        M        T        W        Th       F        Sa
[=======][=======][=======][=======][=======][=======][=======]
                     ^
               Today (2025-09-09)



   S        M        T        W        Th       F        Sa
[=======][=======][=======][=======][=======][=======][=======])
^                                                              ^
Start of next week (Sunday)                                 Start of the week after next (Sunday)
2025-09-14 00:00)                                         (2025-09-21 00:00)

```

---

### Next X Weeks

Example, where today = "2025-09-09"

- nextXWeeksExt X=1 would get you any dates matching these conditions: `>= 2025-09-14 AND < 2025-09-21` (next full week)
- nextXWeeksExt X=2 would get you any dates matching these conditions: `>= 2025-09-14 AND < 2025-09-28` (next full 2-week period)

```yml
nextXWeeksExt X=2 (full 2-week period, anchored):

Current Week:

   S        M        T        W        Th       F        Sa
[=======][=======][=======][=======][=======][=======][=======]
                     ^
               Today (2025-09-09)



X=1 Weeks In the Future:
   S        M        T        W        Th       F        Sa
[=======][=======][=======][=======][=======][=======][=======])
^                                                              ^
Start of 1-week period (Sunday)                              Start of the week after next (Sunday)
(2025-09-14 00:00)                                         (2025-09-21 00:00)



X=2 Weeks In the Future:
   S        M        T        W        Th       F        Sa
[=======][=======][=======][=======][=======][=======][=======])
^                                                              ^
Start of 2-week period (Sunday)                              Start of the week after the week after next (Sunday)
(2025-09-21 00:00)                                      (2025-09-28 00:00)
```

---

### Up To Next X Weeks

Example, where today = "2025-09-09"

- upToNextXWeeksExt X=1 would get you any dates matching this condition: `< 2025-09-21` (everything up until the end of the next full week)
- upToNextXWeeksExt X=2 would get you any dates matching this condition: `< 2025-09-28` (everything up until the end of the next 2 full weeks)

```yml
upToNextXWeeksExt X=2 (full 2-week period, anchored):

Lower bound: -∞


Current Week:

   S        M        T        W        Th       F        Sa
[=======][=======][=======][=======][=======][=======][=======]
                     ^
               Today (2025-09-09)



X=1 Weeks In the Future:
   S        M        T        W        Th       F        Sa
[=======][=======][=======][=======][=======][=======][=======])
^                                                              ^
Start of 1-week period (Sunday)                              Start of the week after next (Sunday)
(2025-09-14 00:00)                                         (2025-09-21 00:00)



X=2 Weeks In the Future:
   S        M        T        W        Th       F        Sa
[=======][=======][=======][=======][=======][=======][=======])
^                                                              ^
Start of 2-week period (Sunday)                              Start of the week after the week after next (Sunday)
(2025-09-21 00:00)                                      (2025-09-28 00:00)
```

---

### After Next X Weeks

Example, where today = "2025-09-09"

- afterNextXWeeksExt X=1 would get you any dates matching this condition: `>= 2025-09-21` (everything after the end of the next full week)
- afterNextXWeeksExt X=2 would get you any dates matching this condition: `>= 2025-09-28` (everything after the end of the next 2 full weeks)

```yml
afterNextXWeeksExt X=2 (full 2-week period, anchored):

Current Week:

   S        M        T        W        Th       F        Sa
[=======][=======][=======][=======][=======][=======][=======]
                     ^
               Today (2025-09-09)



X=1 Weeks In the Future:
   S        M        T        W        Th       F        Sa
[=======][=======][=======][=======][=======][=======][=======])
^                                                              ^
Start of 1-week period (Sunday)                              Start of the week after next (Sunday)
(2025-09-14 00:00)                                         (2025-09-21 00:00)



X=2 Weeks In the Future:
   S        M        T        W        Th       F        Sa
[=======][=======][=======][=======][=======][=======][=======])
^                                                              ^
Start of 2-week period (Sunday)                              Start of the week after the week after next (Sunday)
(2025-09-21 00:00)                                      (2025-09-28 00:00)


Upper bound: +∞

```

---

### Up To Last X Weeks

Example, where today = "2025-09-09"

- upToLastXWeeksExt X=1 would get you any dates matching this condition: `< 2025-08-31` (everything up until the start of the last full week)
- upToLastXWeeksExt X=2 would get you any dates matching this condition: `< 2025-08-24` (everything up until the start of the last 2 full weeks)


```yml
upToLastXWeeksExt X=2 (full 2-week period, anchored):

Lower bound: -∞


X=2 Weeks Ago:
   S        M        T        W        Th       F        Sa
[=======][=======][=======][=======][=======][=======][=======])
^                                                              ^
Start of 2-week period (Sunday)                              Start of last week (Sunday)
(2025-08-24 00:00)                                         (2025-08-31 00:00)


X=1 Weeks Ago:
   S        M        T        W        Th       F        Sa
[=======][=======][=======][=======][=======][=======][=======])
^                                                              ^
Start of 1-week period (Sunday)                                 Start of this week (Sunday)
(2025-08-31 00:00)                                         (2025-09-07 00:00)


Current Week:

   S        M        T
[=======][=======][=======]
                     ^
               Today (2025-09-09)
```

---

### After Last X Weeks

Example, where today = "2025-09-09"

- afterLastXWeeksExt X=1 would get you any dates matching this condition: `>= 2025-08-31` (everything after the start of the last full week)
- afterLastXWeeksExt X=2 would get you any dates matching this condition: `>= 2025-08-24` (everything after the start of the last 2 full weeks)

```yml
afterLastXWeeksExt X=2 (full 2-week period, anchored):

X=2 Weeks Ago:
   S        M        T        W        Th       F        Sa
[=======][=======][=======][=======][=======][=======][=======])
^                                                              ^
Start of 2-week period (Sunday)                              Start of last week (Sunday)
(2025-08-24 00:00)                                         (2025-08-31 00:00)


X=1 Weeks Ago:
   S        M        T        W        Th       F        Sa
[=======][=======][=======][=======][=======][=======][=======])
^                                                              ^
Start of 1-week period (Sunday)                                 Start of this week (Sunday)
(2025-08-31 00:00)                                         (2025-09-07 00:00)


Current Week:

   S        M        T
[=======][=======][=======]
                     ^
               Today (2025-09-09)


Upper bound: +∞

```

---

### Last X Months

Example, where today = "2025-09-09"

- lastXMonthsExt X=1 would get you any dates matching these conditions: `>= 2025-08-01 AND < 2025-09-01` (anchored full month)
- lastXMonthsExt X=2 would get you any dates matching these conditions: `>= 2025-07-01 AND < 2025-09-01` (anchored full 2-month period)

```yml
lastXMonthsExt X=2 (full month, anchored):


X=2 Months Ago:

[========================================================])
^                                                         ^
Start of month (01)                                 Start of last month (01)
(2025-07-01 00:00)                                (2025-08-01 00:00)



X=1 Months Ago:

[========================================================])
^                                                         ^
Start of month (01)                                Start of this month (01)
(2025-08-01 00:00)                                (2025-09-01 00:00)


Current Month:

[========================================================]
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

[========================================================]
                        ^
                      Today (2025-09-09)



X=1 Months In the Future:

[========================================================])
^                                                         ^
Start of next month (01)                                 Start of the month after next (01)
(2025-10-01 00:00)                                (2025-11-01 00:00)



X=2 Months In the Future:

[========================================================])
^                                                         ^
Start of the month after next (01)                  Start of the month after the month after next  (01)
(2025-11-01 00:00)                                (2025-12-01 00:00)
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
[===========|===========|===========])
^                                    ^
Start of quarter            Start of last quarter
 (2025-01-01)                (2025-04-01)



X=1 Quarters Ago:

 Apr          May           Jun
[===========|===========|===========])
^                                    ^
Start of quarter            Start of this quarter
 (2025-04-01)                (2025-07-01)



Current Quarter:

 Jul          Aug           Sep
[===========|===========|===========])
^                             ^
Start of quarter       Today (2025-09-09)
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
[===========|===========|===========])
^                             ^
Start of quarter       Today (2025-09-09)
 (2025-07-01)



X=1 Quarters In the Future:

 Oct          Nov           Dec
[===========|===========|===========])
^                                    ^
Start next of quarter            Start of the quarter after next quarter
 (2025-10-01)                (2026-01-01)



X=2 Quarters In the Future:

 Jan          Feb           March
[===========|===========|===========])
^                                    ^
Start of the quarter     Start of the quarter
after next quarter        after the quarter after next
 (2026-01-01)                (2026-04-01)
```

---

### Last X Years

Example, where today = "2025-09-09"

- lastXYearsExt X=1 would get you any dates matching these conditions: `>= 2024-01-01 AND < 2025-01-01`
- lastXYearsExt X=2 would get you any dates matching these conditions: `>= 2023-01-01 AND < 2025-01-01`

```yml
lastXYearsExt X=2 (full year, anchored):


X=2 Years Ago:

[========================================================])
^                                                         ^
Start of year (01)                                 Start of last year (01)
(2023-01-01 00:00)                                (2024-01-01 00:00)



X=1 Years Ago:

[========================================================])
^                                                         ^
Start of year (01)                                Start of this year (01)
(2024-01-01 00:00)                                (2025-01-01 00:00)


Current Year:

[========================================================]
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

[========================================================]
                                      ^
                                    Today (2025-09-09)



X=1 Years In the Future:

[========================================================])
^                                                         ^
Start of next year (01)                                 Start of the year after next (01)
(2026-01-0 00:00)                                (2027-01-01 00:00)



X=2 Years In the Future:

[========================================================])
^                                                         ^
Start of the year after next (01)                  Start of the year after the year after next  (01)
(2027-01-01 00:00)                                (2028-01-01 00:00)
```
