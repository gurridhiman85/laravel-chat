@extends('layouts.main')

@section('content')
<!-- partial:index.partial.html -->
<div class="clearfix">
    <div class="people-list" id="people-list">
      <div class="search">
        <input type="text" placeholder="search" />
        <i class="fa fa-search"></i>
      </div>
      <ul class="list">
        @if(!empty($users))
            @foreach($users as $user)
                <li class="clearfix contact-user" onclick="getChats({{$user->id}},'{{$user->name}}')">
                    <img src="https://s3-us-west-2.amazonaws.com/s.cdpn.io/195612/chat_avatar_01.jpg" alt="avatar" />
                    <div class="about">
                        <div class="name">{{$user->name}}</div>
                        <div class="status user-status-{{$user->id}}">
                            @if($user->loginStatus == 1) <i class="fa fa-circle online"></i> online
                            @else<i class="fa fa-circle offline"></i> offline
                            @endif
                        </div>
                    </div>
                    <span class="user_unread_message" data-count="{{$user->unread_messages_count}}" id="user_unread_message_{{$user->id}}">
                        @if($user->unread_messages_count > 0)
                            <span class="badge bg-primary unread_chat_counts" title="Unread Messages">{{$user->unread_messages_count}}</span>
                        @endif
                    </span>
                </li>
            @endforeach
        @endif
      </ul>
    </div>

    <div class="chat">
      <div class="chat-header clearfix">
        <div class="chat-header-1">
            <div class="chat-about">
                <div class="chat-with">Chats</div>
            </div>
        </div>
        <div class="chat-header-2" style="display:none">
            <img src="https://s3-us-west-2.amazonaws.com/s.cdpn.io/195612/chat_avatar_01_green.jpg" alt="avatar" />

            <div class="chat-about">
              <div class="chat-with">Chat with <span class="chat-with-name">Vincent Porter</span></div>
              <p class="typing_status"></p>
            </div>
            <i class="fa fa-star"></i>
        </div>

      </div> <!-- end chat-header -->

      <div class="chat-history">
        <ul class="chats-list">
        </ul>

      </div> <!-- end chat-history -->

      <div class="chat-message clearfix">
        <div class="chat-footer-1" style="height:150px">
        </div>
        <div class="chat-footer-2" style="display:none;">
            <textarea name="message-to-send" id="message-to-send" placeholder ="Type your message" data-id="" rows="3"></textarea>
            <i class="fa fa-file-o"></i> &nbsp;&nbsp;&nbsp;
            <i class="fa fa-file-image-o"></i>
            <button onclick="removeMessage(this)" id="removeMessage" style="color: #db8787; display:none;">Cancel</button>
            <button onclick="sendMessage()">Send</button>
        </div>
      </div> <!-- end chat-message -->
    </div> <!-- end chat -->
</div> <!-- end container -->
<script>
    var conn = new WebSocket('ws://127.0.0.1:8090/?userID={{ auth()->user()->id }}');

    var from_user_id = {{ Auth::user()->id }};

    var to_user_id;
</script>
@endsection

