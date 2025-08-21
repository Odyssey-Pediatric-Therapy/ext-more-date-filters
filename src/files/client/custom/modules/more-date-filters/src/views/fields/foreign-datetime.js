define('more-date-filters:views/fields/foreign-datetime', ['views/fields/foreign-datetime'], Dep => {

  return class extends Dep {

    searchTypeList = [
      // Common
      'today',
      "yesterdayExt",
      "tomorrowExt",

      // Days
      'lastSevenDays',
      'lastSevenDaysExt',
      'lastXDays',
      // 'lastXDaysExt',
      'nextXDays',
      // 'nextXDaysExt',
      'olderThanXDays',
      'afterXDays',

      // Weeks
      'currentWeekExt',
      "lastWeekExt",
      // "lastXWeeksExt",
      "nextWeekExt",
      //"nextXWeeksExt",

      // Months
      'currentMonth',
      'lastMonth',
      //"lastXMonthsExt",
      'nextMonth',
      //"nextXMonthsExt",

      // Quarters
      'currentQuarter',
      'lastQuarter',
      //"lastXQuartersExt",
      // "nextQuarterExt",
      //"nextXQuartersExt",

      // Years
      'currentYear',
      'lastYear',
      //"lastXYearsExt",
      // "nextYearExt",
      //"nextXYearsExt",

      // Misc
      'isEmpty',
      'ever',
      'past',
      'future',
      'on',
      'after',
      'before',
      'between',
    ]
  }
})