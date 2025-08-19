define('more-date-filters:views/fields/datetime', ['views/fields/datetime'], Dep => {

  return class extends Dep {

    searchTypeList = [
      // Common
      'today',
      "yesterdayDatetime",
      "tomorrowDatetime",

      // Days
      'lastSevenDays',
      'lastSevenDaysExtDatetime',
      'lastXDays',
      // 'lastXDaysDatetime',
      'nextXDays',
      'olderThanXDays',
      'afterXDays',

      // Weeks
      'currentWeekDatetime',
      "lastWeekDatetime",
      // "lastXWeeksDatetime",
      "nextWeekDatetime",
      //"nextXWeeksDatetime",

      // Months
      'currentMonth',
      'lastMonth',
      //"lastXMonthsDatetime",
      'nextMonth',
      //"nextXMonthsDatetime",

      // Quarters
      'currentQuarter',
      'lastQuarter',
      //"lastXQuartersDatetime",
      // "nextQuarterDatetime",
      //"nextXQuartersDatetime",

      // Years
      'currentYear',
      'lastYear',
      //"lastXYearsDatetime",
      // "nextYearDatetime",
      //"nextXYearsDatetime",

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