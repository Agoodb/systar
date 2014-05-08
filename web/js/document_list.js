$(function () {
	
	var section = aside.children('section[hash="'+hash+'"]');
	
	section.find('#save').on('click',function(event){
		event.stopImmediatePropagation();
		$.refresh(hash);
	});

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
			
			$(document.body).setBlock(data.result);
			
			section.find('#save').show();
			
			var uploadItem=section.children('.upload-list-item:first').clone();
			
			uploadItem.appendTo(section.find('#upload-info')).removeClass('hidden')
				.attr('id',data.result.data.id).children('[name="document[name]"]').val(data.result.data.name);

			uploadItem.find('select').tagging({
				width:'element'
			})
			.on('change',function(event){
		
				var id=uploadItem.attr('id');
				var label,method;
				
				if(event.added && id){
					label=event.added.id;
					method='add';
				}else if(event.removed && id){
					label=event.removed.id
					method='remove';
				}
				
				if(method && id){
					$.post('/document/'+method+'label/'+id,{label:label});
				}
				
			});
			
			uploadItem.children('[name="document[name]"]').on('change',function(){
				var data = $(this).serialize();
				$.post('/document/update/'+uploadItem.attr('id'),data);
			});
	
        },
		dropZone:section
    });
});
