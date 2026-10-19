<section class="iss-card">
    <div class="iss-card__header">{$warning_details}</div>
    <div class="table-responsive">
        <table class="iss-table">
            <thead>
                <tr>
                    <th>{$programs}</th>
                    <th>{$Package}</th>
                    <th colspan="2">{$Version}</th>
                    <th colspan="6">{$Options_Backup}</th>
                </tr>
                <tr>
                    <th></th>
                    <th></th>
                    <th>{$local_version}</th>
                    <th>{$external_version}</th>
                    <th>{$Endpoint}</th>
                    <th>{$Fax}</th>
                    <th>{$Email}</th>
                    <th>{$Asterisk}</th>
                    <th>{$Others}</th>
                    <th>{$Others_new}</th>
                </tr>
            </thead>
            <tbody>
            {foreach item=package from=$packagereport}
                <tr>
                    <td>{$package.desc}</td>
                    <td>{$package.name}</td>
                    <td>{$package.version_current}</td>
                    <td>{$package.version_backup}</td>
                    <td>{$package.endpoint}</td>
                    <td>{$package.fax}</td>
                    <td>{$package.email}</td>
                    <td>{$package.asterisk}</td>
                    <td>{$package.otros}</td>
                    <td>{$package.otros_new}</td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    </div>
</section>
