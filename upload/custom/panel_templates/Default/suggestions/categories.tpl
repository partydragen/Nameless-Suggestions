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
                    <h1 class="h3 mb-0 text-gray-800">{$SUGGESTIONS}</h1>
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{$PANEL_INDEX}">{$DASHBOARD}</a></li>
                        <li class="breadcrumb-item">{$SUGGESTIONS}</li>
							<li class="breadcrumb-item active">{$CATEGORIES}</li>
                    </ol>
                </div>

                <!-- Update Notification -->
                {include file='includes/update.tpl'}

                <div class="card shadow mb-4">
                    <div class="card-body">
						<h3 style="display:inline;">{$CATEGORIES}</h3>
						<span class="float-md-right"><a href="{$NEW_CATEGORY_LINK}" class="btn btn-primary">{$NEW_CATEGORY}</a></span>
						<hr>
						
                        <!-- Success and Error Alerts -->
                        {include file='includes/alerts.tpl'}
						
						{if count($CATEGORIES_LIST)}
							<div class="table-responsive">
								<table class="table table-striped" style="width:100%">
									<thead>
									</thead>
									<tbody>
									{foreach from=$CATEGORIES_LIST item=item}
										<tr>
											<td>{$item.name}</td>
											<td>
												<div class="float-md-right">
													<a class="btn btn-warning btn-sm" href="{$item.edit_link}"><i class="fas fa-edit fa-fw"></i></a>
													<button class="btn btn-danger btn-sm" type="button" onclick="showDeleteModal('{$item.delete_link}')"><i class="fas fa-trash fa-fw"></i></button>
												</div>
											</td>
										</tr>
									{/foreach}
									</tbody>
								</table>
                            </div>
						{else}
                            {$NONE_CATEGORIES_DEFINED}
                        {/if}
                        
                        {if !$PREMIUM}
                            <center><p>Suggestion Module by <a href="https://partydragen.com/" target="_blank">Partydragen</a></p></center>
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
	
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{$ARE_YOU_SURE}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                {$CONFIRM_DELETE_CATEGORY}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{$NO}</button>
                <a href="#" id="deleteLink" class="btn btn-primary">{$YES}</a>
            </div>
        </div>
    </div>
</div>

{include file='scripts.tpl'}

<script type="text/javascript">
    function showDeleteModal(id){
        $('#deleteLink').attr('href', id);
        $('#deleteModal').modal().show();
    }
</script>

</body>
</html>