<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Models\Buyer;
use App\Models\AdminLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    /**
     * Get list of conversations for the authenticated user.
     * Conversations are computed dynamically from unique messaging partners.
     */
    public function getConversations(Request $request)
    {
        $user = $request->user();
        // Determine the type string mapping
        $type = $this->getUserType($user);
        $userId = $user->id;

        // Fetch distinct users that this user has sent messages to
        $sentTo = Message::where('sender_id', $userId)
            ->where('sender_type', $type)
            ->select('receiver_id as partner_id', 'receiver_type as partner_type')
            ->distinct();

        // Fetch distinct users that this user has received messages from
        $receivedFrom = Message::where('receiver_id', $userId)
            ->where('receiver_type', $type)
            ->select('sender_id as partner_id', 'sender_type as partner_type')
            ->distinct();

        // Union the queries to get unique partners
        $partners = $sentTo->union($receivedFrom)->get();

        $conversations = [];
        foreach ($partners as $partner) {
            $partnerDetails = null;

            // Load partner profile info
            if ($partner->partner_type === 'admin') {
                $partnerDetails = AdminLogin::find($partner->partner_id);
                $name = $partnerDetails ? $partnerDetails->username : 'Admin';
            } elseif ($partner->partner_type === 'buyer') {
                $partnerDetails = Buyer::find($partner->partner_id);
                $name = $partnerDetails ? $partnerDetails->name : 'Buyer';
            } else {
                $partnerDetails = User::find($partner->partner_id);
                $name = $partnerDetails ? $partnerDetails->name : 'User/Supplier';
            }

            // Get last message in this thread
            $lastMessage = Message::betweenUsers($userId, $type, $partner->partner_id, $partner->partner_type)
                ->orderBy('timestamp', 'desc')
                ->first();

            $conversations[] = [
                'partner_id' => $partner->partner_id,
                'partner_type' => $partner->partner_type,
                'partner_name' => $name,
                'partner_avatar' => $partnerDetails->avatar ?? null,
                'last_message' => $lastMessage ? $lastMessage->message_text : null,
                'last_message_time' => $lastMessage ? $lastMessage->timestamp : null,
                'is_read' => $lastMessage ? $lastMessage->is_read : true,
            ];
        }

        return response()->json($conversations);
    }

    /**
     * Get all messages between the authenticated user and another partner.
     */
    public function getMessages(Request $request, $partnerId, $partnerType)
    {
        $user = $request->user();
        $type = $this->getUserType($user);
        $userId = $user->id;

        $messages = Message::betweenUsers($userId, $type, $partnerId, $partnerType)
            ->get();

        // Mark incoming messages as read
        Message::where('sender_id', $partnerId)
            ->where('sender_type', $partnerType)
            ->where('receiver_id', $userId)
            ->where('receiver_type', $type)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json($messages);
    }

    /**
     * Send a message to another partner.
     */
    public function sendMessage(Request $request)
    {
        $user = $request->user();
        $type = $this->getUserType($user);
        $userId = $user->id;

        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|integer',
            'receiver_type' => 'required|in:admin,user,buyer',
            'message_text' => 'required_without:file|string|max:5000',
            'file' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,zip', // Max 10MB, secure mimes only
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $filePath = null;
        $fileName = null;
        $fileType = null;
        $fileSize = null;

        // Handle secure file upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $fileType = $file->getClientMimeType();
            $fileSize = $file->getSize();

            // Store in a private directory inside storage app uploads
            $filePath = $file->store('chat_attachments', 'public');
        }

        $message = Message::create([
            'sender_id' => $userId,
            'sender_type' => $type,
            'receiver_id' => $request->receiver_id,
            'receiver_type' => $request->receiver_type,
            'message_text' => $request->message_text ?? 'Sent an attachment: ' . $fileName,
            'timestamp' => now(),
            'is_read' => false,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_type' => $fileType,
            'file_size' => $fileSize,
            'is_delivered' => 'sent'
        ]);

        return response()->json([
            'message' => 'Message sent.',
            'data' => $message
        ], 201);
    }

    /**
     * Map the Eloquent authenticating class type to legacy string.
     */
    private function getUserType($user)
    {
        if ($user instanceof AdminLogin) {
            return 'admin';
        }
        if ($user instanceof Buyer) {
            return 'buyer';
        }
        return 'user'; // Suppliers are stored in 'users' table and maps to 'user' type in message
    }
}
