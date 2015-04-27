var chatAjaxUrl = "";
var chatID;
var chatTimer = 3000;
var tempTimer = 0;
var chatCourseid = 1;

function chatInit(options){
	chatAjaxUrl = options.chatAjaxUrl || chatAjaxUrl;
	chatTimer = options.chatTimer || chatTimer;
	chatCourseid = options.chatCourseid || chatCourseid;
	
	chatID = setInterval(chat_build, chatTimer);
}

function chat_open(){
	jQuery(".chat-mainbox").removeAttr('style');
	jQuery(".chat-mainbox").addClass('open');
}
function chat_close(){
	jQuery(".chat-mainbox").removeClass('open');
}
function chat_change_type(){
	jQuery(".chat-types-box").toggleClass('open');
}
function chat_restart(chatTimer){
	clearInterval(chatID);
	chatID = setInterval(chat_build, chatTimer);
}
function chat_build(){
	if (jQuery(".chat-contacts-content .fa-spin.fa-spinner").length){
		jQuery(".chat-contacts-content .fa-spin.fa-spinner").remove();
	}
	var params = '&courseid='+chatCourseid;
	if (jQuery('.chat-conversation').attr('user-id') != '0'){
		params += '&id='+jQuery('.chat-conversation').attr('user-id');
	}
	jQuery.ajax({
		url: chatAjaxUrl+"?action=chat_load_data"+params,
		type: "GET",
		dataType: "json",
		beforeSend: function(){}
	}).done(function( data ) {
		/*--------------Number of new messages-------------*/
		var newmessages_count = parseInt(data.newmessages_count);
		if (jQuery(".chat-btn strong").length){
			if (newmessages_count > 0){
				jQuery(".chat-btn strong").text(newmessages_count);
				jQuery(".chat-btn strong").attr('items-count', newmessages_count);
			} else {
				jQuery(".chat-btn strong").remove();
			}
		} else {
			if (newmessages_count > 0){
				jQuery(".chat-btn").append('<strong items-count="'+newmessages_count+'">'+newmessages_count+'</strong>');
			}
		}
		if (newmessages_count > 0){
			if (!jQuery(".nav-header-chat i").hasClass('notread')){
				jQuery(".nav-header-chat i").addClass('notread');
			}
		} else {
			if (jQuery(".nav-header-chat i").hasClass('notread')){
				jQuery(".nav-header-chat i").removeClass('notread');
			}
		}
		/*--------------Online users count-------------*/
		if (data.online_users_list == '0'){
			if (!jQuery(".chat-contacts-online .alert").length){
				jQuery(".chat-contacts-online").html('<div class="alert">No online users</div>');
			}
			if (tempTimer != 12000){ tempTimer = 12000; }
		} else {
			/*-------------------Delete offline users---------------------*/
			if (jQuery(".chat-contacts-online .chat-contacts-itembox").length){
				jQuery(".chat-contacts-online .chat-contacts-itembox").each(function(e){
					var old_user = jQuery(this).attr("user-id");
					if (!inArray(old_user, data.online_users)){
						jQuery(this).remove();
					}
				});
			}
			var user_html;
			var contact_item = '';				
			for(var userid in data.online_users_list){
				user_html = data.online_users_list[userid]['user_html'];
				if (jQuery(".chat-contacts-online .contact-item.userid_"+userid).length){
					item_old = jQuery(".chat-contacts-online .contact-item.userid_"+userid).clone().wrap('<p>').parent().html();
					if (item_old.replace(/<img[^>]*>/g,"") != user_html.replace(/<img[^>]*>/g,"")){
						jQuery(".chat-contacts-online .contact-item.userid_"+userid).replaceWith(user_html);
					}
				} else {
					jQuery(".chat-contacts-online").append(user_html);
					chat_sort_list(".chat-contacts-online");
				}
			}
			if (tempTimer != 6000){	tempTimer = 6000; }
		}
		/*--------------Resent users count-------------*/
		if (data.resent_users_list == '0'){
			if (!jQuery(".chat-contacts-resent .alert").length){
				jQuery(".chat-contacts-resent").html('<div class="alert">No resent users</div>');
			}
		} else {
			/*-------------------Delete resent users---------------------*/
			if (jQuery(".chat-contacts-resent .chat-contacts-itembox").length){
				jQuery(".chat-contacts-resent .chat-contacts-itembox").each(function(e){
					var old_user = jQuery(this).attr("user-id");
					if (!inArray(old_user, data.online_users)){
						jQuery(this).remove();
					}
				});
			}
			var user_html;
			var contact_item = '';				
			for(var userid in data.resent_users_list){
				user_html = data.resent_users_list[userid]['user_html'];
				if (jQuery(".chat-contacts-resent .contact-item.userid_"+userid).length){
					item_old = jQuery(".chat-contacts-resent .contact-item.userid_"+userid).clone().wrap('<p>').parent().html();
					if (item_old.replace(/<img[^>]*>/g,"") != user_html.replace(/<img[^>]*>/g,"")){
						jQuery(".chat-contacts-resent .contact-item.userid_"+userid).replaceWith(user_html);
					}
				} else {
					jQuery(".chat-contacts-resent").append(user_html);
					chat_sort_list(".chat-contacts-resent");
				}
			}
		}
		/*--------------Messages-------------*/
		if (jQuery('.chat-conversation').attr('user-id') != '0'){
			if (tempTimer != 3000){ tempTimer = 3000; }
			var userid = jQuery('.chat-conversation').attr('user-id');
			if (data.new_messages_list[userid] != 'undefiled' && data.new_messages_list[userid] != '0'){
				jQuery('.chat-conversation').append(data.new_messages_list[userid]);
				jQuery(".chat-conversation").scrollTop(jQuery(".chat-conversation")[0].scrollHeight);
			}
		}
		if (!jQuery(".chat-mainbox").hasClass("open")) {tempTimer = 15000;}
		if (chatTimer != tempTimer){
			chatTimer = tempTimer;
			chat_restart(tempTimer);
		}
	});
}
function chat_sort_list(el){
	jQuery(el).html(
		jQuery(el).children("li").sort(function (a, b) {
			return jQuery(a).attr("user-name").toUpperCase().localeCompare(
				jQuery(b).attr("user-name").toUpperCase());
		})
	);
}

