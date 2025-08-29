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

    data() {
        const data = super.data();

        data.dateValue = this.getDateStringValue();

        data.isNone = data.dateValue === null;

        if (data.dateValue === -1) {
            data.dateValue = null;
            data.isLoading = true;
        }

        if (this.isSearchMode()) {
            const value = this.getSearchParamsData().value || this.searchParams.dateValue;
            const valueTo = this.getSearchParamsData().valueTo || this.searchParams.dateValueTo;

            data.dateValue = this.getDateTime().toDisplayDate(value);
            data.dateValueTo = this.getDateTime().toDisplayDate(valueTo);

            if (this.xTypeList.includes(this.getSearchType())) {
                data.number = this.searchParams.value;
            }
        }

        if (this.isListMode()) {
            data.titleDateValue = data.dateValue;
        }

        if (this.useNumericFormat) {
            data.useNumericFormat = true;
        }

        // noinspection JSValidateTypes
        return data;
    }

    handleSearchType(type) {
        this.$el.find('div.primary').addClass('hidden');
        this.$el.find('div.additional').addClass('hidden');
        this.$el.find('div.additional-number').addClass('hidden');

        if (['on', 'notOn', 'after', 'before'].includes(type)) {
            this.$el.find('div.primary').removeClass('hidden');
        }
        else if (this.xTypeList.includes(type)) {
            this.$el.find('div.additional-number').removeClass('hidden');
        }
        else if (type === 'between') {
            this.$el.find('div.primary').addClass('hidden');
            this.$el.find('div.additional').removeClass('hidden');
        }
    }

    fetchSearch() {
        const type = this.fetchSearchType();

        if (type === 'between') {
            const valueFrom = this.parseDate(this.$el.find('input.filter-from').val());
            const valueTo = this.parseDate(this.$el.find('input.filter-to').val());

            if (!valueFrom || !valueTo) {
                return null;
            }

            return {
                type: type,
                value: [valueFrom, valueTo],
                data: {
                    value: valueFrom,
                    valueTo: valueTo,
                },
            };
        }

        let data;
        const value = this.parseDate(this.$element.val());

        if (this.xTypeList.includes(type)) {
            const number = this.$el.find('input.number').val();

            data = {
                type: type,
                value: number,
                date: true,
            };
        }
        else if (['on', 'notOn', 'after', 'before'].includes(type)) {
            if (!value) {
                return null;
            }

            data = {
                type: type,
                value: value,
                data: {
                    value: value,
                },
            };
        }
        else if (type === 'isEmpty') {
            data = {
                type: 'isNull',
                data: {
                    type: type,
                },
            };
        }
        else {
            data = {
                type: type,
                date: true,
            };
        }

        return data;
    }
  }
})