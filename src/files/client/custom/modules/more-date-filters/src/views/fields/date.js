define('more-date-filters:views/fields/date', ['views/fields/date', 'more-date-filters:views/fields/search-mixin'], (Dep, searchMixin) => {
  return class extends searchMixin(Dep) {
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
      "upToNextXWeeksFutureExt",
      "afterNextXWeeksExt",
      "afterXWeeksAgoExt",
      "afterXWeeksAgoPastExt",
      "beforeXWeeksAgoExt",

      // Months
      'currentMonth',
      "currentMonthPastExt",
      "currentMonthFutureExt",
      'lastMonth',
      "lastXMonthsExt",
      'nextMonth',
      "nextXMonthsExt",

      // Quarters
      'currentQuarter',
      "currentQuarterPastExt",
      "currentQuarterFutureExt",
      'lastQuarter',
      "lastXQuartersExt",
      "nextQuarterExt",
      "nextXQuartersExt",

      // Years
      'currentYear',
      "currentYearPastExt",
      "currentYearFutureExt",
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
      "upToNextXWeeksFutureExt",
      "afterNextXWeeksExt",
      "afterXWeeksAgoExt",
      "afterXWeeksAgoPastExt",
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