function inArray(needle, haystack) {
	var length = haystack.length;
	for(var i = 0; i < length; i++) {
		if(haystack[i] == needle) return true;
	}
	return false;
}
function chat_open_conversation(id, name){
	chatTimer = 3000;
	chat_restart(chatTimer);
	jQuery.ajax({
		url: chatAjaxUrl+"?action=chat_load_conversation&id="+id,
		type: "GET",
		dataType: "json",
		beforeSend: function(){
			jQuery(".chat-conversation").html("<i class=\"fa fa-spin fa-spinner\"></i>");
		}
	}).done(function( data ) {
		jQuery(".chat-conversation").html(data.output);
		jQuery(".chat-conversation").scrollTop(jQuery(".chat-conversation")[0].scrollHeight);
		if (data.user_loggedin == '1'){
			jQuery(".header-conversation").text("online");
		} else {
			jQuery(".header-conversation").text("offline");
		}
		jQuery('.chat-form #message').keypress(function (e) {
			if (e.which == 13) 
				chat_save_message();
		});
	});
	jQuery(".chat-messages .user-name").text(name);
	jQuery(".chat-content").toggleClass("chat-view");
	jQuery(".chat-conversation").attr("user-id", id);
	jQuery(".chat-types-box").removeClass('open');
}
function chat_save_message(){
	var message = jQuery('.chat-form #message').val();
	message = message.replace(/(<([^>]+)>)/ig,"");
	var userid = jQuery(".chat-conversation").attr("user-id");
	if (message.length && userid.length){
		jQuery('.chat-form #message').val(''); var ind = Math.floor((Math.random() * 100) + 1);
		jQuery.ajax({
			url: chatAjaxUrl+"?action=chat_create_message&id="+userid,
			type: "POST",
			data: 'message='+ message,
			beforeSend: function(){
				jQuery('.chat-conversation').append('<div class="message clearfix"><div class="chat-bubble sent m_'+ind+'">'+message+'</div></div>');
				jQuery(".chat-conversation").scrollTop(jQuery(".chat-conversation")[0].scrollHeight);
			}
		}).done(function( data ) {
			jQuery('.chat-conversation .sent.m_'+ind).addClass('delivered');
			jQuery('.chat-conversation .sent.m_'+ind).removeClass('m_'+ind);
		});
	}
}
function chat_close_conversation(){
	jQuery(".chat-content").toggleClass("chat-view");
	jQuery(".chat-conversation").attr("user-id", 0);
	jQuery(".chat-conversation").html();
}
function chat_filter_users(type){
	jQuery(".chat-types-box").removeClass('open');
	if (type == 'online'){
		jQuery(".chat-contacts-online").addClass('active');
		jQuery(".chat-contacts-resent").removeClass('active');
		jQuery(".header-contacts").text('Online contacts');
	} else {
		jQuery(".chat-contacts-online").removeClass('active');
		jQuery(".chat-contacts-resent").addClass('active');
		jQuery(".header-contacts").text('Recently contacted');
	}
}