<table class="contentTable search-bar">
	<thead><tr><th width="80px">发送消息</td></tr></thead>
	<tbody>
		<tr>
			<td>
				<input type="hidden" name="receivers" class="tagging" data-placeholder="收件人" multiple="multiple" data-ajax="/user/match/" style="width:238px;" />
			</td>
		</tr>
		<tr>
			<td>
				<textarea name="content" placeholder="内容" rows="10"></textarea>
			</td>
		</tr>
		<tr>
			<td>
				<input id="fileupload" type="file" name="document" data-url="/document/submit/upload" multiple="multiple" />
				<div id="upload-info"></div>
			</td>
		</tr>
		<tr>
			<td class="submit">
				<button type="submit" name="send" class="major">发送</button>
			</td>
		</tr>
	</tbody>
</table>
<button id="enable-desktop-notification" class="hidden">启用桌面通知</button>
<div id="progress" class="hidden"><div id="bar" style="width:0%;background:#007;height:1em;"></div></div>
<p class="upload-list-item hidden">
	<input type="hidden" name="documents[]" disabled="disabled" />
	<input type="text" name="document[name]" disabled="disabled" placeholder="名称" />
	<hr />
</p>
<script type="text/javascript">
$(function () {
	
	var section = aside.children('section[hash="'+hash+'"]');
	
	if(window.webkitNotifications && window.webkitNotifications.checkPermission() !== 0){
		section.find('#enable-desktop-notification')
		.on('click',function(){
			window.webkitNotifications.requestPermission();
		})
		.show();
	}
	
	section.find('#fileupload').fileupload({
        dataType: 'json',
		start: function(){
			$('#progress').show();
		},
		progress: function(e, data){
			var progress = parseInt(data.loaded / data.total * 100, 10);
			$('#progress>#bar').css({width:progress + '%'});
		},
        done: function (event, data) {
			
			$('#progress>#bar').css({width:0}).hide();
			
			$(document).setBlock(data.result);
			
			var uploadItem=section.children('.upload-list-item:first').clone();
			
			uploadItem.appendTo(section.find('#upload-info'))
				.removeClass('hidden')
				.attr('id',data.result.data.id)
					.children('[name="document[name]"]')
					.removeAttr('disabled')
					.val(data.result.data.name)
				.end()
					.children('[name="documents[]"]')
					.removeAttr('disabled')
					.val(data.result.data.id);

			uploadItem.children('[name="document[name]"]').on('change',function(){
				var data = $(this).serialize();
				$.post('/document/update/'+uploadItem.attr('id'),data);
			});
	
        },
		dropZone:section
    });
});
</script>
