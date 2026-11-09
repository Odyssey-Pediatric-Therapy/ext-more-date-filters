define('more-date-filters:views/fields/search-mixin', ['more-date-filters:helpers/filter-settings'], filterSettings => Dep => class extends Dep {
    getSearchTypeList() {
        const list = super.getSearchTypeList();
        return filterSettings.filterList(list, this.getConfig(), this.getMetadata(), this.getSearchType());
    }

    isExtendedNumberSearch(type) {
        return this.searchWithAdditionalNumberTypeList.includes(type) && type.endsWith('Ext');
    }

    additionalNumberMinimum(type) {
        return [
            'upToNextXWeeksExt',
            'upToNextXWeeksFutureExt',
            'afterNextXWeeksExt',
            'beforeXWeeksAgoExt',
            'afterXWeeksAgoExt',
        ].includes(type) ? 0 : 1;
    }

    handleSearchType(type) {
        super.handleSearchType(type);
        if (this.isExtendedNumberSearch(type)) {
            this.$el.find('div.additional-number').removeClass('hidden');
            this.$el.find('input.number').attr({min: this.additionalNumberMinimum(type), max: 10000, step: 1});
        } else {
            this.$el.find('input.number').removeAttr('min max step');
        }
    }

    data() {
        const data = super.data();
        if (this.isSearchMode() && this.isExtendedNumberSearch(this.getSearchType())) {
            data.number = this.searchParams.value;
        }
        return data;
    }

    fetchSearch() {
        const data = super.fetchSearch();
        if (data && this.isExtendedNumberSearch(this.fetchSearchType())) {
            data.value = this.$el.find('input.number').val();
        }
        return data;
    }
});
