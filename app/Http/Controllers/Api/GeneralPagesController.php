<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Complaint;
use App\Models\Review;
use App\Models\WtaApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GeneralPagesController extends Controller
{
    /**
     * Get paginated list of blog articles.
     */
    public function getBlogs(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $blogs = Blog::orderBy('date', 'desc')->paginate($perPage);

        return response()->json($blogs);
    }

    /**
     * Get single blog post by slug.
     */
    public function getBlogDetail($slug)
    {
        $blog = Blog::where('slug', $slug)->first();

        if (!$blog) {
            return response()->json(['message' => 'Blog article not found.'], 404);
        }

        return response()->json($blog);
    }

    /**
     * Submit a customer complaint.
     */
    public function submitComplaint(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'product' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'phone' => 'required|string|max:15',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $complaint = Complaint::create([
            'name' => $request->name,
            'product' => $request->product,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'phone' => $request->phone,
            'message' => $request->message,
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Complaint submitted successfully.',
            'data' => $complaint
        ], 201);
    }

    /**
     * Submit a product or service review.
     */
    public function submitReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reviewer_name' => 'required|string|max:255',
            'reviewer_email' => 'required|email|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'review_text' => 'required|string',
            'title' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Fetch IP and User Agent securely for audit trail
        $review = Review::create([
            'reviewer_id' => $request->user() ? $request->user()->id : null,
            'reviewer_name' => $request->reviewer_name,
            'reviewer_email' => $request->reviewer_email,
            'rating' => $request->rating,
            'review_text' => $request->review_text,
            'title' => $request->title,
            'verified_purchase' => $request->input('verified_purchase', false),
            'helpful_count' => 0,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now()
        ]);

        return response()->json([
            'message' => 'Review submitted successfully.',
            'data' => $review
        ], 201);
    }

    /**
     * Apply for World Trade Assurance (WTA).
     */
    public function submitWtaApplication(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'address' => 'required|string',
            'country' => 'required|string|max:100',
            'contact_number' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'registration_no' => 'required|string|max:100',
            'iec_code' => 'nullable|string|max:100',
            'reg_year' => 'required|integer|min:1900|max:' . date('Y'),
            'product_category' => 'required|string|max:255',
            'certificates' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // Max 5MB cert file
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $certificatesPath = null;
        if ($request->hasFile('certificates')) {
            $certificatesPath = $request->file('certificates')->store('wta_certificates', 'public');
        }

        $application = WtaApplication::create([
            'company_name' => $request->company_name,
            'owner_name' => $request->owner_name,
            'address' => $request->address,
            'country' => $request->country,
            'contact_number' => $request->contact_number,
            'email' => $request->email,
            'registration_no' => $request->registration_no,
            'iec_code' => $request->iec_code,
            'reg_year' => $request->reg_year,
            'product_category' => $request->product_category,
            'certificates' => $certificatesPath,
            'status' => 'pending',
            'admin_id' => 0,
        ]);

        return response()->json([
            'message' => 'World Trade Assurance application submitted successfully.',
            'data' => $application
        ], 201);
    }
}
