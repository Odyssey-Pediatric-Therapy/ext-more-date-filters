define('more-date-filters:views/call/fields/date-start', ['crm:views/call/fields/date-start'], Dep => {
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
      'nextXDays',
      'olderThanXDays',
      'afterXDays',

      // Weeks
      'currentWeekExt',
      "lastWeekExt",
      //"lastXWeeksExt",
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