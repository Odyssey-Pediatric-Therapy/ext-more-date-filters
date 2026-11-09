import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import vm from 'node:vm';
import test from 'node:test';
import {createRequire} from 'node:module';
const require = createRequire(import.meta.url);

class TestInputElement {}

const source = 'src/files/client/custom/modules/more-date-filters/src/views';
const context = vm.createContext({HTMLInputElement: TestInputElement, JQuery: () => ({}), BaseFieldView: class {data() {return {};} getSearchTypeList() {return this.searchTypeList;}}});
function loadNative(name, dependencies = {}) {
    Object.assign(context, dependencies);
    const js = `site/client/src/views/fields/${name}.js`;
    const file = fs.existsSync(js) ? js : `site/client/src/views/fields/${name}.ts`;
    let code = fs.readFileSync(file, 'utf8');
    if (file.endsWith('.ts')) {
        const ts = require('typescript');
        code = ts.transpileModule(code, {compilerOptions: {target: ts.ScriptTarget.ES2022, module: ts.ModuleKind.ESNext}}).outputText;
    }
    code = code.replace(/^import[\s\S]*?from ['"][^'"]+['"];?\r?\n/gm, '').replace(/export default (\w+);/, 'globalThis.Native = $1;');
    vm.runInContext(`(function () {${code}\n})();`, context);
    return context.Native;
}
const DateView = loadNative('date');
const DatetimeView = loadNative('datetime', {DateFieldView: DateView});
const modules = {};
context.define = (name, dependencies, factory) => {
    modules[name] = factory(...dependencies.map(name => modules[name] ??
        (name === 'views/fields/date' || name === 'views/fields/foreign-date' || name.includes('working-time-range') ? DateView : DatetimeView)));
};
vm.runInContext(fs.readFileSync(`${source}/../helpers/filter-settings.js`, 'utf8'), context);
vm.runInContext(fs.readFileSync(`${source}/fields/search-mixin.js`, 'utf8'), context);

const views = fs.readdirSync(source, {recursive: true}).filter(file => file.endsWith('.js') && file !== 'fields/search-mixin.js' && file.includes('fields/'));
for (const file of views) {
    test(`${file}: numeric filters display, submit, and restore their count`, () => {
        const code = fs.readFileSync(path.join(source, file), 'utf8');
        vm.runInContext(code, context);
        const name = code.match(/define\('([^']+)'/)[1];
        const view = new modules[name]({});
        for (const type of view.searchTypeList.filter(type => type.includes('X') && type.endsWith('Ext'))) {
            assert(view.searchWithAdditionalNumberTypeList.includes(type), `${type} requires a numeric input`);
        }
        const visible = new Set();
        let attrs;
        let number = '3';
        const zeroOffsets = ['upToNextXWeeksExt', 'upToNextXWeeksFutureExt', 'afterNextXWeeksExt', 'beforeXWeeksAgoExt', 'afterXWeeksAgoExt'];
        view.$el = {find: selector => ({
            addClass: () => visible.delete(selector), removeClass: () => visible.add(selector),
            attr: value => {attrs = value;}, removeAttr: () => {attrs = undefined;}, val: () => number,
        })};
        view.element = {querySelector: selector => Object.assign(new TestInputElement(), {
            classList: {add: () => visible.delete(selector), remove: () => visible.add(selector)},
            value: number,
        })};
        view.$element = {val: () => ''};
        view.parseDate = () => null;
        view.isSearchMode = () => true;
        view.getSearchParamsData = () => ({});
        view.model = {get: () => null};
        view.getDateTime = () => ({toDisplayDate: () => '', toDisplay: () => ''});
        view.getDateStringValue = () => '';
        view.isListMode = () => false;
        view.searchParams = {value: '3'};
        for (const type of view.searchWithAdditionalNumberTypeList) {
            view.fetchSearchType = view.getSearchType = () => type;
            view.handleSearchType(type);
            assert(visible.has('div.additional-number'), type);
            const result = view.fetchSearch();
            assert.equal(result.type, type);
            assert.equal(result.value, '3', type);
            assert.equal(view.data().number, '3', type);
            if (type.endsWith('Ext')) assert.equal(attrs.min, zeroOffsets.includes(type) ? 0 : 1);
            if (zeroOffsets.includes(type)) {
                number = '0';
                view.searchParams.value = 0;
                assert.equal(view.fetchSearch().value, '0', `${type} must submit zero`);
                assert.equal(view.data().number, 0, `${type} must restore zero`);
                number = '3';
                view.searchParams.value = '3';
            }
        }
        view.handleSearchType('today');
        assert(!visible.has('div.additional-number'));
        assert.equal(attrs, undefined);
        if (file.includes('working-time-range')) assert(!view.searchTypeList.includes('currentMinuteExt'));
        const fields = JSON.parse(fs.readFileSync('src/files/custom/Espo/Modules/MoreDateFilters/Resources/metadata/entityDefs/Settings.json', 'utf8')).fields;
        const values = {moreDateFiltersWeeks: []};
        view.getConfig = () => ({get: name => values[name]});
        view.getMetadata = () => ({get: path => path.reduce((node, key) => node?.[key], {entityDefs: {Settings: {fields}}})});
        view.getSearchType = () => null;
        assert(!view.getSearchTypeList().includes('currentWeekExt'), `${file} must apply availability settings`);
        values.moreDateFiltersWeeks = ['currentWeekExt'];
        assert(view.getSearchTypeList().includes('currentWeekExt'));
        assert(!view.getSearchTypeList().includes('lastXWeeksExt'));
        view.getSearchType = () => 'lastXWeeksExt';
        assert(view.getSearchTypeList().includes('lastXWeeksExt'), `${file} must preserve the applied filter`);
        view.getSearchType = () => null;
        for (const name of Object.keys(fields)) values[name] = [];
        assert.deepEqual(Array.from(view.getSearchTypeList()), ['ever']);
    });
}
