define('more-date-filters:controllers/guide', ['controller'], Controller => class extends Controller {
    actionIndex() {
        this.main('more-date-filters:views/guide', {}, view => view.render());
    }
});
