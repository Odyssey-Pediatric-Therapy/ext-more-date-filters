define('more-date-filters:views/fields/datetime-optional', ['views/fields/datetime-optional'], Dep => {

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
      // 'nextXDaysDatetime',
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