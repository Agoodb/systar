$(function(){
	var section = page.children('section[hash="'+hash+'"]');

	section.find('[name="people[type]"]').on('change',function(){
		if($(this).val()==='客户'){
			$.post('/'+controller+'/submit/changetype/'+uriSegments[2],{type:$(this).val()},function(){
				$.redirect(hash.replace(/^[^\/]*/,'client'));
			});
		}else if($(this).val()==='联系人'){
			$.post('/'+controller+'/submit/changetype/'+uriSegments[2],{type:$(this).val()},function(){
				$.redirect(hash.replace(/^[^\/]*/,'contact'));
			});
		}
	});
	
	/*判别个人或单位，激活不同的表单*/
	section.find('[name="people[character]"]').on('change',function(){
		var character=$(this).is(':checked')?'单位':'个人';
		$.post(hash,{character:character},function(response){
			section.setBlock(response);
		});
	});
	
	/*响应客户来源选项*/
	section.find('[name="profiles[来源类型]"]').on('change',function(){
		if($.inArray($(this).val(),['其他网络','媒体','老客户介绍','合作单位介绍','其他'])===-1){
			$(this).siblings('[name="profiles[来源]"]').hide().attr('disabled','disabled').val('');
		}else{
			$(this).siblings('[name="profiles[来源]"]').removeAttr('disabled').show();
		}
	});
	
	section.find('input[name="people[birthday]"]').click(function(){
		$(this).select();
	});
	
	//根据身份证生成生日
	section.find('input[name="people[id_card]"]').blur(function(){
		if($(this).val().length===18){
			$('input[name="people[birthday]"]').val($(this).val().substr(6,4)+'-'+$(this).val().substr(10,2)+'-'+$(this).val().substr(12,2));
		}
	});
	
	/*相关人添加表单－相关人名称自动完成事件的响应*/
	section.find('.item[name="relative"]')
	.on('autocompleteselect',function(event,data){
		/*有自动完成结果且已选择*/
		$(this).find('[name="relative[id]"]').val(data.value).trigger('change');

		$(this).find('[display-for~="new"]').trigger('disable');
	})
	.on('autocompleteresponse',function(){
		/*自动完成响应*/
		$(this).find('[display-for~="new"]').trigger('enable');
		$(this).find('[name="relative[id]"]').val('').trigger('change');
	});
	
	/*相关人关系“其他”选项*/
	section.find('[name="relative[relation]"]').change(function(){
		if($(this).val()===''){
			$('<input>',{type:'text',name:$(this).attr('name'),placeholder:$(this).children('option:first').html()}).insertAfter(this);
		}
	});
});