define('more-date-filters:views/fields/datetime', ['views/fields/datetime'], Dep => {

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
      'nextXMinutesExt',

      //Hours
      'lastXHoursExt',
      'nextXHoursExt',

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
      "upToLastXWeeksExt",
      "afterLastXWeeksExt",

      // Quarters
      "lastXQuartersExt",
      "nextXQuartersExt",

      // Years
      "lastXYearsExt",
      "nextXYearsExt",
    ]
  }
})