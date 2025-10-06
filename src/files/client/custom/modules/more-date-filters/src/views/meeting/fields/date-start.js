define('more-date-filters:views/meeting/fields/date-start', ['crm:views/meeting/fields/date-start'], Dep => {
  return class extends Dep {
    searchTypeList = [
      // Common
      'ever',
      'past',
      'future',
      'today',
      "yesterdayExt",
      "tomorrowExt",

      //Minutes
      'lastXMinutesExt',
      'currentMinuteExt',
      'currentMinutePastExt',
      'currentMinuteFutureExt',
      'nextXMinutesExt',

      //Hours
      'lastXHoursExt',
      'currentHourExt',
      'currentHourPastExt',
      'currentHourFutureExt',
      'nextXHoursExt',

      // Days
      'todayPastExt',
      'todayFutureExt',
      'lastSevenDays',
      'lastSevenDaysExt',
      'lastXDays',
      'lastXDaysExt',
      'nextXDays',
      'nextXDaysExt',
      'olderThanXDays',
      'afterXDays',

      // Weeks
      'currentWeekExt',
      'currentWeekPastExt',
      'currentWeekFutureExt',
      "lastWeekExt",
      "lastXWeeksExt",
      "nextWeekExt",
      "nextXWeeksExt",
      "upToNextXWeeksExt",
      "afterNextXWeeksExt",
      "afterXWeeksAgoExt",
      "beforeXWeeksAgoExt",

      // Months
      'currentMonth',
      "currentMonthPastExt",
      "currentMonthFutureExt",
      'lastMonth',
      "lastXMonthsExt",
      'nextMonth',
      "nextXMonthsExt",

      // Quarters
      'currentQuarter',
      "currentQuarterPastExt",
      "currentQuarterFutureExt",
      'lastQuarter',
      "lastXQuartersExt",
      "nextQuarterExt",
      "nextXQuartersExt",

      // Years
      'currentYear',
      "currentYearPastExt",
      "currentYearFutureExt",
      'lastYear',
      "lastXYearsExt",
      "nextYearExt",
      "nextXYearsExt",

      // Misc
      'isEmpty',
      'on',
      'after',
      'before',
      'between',
    ]

    searchWithAdditionalNumberTypeList  = [
      //Minutes
      'lastXMinutesExt',
      'nextXMinutesExt',

      //Hours
      'lastXHoursExt',
      'nextXHoursExt',

      // Days
      'lastXDays',
      'lastXDaysExt',
      'nextXDays',
      'nextXDaysExt',
      'olderThanXDays',
      'afterXDays',

      // Weeks
      "lastXWeeksExt",
      "nextXWeeksExt",
      "lastXMonthsExt",
      "nextXMonthsExt",
      "upToNextXWeeksExt",
      "afterNextXWeeksExt",
      "afterXWeeksAgoExt",
      "beforeXWeeksAgoExt",

      // Quarters
      "lastXQuartersExt",
      "nextXQuartersExt",

      // Years
      "lastXYearsExt",
      "nextXYearsExt",
    ]
  }
})