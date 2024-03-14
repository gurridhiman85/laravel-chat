<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Ratchet\MessageComponentInterface;

use Ratchet\ConnectionInterface;

use App\Models\User;

use App\Models\Chat;

use App\Models\Chat_request;

use Auth;

class SocketController extends Controller implements MessageComponentInterface
{
    protected $clients;
    protected $userID;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);

        $queryParams = $conn->httpRequest->getUri()->getQuery();
        parse_str($queryParams, $queryParameters);
        $this->userID = isset($queryParameters['userID']) ? $queryParameters['userID'] : null;

        if(!empty($this->userID)){
            $userDetails = User::find($this->userID);
            $userDetails->connectionId = $conn->resourceId;
            $userDetails->loginStatus = 1;
            $userDetails->save();

            $data['userID'] = $this->userID;
            $data['loginStatus'] = 1;

            foreach ($this->clients as $client) {
                $client->send(json_encode($data));
            }
            echo "New connection! ({$conn->resourceId})\n";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }

    }

     public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg);

        if($data->type == 'request_send_message'){
            $chat = new Chat;

            $chat->senderId = $data->from_user_id;

            $chat->receiverId = $data->to_user_id;

            $chat->content = $data->message;

            $chat->status = 0;

            $chat->save();

            $chat_message_id = $chat->id;

            $receiver_connection_id = User::select('connectionId')->where('id', $data->to_user_id)->first();

            $sender_connection_id = User::select('connectionId')->where('id', $data->from_user_id)->first();


            foreach ($this->clients as $client) {
                if ($sender_connection_id->connectionId == $client->resourceId || $receiver_connection_id->connectionId == $client->resourceId) {
                    // The sender is not the receiver, send to each client connected
                    $send_data['chat_message_id'] = $chat_message_id;

                    $send_data['message'] = $data->message;

                    $send_data['from_user_id'] = $data->from_user_id;

                    $send_data['to_user_id'] = $data->to_user_id;

                    if($client->resourceId == $receiver_connection_id->connectionId)
                    {
                        Chat::where('id', $chat_message_id)->update(['status' => 1]);

                        $send_data['status'] = 1;
                    }
                    else
                    {
                        $send_data['status'] = 0;
                    }

                    $client->send(json_encode($send_data));
                }
            }
        }

        if($data->type == 'update_chat_status')
        {
            if(!empty($data->chat_message_id)){
                Chat::where('id', $data->chat_message_id)->update(['status' => $data->chat_message_status]);
            }elseif($data->chat_message_status == 2){
                $to_user_id = $data->to_user_id;
                $from_user_id = $data->from_user_id;
                $unReadChats = Chat::where(function ($query) use ($to_user_id, $from_user_id) {
                    $query->where('senderId', $from_user_id)
                        ->where('receiverId', $to_user_id);
                })->where('status','!=',2)->get();

                Chat::where('senderId',$data->from_user_id)->where('receiverId',$data->to_user_id)->update(['status' => $data->chat_message_status]);
            }elseif($data->chat_message_status == 1){
                $to_user_id = $data->to_user_id;
                $from_user_id = $data->from_user_id;
                $unReadChats = Chat::where(function ($query) use ($to_user_id, $from_user_id) {
                    $query->where('senderId', $from_user_id)
                        ->where('receiverId', $to_user_id);
                })->where('status',0)->get();

                Chat::where('senderId',$data->from_user_id)->where('receiverId',$data->to_user_id)->where('status',0)->update(['status' => $data->chat_message_status]);
            }


            $sender_connection_id = User::select('connectionId')->where('id', $data->from_user_id)->first();

            foreach($this->clients as $client)
            {
                if($client->resourceId == $sender_connection_id->connectionId)
                {
                    if(!empty($data->chat_message_id)){
                        $send_data['update_message_status'] = $data->chat_message_status;
                        $send_data['chat_message_id'] = $data->chat_message_id;
                        $client->send(json_encode($send_data));
                    }else{
                        foreach($unReadChats as $unReadChat){
                            $send_data['update_message_status'] = $data->chat_message_status;
                            $send_data['chat_message_id'] = $unReadChat->id;
                            $client->send(json_encode($send_data));
                        }
                    }
                }
            }
        }

        if($data->type == 'typing_notification'){
            $receiver_connection_id = User::select('connectionId')->where('id', $data->to_user_id)->first();

            foreach($this->clients as $client)
            {
                if($client->resourceId == $receiver_connection_id->connectionId)
                {
                    $client->send(json_encode($data));
                }
            }
        }
     }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $queryParams = $conn->httpRequest->getUri()->getQuery();
        parse_str($queryParams, $queryParameters);
        $this->userID = isset($queryParameters['userID']) ? $queryParameters['userID'] : null;

        $data['userID'] = $this->userID;
        $data['loginStatus'] = 0;

        $userDetails = User::find($this->userID);
        $userDetails->connectionId = 0;
        $userDetails->loginStatus = 0;
        $userDetails->save();

        foreach ($this->clients as $client) {
            $client->send(json_encode($data));
        }

        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }
}
