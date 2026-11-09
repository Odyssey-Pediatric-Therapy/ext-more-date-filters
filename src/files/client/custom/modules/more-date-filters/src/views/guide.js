define('more-date-filters:views/guide', [
    'view',
    'more-date-filters:helpers/filter-settings',
], (View, filterSettings) => class extends View {
    template = 'more-date-filters:guide';

    data() {
        const metadata = this.getMetadata();
        const config = this.getConfig();
        const translate = (key, category) => this.translate(key, category, 'MoreDateFiltersGuide');
        // Escape prose and code alike; only the code wrapper adds HTML.
        const inlineCode = text => text.split(/(`[^`]+`)/g).map(part => {
            const isCode = part.startsWith('`') && part.endsWith('`');
            const escaped = this.getHelper().escapeString(isCode ? part.slice(1, -1) : part);
            return isCode ? `<code>${escaped}</code>` : escaped;
        }).join('');
        const groups = filterSettings.fieldNames.map(name => {
            const types = [...(metadata.get(['entityDefs', 'Settings', 'fields', name, 'options']) || [])];
            if (name === 'moreDateFiltersCommon') types.unshift('ever');
            const enabled = new Set(filterSettings.filterList(types, config, metadata));
            return {
                label: this.translate(name, 'fields', 'Settings'),
                rows: types.map(type => ({
                    name: this.getLanguage().translateOption(type, 'dateSearchRanges', 'Global'),
                    enabled: enabled.has(type),
                    status: translate(enabled.has(type) ? 'enabled' : 'disabled', 'labels'),
                    summary: translate(type, 'filterSummaries'),
                    summaryHtml: inlineCode(translate(type, 'filterSummaries')),
                })),
            };
        });
        const rows = groups.flatMap(group => group.rows);
        return {
            groups,
            enabledCount: rows.filter(row => row.enabled).length,
            totalCount: rows.length,
            labels: Object.fromEntries(['filter', 'status', 'summary', 'overview', 'rules', 'enabled', 'example']
                .map(key => [key, translate(key, 'labels')])),
            intro: translate('intro', 'messages'),
            availability: translate('availability', 'messages'),
            rules: ['periods', 'boundaries', 'count', 'calendar', 'dates', 'zone', 'field']
                .map(key => {
                    const section = translate(key, 'ruleSections');
                    return {
                        title: section.title,
                        items: section.items.map(inlineCode),
                        example: section.example ? inlineCode(section.example) : null,
                    };
                }),
        };
    }
});
