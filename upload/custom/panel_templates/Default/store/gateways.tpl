{include file='header.tpl'}

<body id="page-top">

<!-- Wrapper -->
<div id="wrapper">

    <!-- Sidebar -->
    {include file='sidebar.tpl'}

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

        <!-- Main content -->
        <div id="content">

            <!-- Topbar -->
            {include file='navbar.tpl'}

            <!-- Begin Page Content -->
            <div class="container-fluid">

                <!-- Page Heading -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">{$GATEWAYS}</h1>
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{$PANEL_INDEX}">{$DASHBOARD}</a></li>
                        <li class="breadcrumb-item active">{$STORE}</li>
                        <li class="breadcrumb-item active">{$GATEWAYS}</li>
                    </ol>
                </div>

                <!-- Update Notification -->
                {include file='includes/update.tpl'}

                <div class="card shadow mb-4">
                    <div class="card-body">

                        <!-- Success and Error Alerts -->
                        {include file='includes/alerts.tpl'}

                        {if isset($GATEWAYS_LIST)}
                        <div class="table-responsive">
                            <table class="table table-striped dataTables-payments">
                                <thead>
                                    <tr>
                                        <th>{$PAYMENT_METHOD}</th>
                                        <th>{$ENABLED}</th>
                                        <th><div class="float-right">{$EDIT}</div></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach from=$GATEWAYS_LIST item=gateway}
                                        <tr>
                                            <td>{$gateway.name}</td>
                                            <td>{if $gateway.enabled}<span class="badge badge-success">{$ENABLED}</span>{else}<span class="badge badge-danger">{$DISABLED}</span>{/if}</td>
                                            <td><a href="{$gateway.edit_link}" class="btn btn-primary btn-sm float-right">{$EDIT}</a></td>
                                        </tr>
                                    {/foreach}
                                </tbody>
                            </table>
                        </div>
                        {/if}
                        
                    </div>
                </div>

                <!-- Spacing -->
                <div style="height:1rem;"></div>

                <!-- End Page Content -->
            </div>

            <!-- End Main Content -->
        </div>

        {include file='footer.tpl'}

        <!-- End Content Wrapper -->
    </div>

    <!-- End Wrapper -->
</div>

{include file='scripts.tpl'}


</body>
</html>