define('more-date-filters:views/call/fields/date-start', ['crm:views/call/fields/date-start'], Dep => {
  return class extends Dep {

    searchTypeList = [
      // Common
      'today',
      "yesterdayDatetime",
      "tomorrowDatetime",

      // Days
      'lastSevenDays',
      'lastXDays',
      'nextXDays',
      'olderThanXDays',
      'afterXDays',

      // Weeks
      'currentWeekDatetime',
      "lastWeekDatetime",
      //"lastXWeeksDatetime",
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
      "nextQuarterDatetime",
      //"nextXQuartersDatetime",

      // Years
      'currentYear',
      'lastYear',
      //"lastXYearsDatetime",
      "nextYearDatetime",
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