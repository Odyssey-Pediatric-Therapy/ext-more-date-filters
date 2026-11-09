<div class="page-header">
    <h3>{{translate 'moreDateFiltersGuide'}}</h3>
</div>
<div class="panel panel-default">
    <div class="panel-heading"><h4 class="panel-title">{{labels.overview}}</h4></div>
    <div class="panel-body">
        <p>{{intro}}</p>
        <p><strong>{{labels.enabled}}: {{enabledCount}} / {{totalCount}}</strong></p>
        <p class="text-muted">{{availability}}</p>
    </div>
</div>
{{#each groups}}
<div class="panel panel-default">
    <div class="panel-heading"><h4 class="panel-title">{{label}}</h4></div>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th scope="col" style="width: 28%">{{../labels.filter}}</th>
                    <th scope="col" style="width: 14%">{{../labels.status}}</th>
                    <th scope="col">{{../labels.summary}}</th>
                </tr>
            </thead>
            <tbody>
                {{#each rows}}
                <tr>
                    <th scope="row"><code>{{name}}</code></th>
                    <td>
                        <span class="{{#if enabled}}text-success{{else}}text-muted{{/if}}">
                            <span class="fas {{#if enabled}}fa-check{{else}}fa-minus{{/if}}" aria-hidden="true"></span>
                            {{status}}
                        </span>
                    </td>
                    <td>{{{summaryHtml}}}</td>
                </tr>
                {{/each}}
            </tbody>
        </table>
    </div>
</div>
{{/each}}
<div class="panel panel-default">
    <div class="panel-heading"><h4 class="panel-title">{{labels.rules}}</h4></div>
    <div class="panel-body">
        <div style="max-width: 78ch">
            {{#each rules}}
            {{#unless @first}}<hr>{{/unless}}
            <section>
                <h4>{{title}}</h4>
                <ul>
                    {{#each items}}<li><p>{{{this}}}</p></li>{{/each}}
                </ul>
                {{#if example}}
                <p><strong>{{../labels.example}}:</strong> {{{example}}}</p>
                {{/if}}
            </section>
            {{/each}}
        </div>
    </div>
</div>
