define('more-date-filters:views/admin/settings', [
    'views/settings/record/edit',
    'more-date-filters:helpers/filter-settings',
], (SettingsEdit, filterSettings) => class extends SettingsEdit {
    layoutName = 'moreDateFiltersSettings';
    saveAndContinueEditingAction = false;

    setup() {
        for (const name of filterSettings.fieldNames) {
            // Keep an explicitly empty list. Only first-time, unset settings default to all.
            if (this.model.get(name) != null) continue;
            const options = this.getMetadata().get(['entityDefs', 'Settings', 'fields', name, 'options']) || [];
            this.model.set(name, [...options]);
        }
        super.setup();
    }
});
