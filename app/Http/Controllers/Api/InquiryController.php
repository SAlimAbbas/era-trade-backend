<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Inquiie;
use App\Models\InquiryFrnt;
use App\Models\BuyingRequest;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InquiryController extends Controller
{
    /**
     * Submit an inquiry for a product/supplier.
     */
    public function submitInquiry(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'quantity' => 'nullable|integer|min:1',
            'requirement' => 'required|string',
            'product_id' => 'required|exists:product,id',
            'country_code' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Fetch product and supplier details
        $product = Product::with('supplier')->find($request->product_id);
        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $supplier = $product->supplier;

        // 1. Insert into 'inquiries' table (main table)
        $inquiry = Inquiry::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'quantity' => $request->quantity,
            'requirement' => $request->requirement,
            'product_id' => $product->id,
            'product_name' => $product->title,
            'supplier_id' => $supplier ? $supplier->id : 0,
            'supplier_name' => $supplier ? $supplier->Name : 'Unknown',
            'admin_id' => $product->admin_id ?? 0,
            'country_code' => $request->country_code ?? 0,
            'created_at' => now(),
        ]);

        // 2. Mirror details to legacy 'inquiies' table for older scripts that query it
        Inquiie::create([
            'product_id' => $product->id,
            'product_name' => $product->title,
            'supplier_id' => $supplier ? $supplier->id : 0,
            'supplier_name' => $supplier ? $supplier->Name : 'Unknown',
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'country' => $request->country ?? '',
            'company' => $request->company ?? '',
            'quantity' => $request->quantity,
            'message' => $request->requirement,
            'status' => 'pending',
            'remark' => '',
            'reply' => '',
            'created_at' => now(),
            'updated_at' => now(),
            'admin_id' => $product->admin_id ?? 0,
        ]);

        return response()->json([
            'message' => 'Inquiry submitted successfully.',
            'inquiry' => $inquiry
        ], 201);
    }

    /**
     * Submit a general front-page inquiry.
     */
    public function submitGeneralInquiry(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:200',
            'email' => 'required|email|max:200',
            'mobile' => 'required|string|max:100',
            'country' => 'required|string|max:200',
            'lookingfor' => 'required|string|max:200',
            'product' => 'required|string|max:255',
            'messages' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $inquiryFrnt = InquiryFrnt::create([
            'name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'country' => $request->country,
            'lookingfor' => $request->lookingfor,
            'product' => $request->product,
            'messages' => $request->messages,
            'added_on' => now()->toDateString(),
            'admin_id' => 0,
        ]);

        return response()->json([
            'message' => 'General inquiry submitted successfully.',
            'data' => $inquiryFrnt
        ], 201);
    }

    /**
     * Submit an RFQ / Buying Request.
     */
    public function submitRfq(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'phone' => 'required|string|max:20',
            'country_code' => 'required|integer',
            'industry' => 'nullable|string|max:100',
            'quantity' => 'required|string|max:100',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $rfq = BuyingRequest::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'country_code' => $request->country_code,
            'industry' => $request->industry,
            'quantity' => $request->quantity,
            'message' => $request->message,
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Buying request / RFQ submitted successfully.',
            'rfq' => $rfq
        ], 201);
    }

    /**
     * Get RFQs list (leads) for suppliers.
     */
    public function getRfqs(Request $request)
    {
        $user = $request->user('sanctum');
        $query = \App\Models\Lead::orderBy('Id', 'desc');

        if ($request->filled('industry') && $request->input('industry') !== 'All Categories') {
            $query->where('Type', $request->input('industry'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('Title', 'like', '%' . $search . '%')
                  ->orWhere('Description', 'like', '%' . $search . '%');
            });
        }

        $perPage = $request->input('per_page', 20);
        $leads = $query->paginate($perPage);

        $leads->getCollection()->transform(function ($lead) use ($user) {
            $revealed = false;
            if ($user) {
                $revealed = \App\Models\ContactReveal::where('user_id', $user->id)
                    ->where('revealed_id', $lead->LeadID)
                    ->where('revealed_type', 'buyer')
                    ->exists();
            }

            return [
                'id' => $lead->Id,
                'lead_id' => $lead->LeadID,
                'name' => $lead->Title,
                'email' => $revealed ? $lead->Email : '***',
                'phone' => $revealed ? $lead->Contact : '***',
                'country_code' => 0,
                'country_name' => $lead->Country,
                'industry' => $lead->Type,
                'quantity' => $lead->Qty . ' ' . $lead->Unit,
                'message' => $lead->Description ?: 'No specifications provided. Please reveal contact to discuss bulk orders.',
                'created_at' => $lead->Date ? $lead->Date->toIso8601String() : now()->toIso8601String(),
                'revealed' => $revealed,
            ];
        });

        return response()->json($leads);
    }

    /**
     * Get inquiries and RFQs submitted by the authenticated buyer or user.
     */
    public function getBuyerInquiries(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        // Fetch general RFQs (BuyingRequests)
        $rfqs = BuyingRequest::where('email', $user->email)
            ->orderBy('created_at', 'desc')
            ->get();

        // Fetch product-specific inquiries
        $inquiries = Inquiry::where('email', $user->email)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'rfqs' => $rfqs,
            'inquiries' => $inquiries
        ]);
    }
}
