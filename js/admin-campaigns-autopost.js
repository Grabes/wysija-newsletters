jQuery(function(d){function c(){var e=d("#category_ids").val();if(e.length===0){return[]}else{return e.split(",")}}function b(){var e=[];d(".categories:visible").each(function(f,g){if(d(this).val()!==""){e.push(d(this).val())}});e=d.unique(e);d("#category_ids").val(e.join(","))}function a(e){d("#category-"+e).remove()}d(".categories:visible").live("change",function(){b()});d("#add-category").click(function(){if((d("#category_selection li").length+1)>=d(".categories:hidden option").length){return}var e=d("#category_list").html();d("#category_selection").append('<li id="category-'+(parseInt(d("#category_selection li").length)+1)+'" class="clearfix"><span>'+e+'</span><a class="icon-minus remove-category" rel="'+(parseInt(d("#category_selection li").length)+1)+'" href="javascript:;"><span></span></a></li>');b();return false});d(".remove-category").live("click",function(){a(d(this).attr("rel"));b();return false});d("#autopost-submit").click(function(){wysijaAJAX.task="generate_auto_post";wysijaAJAX.wysijaData=d("#autopost-form").serializeArray();jQuery.ajax({type:"POST",url:wysijaAJAX.ajaxurl,data:wysijaAJAX,success:function(e){if(e.result==false){}else{window.parent.WysijaPopup.getInstance().callback(e.result);window.parent.WysijaPopup.close()}}});return false});d(function(){b()})});