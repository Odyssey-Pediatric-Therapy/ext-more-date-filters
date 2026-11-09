import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
import test from 'node:test';

const root = 'src/files/custom/Espo/Modules/MoreDateFilters/Resources/';
const source = 'src/files/client/custom/modules/more-date-filters/src/';
const read = file => JSON.parse(fs.readFileSync(root + file, 'utf8'));
const fields = read('metadata/entityDefs/Settings.json').fields;
const metadata = {get: path => path.reduce((value, key) => value?.[key], {entityDefs: {Settings: {fields}}})};
const modules = {};
class SettingsEdit {setup() {this.parentSetupCalled = true;}}
const context = vm.createContext({define: (name, deps, factory) => {
    modules[name] = factory(...deps.map(name => name === 'views/settings/record/edit' ? SettingsEdit : modules[name]));
}});
vm.runInContext(fs.readFileSync(source + 'helpers/filter-settings.js', 'utf8'), context);
vm.runInContext(fs.readFileSync(source + 'views/admin/settings.js', 'utf8'), context);
const settings = modules['more-date-filters:helpers/filter-settings'];
const filterList = (list, values, active = null) => Array.from(settings.filterList(list, {get: name => values[name]}, metadata, active));

test('all available filter types are covered exactly once by the settings checklists', () => {
    const selected = Object.values(fields).flatMap(field => field.options);
    assert.equal(new Set(selected).size, selected.length);
    const registered = Object.keys(read('metadata/app/select.json').whereItemConverterClassNameMap);
    for (const type of registered) assert(selected.includes(type), type);
    for (const field of Object.values(fields)) {
        assert.equal(field.type, 'checklist');
        assert.deepEqual(field.default, field.options);
        assert.equal(field.translation, 'Global.options.dateSearchRanges');
        assert.notEqual(field.onlyAdmin, true, 'Ordinary users must be able to read their dropdown choices.');
    }
    assert(!selected.includes('ever'), 'The neutral Ever choice is always available.');
});

test('missing settings leave existing dropdowns and their ordering intact', () => {
    const list = ['ever', 'today', 'lastXWeeksExt', 'on'];
    assert.deepEqual(filterList(list, {}), list);
    assert.deepEqual(list, ['ever', 'today', 'lastXWeeksExt', 'on']);
});

test('disabled groups are hidden, selected items remain, and unknown third-party options are unaffected', () => {
    const list = ['ever', 'today', 'currentWeekExt', 'lastXWeeksExt', 'on', 'thirdPartyDateFilter'];
    const values = {moreDateFiltersWeeks: ['currentWeekExt']};
    assert.deepEqual(filterList(list, values), ['ever', 'today', 'currentWeekExt', 'on', 'thirdPartyDateFilter']);
    assert.deepEqual(filterList(list, values, 'lastXWeeksExt'), list, 'An applied saved filter must not silently change.');
    values.moreDateFiltersWeeks = [];
    assert.deepEqual(filterList(list, values), ['ever', 'today', 'on', 'thirdPartyDateFilter']);
});

test('configuration changes are applied on the next list read without altering supported field types', () => {
    const values = {moreDateFiltersMinutes: [], moreDateFiltersWeeks: []};
    const list = ['ever', 'today', 'lastXWeeksExt']; // A plain date has no minute options.
    assert.deepEqual(filterList(list, values), ['ever', 'today']);
    values.moreDateFiltersWeeks = ['lastXWeeksExt'];
    values.moreDateFiltersMinutes = fields.moreDateFiltersMinutes.options;
    assert.deepEqual(filterList(list, values), list, 'Enabling minutes must not inject them into a date-only field.');
});

test('unchecking all settings leaves the neutral Ever option', () => {
    const values = Object.fromEntries(settings.fieldNames.map(name => [name, []]));
    assert.deepEqual(filterList(['ever', ...Object.values(fields).flatMap(field => field.options)], values), ['ever']);
});

test('admin settings initialize all choices but preserve an explicitly empty or customized selection', () => {
    const View = modules['more-date-filters:views/admin/settings'];
    const view = new View();
    const values = {moreDateFiltersWeeks: [], moreDateFiltersHours: ['currentHourExt']};
    view.model = {get: name => values[name], set: (name, value) => {values[name] = value;}};
    view.getMetadata = () => metadata;
    view.setup();
    assert(view.parentSetupCalled);
    assert.equal(view.layoutName, 'moreDateFiltersSettings');
    assert.deepEqual(values.moreDateFiltersWeeks, []);
    assert.deepEqual(values.moreDateFiltersHours, ['currentHourExt']);
    assert.deepEqual(Array.from(values.moreDateFiltersMinutes), fields.moreDateFiltersMinutes.options);
    assert.notEqual(values.moreDateFiltersMinutes, fields.moreDateFiltersMinutes.options);
});

test('admin page joins the same shared Extension Settings panel as Calendar Plus', () => {
    const panel = read('metadata/app/adminPanel.json').extensionSettings;
    assert.equal(panel.label, 'Extension Settings');
    assert.equal(panel.itemList[0], '__APPEND__');
    const item = panel.itemList[1];
    assert.equal(item.url, '#Admin/moreDateFiltersSettings');
    assert.equal(item.recordView, 'more-date-filters:views/admin/settings');
    const layout = read('layouts/Settings/moreDateFiltersSettings.json');
    const labels = read('i18n/en_US/Settings.json').labels;
    for (const panel of layout) assert(labels[panel.label], 'Panel titles must use the labels translation category.');
    const names = layout.flatMap(panel => panel.rows.flat().filter(Boolean).map(field => field.name));
    assert.deepEqual(new Set(names), new Set(settings.fieldNames));
    const params = read('metadata/app/config.json').params;
    for (const name of settings.fieldNames) assert.equal(params[name].level, 'default');
});
