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
      'nextXDays',
      'olderThanXDays',
      'afterXDays',

      // Weeks
      'currentWeekExt',
      "lastWeekExt",
      "lastXWeeksExt",
      "nextWeekExt",
      "nextXWeeksExt",
      "upToNextXWeeksExt",
      "afterNextXWeeksExt",
      "upToLastXWeeksExt",
      "afterLastXWeeksExt",

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

    xTypeList = [
      'lastXDays',
      'nextXDays',
      'olderThanXDays',
      'afterXDays',
      "lastXWeeksExt",
      "nextXWeeksExt",
      "lastXMonthsExt",
      "nextXMonthsExt",
      "lastXQuartersExt",
      "nextXQuartersExt",
      "lastXYearsExt",
      "nextXYearsExt",
      "upToNextXWeeksExt",
      "afterNextXWeeksExt",
      "upToLastXWeeksExt",
      "afterLastXWeeksExt",
    ]
  }
})