conn.onopen = function(e) {
    console.log("Connection established!");
};
conn.onmessage = function(e) {
    var data = JSON.parse(e.data);
    if(data.loginStatus == 1){
        $('.user-status-'+data.userID).html('<i class="fa fa-circle online"></i> online');
        if(data.userID == to_user_id){
            update_message_status(null, from_user_id, to_user_id, 1);
        }
    }else if(data.loginStatus == 0){
        $('.user-status-'+data.userID).html('<i class="fa fa-circle offline"></i> offline')
    }else if(data.type == 'edit_message'){
        if(data.from_user_id == from_user_id || (to_user_id != '' && data.from_user_id == to_user_id)){
            $('#chatDiv'+data.chat_message_id+' .messageText').text(data.message);
            $('#chatDiv'+data.chat_message_id).append('<span class="messageEdited">(edited)</span>');

        }
    }else if(data.message){
        var html = '';

		if(data.from_user_id == from_user_id)
		{
            var icon_style = '';

			if(data.status == 0)
			{
				icon_style = '<span id="chat_status_'+data.chat_message_id+'" class="float-end chat_status"><i class="fas fa-check text-muted"></i></span>';
			}
			if(data.status == 1)
			{
				icon_style = '<span id="chat_status_'+data.chat_message_id+'" class="float-end chat_status"><i class="fas fa-check-double text-muted"></i></span>';
			}

			if(data.status == 2)
			{
				icon_style = '<span class="text-primary float-end chat_status" id="chat_status_'+data.chat_message_id+'"><i class="fas fa-check-double double-check-primary"></i></span>';
			}

			html += `
			<li class="clearfix">
                <div class="message-data align-right">
                    <span class="message-data-time" >`+data.time+`, Today</span> &nbsp; &nbsp;
                    <span class="message-data-name" >Me <i class="fa fa-circle me"></i></span>
                </div>
                <div class="message other-message message-options-sender float-right" id="chatDiv`+data.chat_message_id+`"><span class="messageText">`+data.message+`</span> `+icon_style+`</div>
            </li>
			`;
		}
		else
		{
			if(to_user_id != '' && data.from_user_id == to_user_id)
			{
				html += `
				<li>
                    <div class="message-data">
                        <span class="message-data-name"><i class="fa fa-circle online"></i> `+data.name+`</span>
                        <span class="message-data-time">`+data.time+`, Today</span>
                    </div>
                    <div class="message my-message" id="chatDiv`+data.chat_message_id+`"><span class="messageText">`+data.message+`</span></div>
                </li>
				`;
                update_message_status(data.chat_message_id, data.from_user_id, data.to_user_id, 2);
			}
			else
			{
				var count_unread_message_element = $('#user_unread_message_'+data.from_user_id+'');
            	if(count_unread_message_element)
            	{
	            	var count_unread_message = count_unread_message_element.attr('data-count');
                    console.log('count_unread_message',count_unread_message);

	                count_unread_message = parseInt(count_unread_message) + 1;
                    count_unread_message_element.attr('data-count',count_unread_message);

	            	count_unread_message_element.html('<span class="badge bg-primary unread_chat_counts" title="Unread Messages">'+count_unread_message+'</span>');

	            	update_message_status(data.chat_message_id, data.from_user_id, data.to_user_id, 1);
	            }
			}

		}

		if(html != '')
		{
			$('.chats-list').append(html);
            scroll_top();
		}
    }
    else if(data.update_message_status)
	{
		var chat_status_element = $('#chat_status_'+data.chat_message_id+'');

		if(chat_status_element)
		{
			if(data.update_message_status == 2)
			{
				chat_status_element.html('<i class="fas fa-check-double double-check-primary"></i>');
			}
			if(data.update_message_status == 1)
			{
				chat_status_element.html('<i class="fas fa-check-double text-muted"></i>');
			}
		}
	}else if(data.type == 'typing_notification'){
        if(to_user_id == data.from_user_id){
            if(data.statusType == 'typing'){
                $(".typing_status").html('typing...')
            }else{
                $(".typing_status").html('')
            }
        }
    }
    else if(data.type == 'delete_message'){

        if(data.senderId == from_user_id)
		{
            $("#chatDiv"+data.chatId).html('<i class="fa fa-window-close" aria-hidden="true" style="font-size: 12px;"> &nbsp; You deleted this message.</i>');
            $("#chatDiv"+data.chatId).removeClass('message-options-sender');
		}else if(data.receiverId == from_user_id && data.senderId == to_user_id && data.deleteStatus == 2){
            $("#chatDiv"+data.chatId).html('<i class="fa fa-window-close" aria-hidden="true" style="font-size: 12px;"> &nbsp; This message was deleted.</i>');
        }
    }
};

