define('more-date-filters:views/fields/datetime-optional', ['views/fields/datetime-optional'], Dep => {

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
      "nextQuarterExt",
      //"nextXQuartersExt",

      // Years
      'currentYear',
      'lastYear',
      //"lastXYearsExt",
      "nextYearExt",
      //"nextXYearsExt",

      // Misc
      'isEmpty',
      'on',
      'after',
      'before',
      'between',
    ]
  }
})