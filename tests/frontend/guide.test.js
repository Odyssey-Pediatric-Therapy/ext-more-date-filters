import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
import test from 'node:test';
import {createRequire} from 'node:module';
const require = createRequire(import.meta.url);
const Handlebars = require('handlebars');
const resources = 'src/files/custom/Espo/Modules/MoreDateFilters/Resources/';
const client = 'src/files/client/custom/modules/more-date-filters/';
const read = file => JSON.parse(fs.readFileSync(resources + file, 'utf8'));
const fields = read('metadata/entityDefs/Settings.json').fields;
const guide = read('i18n/en_US/MoreDateFiltersGuide.json');
const settings = read('i18n/en_US/Settings.json');
const native = JSON.parse(fs.readFileSync('site/application/Espo/Resources/i18n/en_US/Global.json', 'utf8'));
const options = {...native.options.dateSearchRanges, ...read('i18n/en_US/Global.json').options.dateSearchRanges};
const modules = {view: class {}};
const context = vm.createContext({define: (name, deps, factory) => {
    modules[name] = factory(...deps.map(name => modules[name]));
}});
for (const file of ['helpers/filter-settings.js', 'views/guide.js']) {
    vm.runInContext(fs.readFileSync(client + 'src/' + file, 'utf8'), context);
}
function data(values = {}) {
    const view = new modules['more-date-filters:views/guide']();
    view.getHelper = () => ({escapeString: Handlebars.escapeExpression});
    view.getConfig = () => ({get: key => values[key]});
    view.getMetadata = () => ({get: path => path.reduce((node, key) => node?.[key], {entityDefs: {Settings: {fields}}})});
    view.translate = (key, category, scope) => (scope === 'Settings' ? settings : guide)[category]?.[key] ?? key;
    view.getLanguage = () => ({translateOption: type => options[type] ?? type});
    return view.data();
}

test('guide documents every configurable filter plus Ever with readable labels and summaries', () => {
    const result = data();
    const rows = result.groups.flatMap(group => group.rows);
    const expected = ['ever', ...Object.values(fields).flatMap(field => field.options)];
    assert.equal(rows.length, expected.length);
    assert.equal(result.enabledCount, expected.length);
    assert.equal(result.totalCount, expected.length);
    for (const type of expected) {
        assert(options[type], `Missing filter label: ${type}`);
        assert(guide.filterSummaries[type], `Missing filter summary: ${type}`);
        assert(rows.some(row => row.name === options[type] && row.summary === guide.filterSummaries[type]), type);
    }
});

test('guide status matches configured availability, including empty groups and unset defaults', () => {
    const values = {moreDateFiltersWeeks: ['lastXWeeksExt'], moreDateFiltersCommon: []};
    let result = data(values);
    let rows = result.groups.flatMap(group => group.rows);
    assert(rows.find(row => row.name === options.ever).enabled);
    assert(!rows.find(row => row.name === options.today).enabled);
    assert(rows.find(row => row.name === options.lastXWeeksExt).enabled);
    assert(!rows.find(row => row.name === options.currentWeekExt).enabled);
    assert(rows.find(row => row.name === options.currentHourExt).enabled);
    for (const name of Object.keys(fields)) values[name] = [];
    result = data(values);
    assert.equal(result.enabledCount, 1, 'Ever is always enabled.');
    assert.equal(result.groups.flatMap(group => group.rows).filter(row => row.status === 'Disabled').length, result.totalCount - 1);
});

test('guide renders grouped tables with status text and summaries instead of a Markdown wall', () => {
    const render = Handlebars.compile(fs.readFileSync(client + 'res/templates/guide.tpl', 'utf8'));
    const result = data({moreDateFiltersWeeks: []});
    const html = render(result, {helpers: {translate: () => 'Date filter guide'}});
    assert.equal((html.match(/<table /g) || []).length, result.groups.length);
    assert.equal((html.match(/<th scope="row">/g) || []).length, result.totalCount);
    assert(html.includes('Disabled'));
    assert(html.includes('Enabled'));
    assert(html.includes(guide.filterSummaries.lastXWeeksExt));
    assert(!html.includes('alert-info'));
    for (const name of ['Is Empty', 'Date Start', 'Date End', 'Today', 'Last X Hours', 'Next X Hours']) {
        assert(html.includes(`<code>${name}</code>`), `${name} should be highlighted as inline code`);
    }
});


test('guide is selectable as a navbar tab for ordinary users in User Interface settings', () => {
    const scope = read('metadata/scopes/MoreDateFiltersGuide.json');
    assert.equal(scope.tab, true);
    assert.equal(scope.acl, 'boolean');
    const acl = read('metadata/app/acl.json');
    assert.equal(acl.mandatory.scopeLevel.MoreDateFiltersGuide, true);
    assert.equal(acl.adminMandatory.scopeLevel.MoreDateFiltersGuide, true);
    assert.equal(scope.entity, false);
    assert.notEqual(scope.disabled, true);
    const labels = read('i18n/en_US/Global.json');
    assert.equal(labels.scopeNamesPlural.MoreDateFiltersGuide, 'Date filter guide');
    const defs = read('metadata/clientDefs/MoreDateFiltersGuide.json');
    assert.equal(defs.controller, 'more-date-filters:controllers/guide');
    assert(defs.iconClass);
});


test('native Tab List Add dialog includes the unselected guide for both admins and ordinary users', () => {
    const nativeContext = vm.createContext({ArrayFieldView: class {}, BullView: {extend() {}}});
    for (const [file, name] of [['acl', 'NativeAcl'], ['views/settings/fields/tab-list', 'NativeTabList']]) {
        let code = fs.readFileSync(`site/client/src/${file}.js`, 'utf8');
        code = code.replace(/^import .*;\r?\n/gm, '').replace(/export default (\w+);/, `globalThis.${name} = $1;`);
        vm.runInContext(`(function () {${code}\n})();`, nativeContext);
    }
    const scope = read('metadata/scopes/MoreDateFiltersGuide.json');
    const aclMetadata = read('metadata/app/acl.json');
    for (const isAdmin of [false, true]) {
        const acl = new nativeContext.NativeAcl({isAdmin: () => isAdmin}, 'MoreDateFiltersGuide', {}, {});
        const table = isAdmin ? aclMetadata.adminMandatory.scopeLevel : aclMetadata.mandatory.scopeLevel;
        const view = new nativeContext.NativeTabList();
        view.params = {};
        view.selected = []; // The guide was removed; it must be available to add again.
        view.noDelimiters = true;
        view.getAcl = () => ({checkScope: name => acl.checkScope(table[name] ?? null)});
        view.getMetadata = () => ({get: path => path.split('.').reduce((value, key) => value?.[key], {scopes: {MoreDateFiltersGuide: scope}})});
        view.translate = () => 'Date filter guide';
        view.setupOptions();
        assert(view.params.options.includes('MoreDateFiltersGuide'), `Missing from Add dialog for ${isAdmin ? 'admin' : 'ordinary user'}`);
        assert.equal(view.translatedOptions.MoreDateFiltersGuide, 'Date filter guide');
    }
});
