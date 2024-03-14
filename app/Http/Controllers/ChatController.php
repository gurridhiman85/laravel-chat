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
                    $messageText = $chat['content'];

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

                    if($messageClass == 'sender'){

                        $icon_style;
                        if($chat['status'] == 0)
                        {
                            $icon_style = '<span id="chat_status_'.$chat['id'].'" class="float-end chat_status"><i class="fas fa-check text-muted"></i></span>';
                        }
                        elseif($chat['status'] == 1)
                        {
                            $icon_style = '<span id="chat_status_'.$chat['id'].'" class="float-end chat_status"><i class="fas fa-check-double text-muted"></i></span>';
                        }

                        elseif($chat['status'] == 2)
                        {
                            $icon_style = '<span class="text-primary float-end chat_status" id="chat_status_'.$chat['id'].'"><i class="fas fa-check-double double-check-primary"></i></span>';
                        }

                        echo '<li class="clearfix">
                                <div class="message-data align-right">
                                    <span class="message-data-time" >'.$showTime.', '.$showDate.'</span> &nbsp; &nbsp;
                                    <span class="message-data-name" >Me <i class="fa fa-circle me"></i></span>
                                </div>
                                <div class="message other-message float-right">'.$messageText.''.$icon_style.'</div>
                            </li>';
                    }else{
                        echo '<li>
                                <div class="message-data">
                                    <span class="message-data-name"><i class="fa fa-circle online"></i> '.$chat['receriver_details']['name'].'</span>
                                    <span class="message-data-time">'.$showTime.', '.$showDate.'</span>
                                </div>
                                <div class="message my-message">'.$messageText.'</div>
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
