<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Models\User;
use App\Models\Chat;

class ChatController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $userId = Auth::user()->id;

        $users = User::whereNot('users.id', $userId)
        ->leftJoin('chats', function ($join) use ($userId){
            $join->on('users.id', '=', 'chats.senderId')
                ->where('chats.status', '!=', 2)
                ->where('chats.receiverId', '=', $userId);
        })
        ->selectRaw('users.id, users.name,users.loginStatus, COUNT(chats.id) as unread_messages_count')
        ->groupBy('users.id', 'users.name', 'users.loginStatus')
        ->get();
        return view('chat',['users' => $users]);
    }

    public function get_chats($targetUserId = null){
        if(!empty($targetUserId)){
            $userId = Auth::user()->id;
            $chats = Chat::with(['senderDetails','receriverDetails'])->where(function ($query) use ($userId, $targetUserId) {
                        $query->where('senderId', $userId)
                            ->where('receiverId', $targetUserId);
                    })
                    ->orWhere(function ($query) use ($userId, $targetUserId){
                        $query->where('senderId', $targetUserId)
                            ->where('receiverId', $userId);
                    })
                    ->orderBy('id','ASC')->get();
            // dd($chats->toArray());
            if(!empty($chats) && count($chats) > 0){

                foreach($chats as $chat){
                    $messageText = '<span class="messageText">'.$chat['content'].'</span>';

                    $senderId = $chat['senderId'];
                    $messageClass = ($senderId == $userId) ? 'sender' : 'receiver';

                    $messgeDate = date('d/m/Y',strtotime($chat['created_at']));

                    if($messgeDate == date('d/m/Y')){
                        $showDate = "Today";
                    }elseif($messgeDate == date('d/m/Y', strtotime("-1 day"))){
                        $showDate = "Yesterday";
                    }else{
                        $showDate = $messgeDate;
                    }
                    $showTime = date('g:i A',strtotime($chat['created_at']));

                    $edit_text = '';
                    if($chat['isEdited'] == 1){
                        $edit_text = '<span class="messageEdited">(edited)</span>';
                    }

                    if($messageClass == 'sender'){
                        $icon_style;
                        $options = '<i onclick="showMsgOption(this)" class="fa fa-ellipsis-v options-msg" aria-hidden="true"></i>';
                        if($chat['status'] == 0)
                        {
                            $icon_style = '<span id="chat_status_'.$chat['id'].'" class="float-end chat_status"><i class="fas fa-check text-muted"></i></span>';
                        }
                        elseif($chat['status'] == 1)
                        {
                            $icon_style = '<span id="chat_status_'.$chat['id'].'" class="float-end chat_status"><i class="fas fa-check-double text-muted"></i></span>';
                        }

                        elseif($chat['status'] == 2){
                            $icon_style = '<span class="text-primary float-end chat_status" id="chat_status_'.$chat['id'].'"><i class="fas fa-check-double double-check-primary"></i></span>';
                        }
                        $messageText = $messageText.''.$icon_style.''.$edit_text;
                        $class = 'message-options-sender';
                        if($chat['deleteStatus'] == 1 || $chat['deleteStatus'] == 2){
                            $messageText = '<i class="fa fa-window-close" aria-hidden="true" style="font-size: 12px;"> &nbsp; You deleted this message.</i>';
                            $class = '';
                        }

                        echo '<li class="clearfix">
                                <div class="message-data align-right">
                                    <span class="message-data-time" >'.$showTime.', '.$showDate.'</span> &nbsp; &nbsp;
                                    <span class="message-data-name" >Me <i class="fa fa-circle me"></i></span>
                                </div>
                                <div class="message other-message '.$class.' float-right" id="chatDiv'.$chat['id'].'">'.$messageText.'</div>
                            </li>';
                    }else{
                        $messageText = $messageText.''.$edit_text;
                        if($chat['deleteStatus'] == 2){
                            $messageText = '<i class="fa fa-window-close" aria-hidden="true" style="font-size: 12px;"> &nbsp; This message was deleted.</i>';
                        }
                        echo '<li>
                                <div class="message-data">
                                    <span class="message-data-name"><i class="fa fa-circle online"></i> '.$chat['receriverDetails']['name'].'</span>
                                    <span class="message-data-time">'.$showTime.', '.$showDate.'</span>
                                </div>
                                <div class="message my-message" id="chatDiv'.$chat['id'].'">'.$messageText.'</div>
                            </li>';
                    }
                    // $date = $messgeDate;
                }
            }else{
                echo"<p>No messages yet. Start the conversation!</p>";
            }
        }
    }
}
