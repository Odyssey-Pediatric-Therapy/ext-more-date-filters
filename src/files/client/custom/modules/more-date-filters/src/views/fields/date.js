define('more-date-filters:views/fields/date', ['views/fields/date'], Dep => {
  return class extends Dep {
    searchTypeList = [
      // Common
      'ever',
      'past',
      'future',
      'today',
      "yesterdayExt",
      "tomorrowExt",

      // Days
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
      "currentWeekPastExt",
      "currentWeekFutureExt",
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
      'lastMonth',
      "lastXMonthsExt",
      'nextMonth',
      "nextXMonthsExt",

      // Quarters
      'currentQuarter',
      'lastQuarter',
      "lastXQuartersExt",
      "nextQuarterExt",
      "nextXQuartersExt",

      // Years
      'currentYear',
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