function getChats(targetUserId,targetUserName){
    if(targetUserId != ''){
        to_user_id = targetUserId;
        $.ajax({
            url: '/get_chats/' + targetUserId,
            method: 'GET',
            success: function (response) {
                $('.chats-list').html(response);
                $(".chat-header-1").hide();
                $(".chat-header-2").show();
                $('.chat-footer-1').hide();
                $('.chat-footer-2').show();
                $('.chat-with-name').html(targetUserName);
                $("#user_unread_message_"+targetUserId).html('');
                $("#user_unread_message_"+targetUserId).attr('data-count',0);
                update_message_status(null, targetUserId, from_user_id, 2);
                scroll_top();
                removeMessage();
            }
        });
    }
}

function sendMessage(){
    var message = $("#message-to-send").val();
    var chatId = '';
    if($("#message-to-send").attr('data-id')){
        chatId = $("#message-to-send").attr('data-id');
    }
    if(message != ''){
        var data = {
            message : message,
            from_user_id : from_user_id,
            to_user_id : to_user_id,
            type : 'request_send_message',
            chatId : chatId
        };

        conn.send(JSON.stringify(data));
        $("#message-to-send").val('');
        removeMessage();
    }
}

function scroll_top()
{
    $('.chat-history').scrollTop($('.chat-history')[0].scrollHeight);
}

function scroll_to_botttom(){
    $("html, body").animate({ scrollTop: $(document).height() }, 1000);
}

function update_message_status(chat_message_id=null, from_user_id, to_user_id, chat_message_status){
    var data = {
		chat_message_id : chat_message_id,
		from_user_id : from_user_id,
		to_user_id : to_user_id,
		chat_message_status : chat_message_status,
		type : 'update_chat_status'
	};
	conn.send(JSON.stringify(data));
}

function sendTypingNotification(type){
    var data = {
        from_user_id : from_user_id,
		to_user_id : to_user_id,
		statusType : type,
		type : 'typing_notification'
	};
	conn.send(JSON.stringify(data));
}

function deleteMessage(id,deleteStatus){
    var data = {
        chatId : id,
		deleteStatus : deleteStatus,
		type : 'delete_message'
	};
	conn.send(JSON.stringify(data));
    removeMessage();
}

function editMessage(id){
    $('#removeMessage').show();
    $("#message-to-send").val($("#chatDiv"+id+" .messageText").text());
    $("#message-to-send").attr('data-id',id);
    scroll_to_botttom();
}

function forwardMessage(id){

}

function removeMessage(event){
    $("#message-to-send").val('');
    $("#message-to-send").attr('data-id','');
    $('#removeMessage').hide();
}

$(document).ready(function(){
    var typingCheckInterval = 5000;
    let typingTimeout;
    $('#message-to-send').on('keydown',function(){
        clearTimeout(typingTimeout);
        sendTypingNotification('typing')
    });
    $('#message-to-send').on('keyup',function(){
        typingTimeout = setTimeout(function(){sendTypingNotification('stoped')}, typingCheckInterval);
    });

});

$(function() {
    $.contextMenu({
        selector: '.message-options-sender',
        callback: function(key, options) {
            var m = "clicked: " + key;
            var msgIdString = $(this).find('.chat_status').attr('id');
            var msgIdArray = msgIdString.split("_");
            var id = msgIdArray[msgIdArray.length - 1];

            if(!isNaN(id)){
                if(key == 'edit'){
                    editMessage(id);
                }else if(key == 'deleteforme'){
                    deleteMessage(id,1);
                }
                else if(key == 'deleteforeveryone'){
                    deleteMessage(id,2);
                }
                else if(key == 'forwand'){
                    forwardMessage(id);
                }
            }

            // window.console && console.log(m) || alert(m);
        },
        items: {
            "forwand":{name:"Forward", icon: "fa send"},
            "edit": {name: "Edit", icon: "edit"},
            "delete": {name: "Delete", icon: "delete",
                        items: {
                            "deleteforme": {name: "Delete for me", icon: "delete"},
                            "deleteforeveryone": {name: "Delete for everyone", icon: "delete"},
                        }
                    },
        }
    });
});
