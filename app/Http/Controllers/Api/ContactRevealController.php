<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactReveal;
use App\Models\Supplier;
use App\Models\Lead;
use App\Models\Buyer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactRevealController extends Controller
{
    /**
     * Reveal the contact details of a buyer or supplier based on plan daily limits.
     */
    public function reveal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'revealed_id' => 'required|string',
            'revealed_type' => 'required|in:supplier,buyer'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'You must be logged in to reveal contact details.'], 401);
        }

        $revealedId = $request->input('revealed_id');
        $revealedType = $request->input('revealed_type');
        $today = now()->toDateString();

        // 1. Get the current user's role/type and active plan
        // Let's determine plan
        $plan = 'Basic';
        $userType = 'buyer';

        // Check if user is in users (suppliers) or buyers
        $supplier = Supplier::where('added_by', $user->id)->first();
        if ($supplier) {
            $plan = $supplier->plan ?: 'Basic';
            $userType = 'supplier';
        } else {
            // Check if user has plan column
            $plan = $user->plan ?: 'Basic';
            // Check type
            if ($user->types === 'user' || $user->types === 'supplier') {
                $userType = 'supplier';
            }
        }

        // 2. Normalize plan name and set limits
        $planLower = strtolower($plan);
        $limit = 5; // Default for Basic or none
        if ($planLower === 'premium') {
            $limit = 8;
        } elseif ($planLower === 'advance' || $planLower === 'advanced') {
            $limit = 15;
        } elseif ($planLower === 'vip') {
            $limit = 20;
        }

        // 3. Check if already revealed today
        $existing = ContactReveal::where('user_id', $user->id)
            ->where('revealed_id', $revealedId)
            ->where('revealed_type', $revealedType)
            ->first();

        if ($existing) {
            return $this->getContactResponse($revealedId, $revealedType);
        }

        // 4. Check daily reveals count
        $revealsToday = ContactReveal::where('user_id', $user->id)
            ->where('reveal_date', $today)
            ->count();

        if ($revealsToday >= $limit) {
            return response()->json([
                'message' => "Daily reveal limit of {$limit} reached for your {$plan} plan. Upgrade to view more contacts."
            ], 403);
        }

        // 5. Create reveal record
        ContactReveal::create([
            'user_id' => $user->id,
            'user_type' => $userType,
            'revealed_id' => $revealedId,
            'revealed_type' => $revealedType,
            'reveal_date' => $today
        ]);

        return $this->getContactResponse($revealedId, $revealedType);
    }

    /**
     * Retrieve target contact info.
     */
    private function getContactResponse($revealedId, $revealedType)
    {
        if ($revealedType === 'supplier') {
            $supplier = Supplier::where('SuplierID', $revealedId)->first();
            if (!$supplier) {
                $supplier = Supplier::find($revealedId);
            }
            if (!$supplier) {
                return response()->json(['message' => 'Supplier not found.'], 404);
            }
            return response()->json([
                'revealed' => true,
                'email' => $supplier->Email ?: 'N/A',
                'phone' => $supplier->Contact ?: 'N/A',
            ]);
        } else {
            // Revealed type is buyer (from leads)
            $lead = Lead::where('LeadID', $revealedId)->first();
            if (!$lead) {
                $lead = Lead::find($revealedId);
            }
            if (!$lead) {
                // Check if it exists in BuyingRequest
                $lead = \App\Models\BuyingRequest::find($revealedId);
            }
            if (!$lead) {
                return response()->json(['message' => 'Lead/Buyer not found.'], 404);
            }
            return response()->json([
                'revealed' => true,
                'email' => $lead->Email ?: 'N/A',
                'phone' => $lead->Contact ?: ($lead->phone ?: 'N/A'),
            ]);
        }
    }
}
