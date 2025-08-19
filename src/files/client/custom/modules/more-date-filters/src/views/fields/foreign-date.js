define('more-date-filters:views/fields/foreign-date', ['views/fields/foreign-date'], Dep => {

  return class extends Dep {

    searchTypeList = [
      // Common
      'today',
      "yesterdayDate",
      "tomorrowDate",

      // Days
      'lastSevenDays',
      'lastSevenDaysExtDate',
      'lastXDays',
      // 'lastXDaysDate',
      'nextXDays',
      'olderThanXDays',
      'afterXDays',

      // Weeks
      'currentWeekDate',
      "lastWeekDate",
      // "lastXWeeksDate",
      "nextWeekDate",
      //"nextXWeeksDate",

      // Months
      'currentMonth',
      'lastMonth',
      //"lastXMonthsDate",
      'nextMonth',
      //"nextXMonthsDate",

      // Quarters
      'currentQuarter',
      'lastQuarter',
      //"lastXQuartersDate",
      // "nextQuarterDate",
      //"nextXQuartersDate",

      // Years
      'currentYear',
      'lastYear',
      //"lastXYearsDate",
      // "nextYearDate",
      //"nextXYearsDate",

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