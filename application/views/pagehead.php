<script type="text/javascript">
	var controller='<?=CONTROLLER?>';
	var affair='<?=@$this->user->permission[CONTROLLER]['_affair_name']?>';
	var action='<?=METHOD?>';
	var username='<?=$this->user->name?>';
	var sysname='<?=$this->company->sysname?>';
	var lastListAction='<?=$this->session->userdata('last_list_action')?>';
	var asPopupWindow=<?=intval($this->as_popup_window)?>;
</script>