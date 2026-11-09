define('more-date-filters:helpers/filter-settings', [], () => {
    const fieldNames = [
        'moreDateFiltersCommon',
        'moreDateFiltersMinutes',
        'moreDateFiltersHours',
        'moreDateFiltersDays',
        'moreDateFiltersWeeks',
        'moreDateFiltersMonths',
        'moreDateFiltersQuarters',
        'moreDateFiltersYears',
        'moreDateFiltersOther',
    ];

    return {
        fieldNames,

        filterList(list, config, metadata, activeType) {
            const hidden = new Set();
            for (const name of fieldNames) {
                const enabled = config.get(name);
                const options = metadata.get(['entityDefs', 'Settings', 'fields', name, 'options']);
                // An unset setting preserves the original list; [] deliberately hides the group.
                if (!Array.isArray(enabled) || !Array.isArray(options)) continue;
                for (const type of options) {
                    if (!enabled.includes(type)) hidden.add(type);
                }
            }
            // Preserve the currently applied choice so reopening a saved filter never changes it.
            // Ever is the neutral choice when the administrator has unchecked everything.
            return list.filter(type => type === 'ever' || type === activeType || !hidden.has(type));
        },
    };
});
