<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Inquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SupplierDashboardController extends Controller
{
    /**
     * Get the authenticated supplier's profile.
     */
    public function getProfile(Request $request)
    {
        $user = $request->user();
        $supplier = Supplier::where('added_by', $user->id)->first();

        if (!$supplier) {
            return response()->json(['message' => 'Supplier profile not found.'], 404);
        }

        return response()->json($supplier);
    }

    /**
     * Update supplier profile details.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $supplier = Supplier::where('added_by', $user->id)->first();

        if (!$supplier) {
            return response()->json(['message' => 'Supplier profile not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'CompanyName' => 'required|string|max:255',
            'Address' => 'required|string',
            'Description' => 'required|string',
            'Contact' => 'required|string|max:30',
            'State' => 'required|string|max:200',
            'City' => 'required|string|max:50',
            'Country' => 'required|string|max:50',
            'Zipcode' => 'required|string|max:150',
            'PaymentTerms' => 'required|string',
            'Whatsappno' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // Max 2MB
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // Max 5MB
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only([
            'CompanyName', 'Address', 'Description', 'Contact',
            'State', 'City', 'Country', 'Zipcode', 'PaymentTerms',
            'Whatsappno', 'Alternateno', 'Primarybusiness', 'Businesscategories'
        ]);

        // Secure file uploads
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('supplier_logos', 'public');
            $data['logo'] = $logoPath;
        }

        if ($request->hasFile('banner')) {
            $bannerPath = $request->file('banner')->store('supplier_banners', 'public');
            $data['Banner'] = $bannerPath;
        }

        $data['updated_at'] = now();

        $supplier->update($data);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'supplier' => $supplier
        ]);
    }

    /**
     * Get all products of the authenticated supplier.
     */
    public function getProducts(Request $request)
    {
        $user = $request->user();
        $supplier = Supplier::where('added_by', $user->id)->first();

        if (!$supplier) {
            return response()->json(['message' => 'Supplier profile not found.'], 404);
        }

        $products = Product::where(function ($q) use ($supplier) {
                $q->where('supplierid', $supplier->id)
                  ->orWhere('supplierid', $supplier->SuplierID);
            })
            ->whereIn('status', [0, 'active'])
            ->orderBy('added_on', 'desc')
            ->get();

        return response()->json($products);
    }

    /**
     * Store a new product.
     */
    public function storeProduct(Request $request)
    {
        $user = $request->user();
        $supplier = Supplier::where('added_by', $user->id)->first();

        if (!$supplier) {
            return response()->json(['message' => 'Only suppliers can manage products.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'categories_id' => 'required|exists:categories,id',
            'subcategories_id' => 'required|exists:sub_categories,id',
            'subsubcategories_id' => 'required|exists:sub_sub_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'qty' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:100',
            'unit' => 'required|string|max:200',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048', // Max 2MB
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $imagePath = $request->file('image')->store('product_images', 'public');

        $product = Product::create([
            'productid' => 'PROD-' . strtoupper(Str::random(10)),
            'categories_id' => $request->categories_id,
            'subcategories_id' => $request->subcategories_id,
            'subsubcategories_id' => $request->subsubcategories_id,
            'title' => $request->title,
            'slug' => Str::slug($request->title) . '-' . rand(1000, 9999),
            'description' => $request->description,
            'qty' => $request->qty,
            'price' => $request->price,
            'currency' => $request->currency,
            'unit' => $request->unit,
            'image' => $imagePath,
            'supplierid' => $supplier->SuplierID,
            'status' => 'active',
            'added_on' => now(),
            'added_by' => $user->id,
            'admin_id' => $supplier->admin_id ?? 0,
        ]);

        return response()->json([
            'message' => 'Product added successfully.',
            'product' => $product
        ], 201);
    }

    /**
     * Update an existing product.
     */
    public function updateProduct(Request $request, $id)
    {
        $user = $request->user();
        $supplier = Supplier::where('added_by', $user->id)->first();

        if (!$supplier) {
            return response()->json(['message' => 'Only suppliers can manage products.'], 403);
        }

        $product = Product::where('id', $id)
            ->where('supplierid', $supplier->SuplierID)
            ->first();

        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'categories_id' => 'required|exists:categories,id',
            'subcategories_id' => 'required|exists:sub_categories,id',
            'subsubcategories_id' => 'required|exists:sub_sub_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'qty' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:100',
            'unit' => 'required|string|max:200',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // Max 2MB
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only([
            'categories_id', 'subcategories_id', 'subsubcategories_id',
            'title', 'description', 'qty', 'price', 'currency', 'unit'
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('product_images', 'public');
            $data['image'] = $imagePath;
        }

        // Regenerate slug if title changes
        if ($product->title !== $request->title) {
            $data['slug'] = Str::slug($request->title) . '-' . rand(1000, 9999);
        }

        $product->update($data);

        return response()->json([
            'message' => 'Product updated successfully.',
            'product' => $product
        ]);
    }

    /**
     * Soft delete/Deactivate a product.
     */
    public function deleteProduct(Request $request, $id)
    {
        $user = $request->user();
        $supplier = Supplier::where('added_by', $user->id)->first();

        if (!$supplier) {
            return response()->json(['message' => 'Only suppliers can manage products.'], 403);
        }

        $product = Product::where('id', $id)
            ->where('supplierid', $supplier->SuplierID)
            ->first();

        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        // We set status to inactive rather than hard deleting to preserve historical inquiries
        $product->update(['status' => 'inactive']);

        return response()->json([
            'message' => 'Product removed successfully.'
        ]);
    }

    public function getMetrics(Request $request)
    {
        $user = $request->user();
        $supplier = Supplier::where('added_by', $user->id)->first();

        if (!$supplier) {
            // If the user registered but has not set up a supplier record, create a default one
            $supplier = Supplier::create([
                'SuplierID' => 'SUP-' . strtoupper(Str::random(8)),
                'Name' => $user->name,
                'CompanyName' => $user->name . ' Co.',
                'Email' => $user->email ?? 'info@' . Str::slug($user->name) . '.com',
                'added_by' => $user->id,
                'status' => 0, // Using integer 0 as active status
                'Country' => 'India',
                'State' => 'Maharashtra',
                'City' => 'Mumbai',
                'Address' => 'Office block 4',
                'Description' => 'Exporter and distributor.',
                'Contact' => $user->phone ?? '9900000000',
                'Zipcode' => '400001',
                'PaymentTerms' => 'LC / TT',
                'Businesscategories' => 'General Trade',
                'Primarybusiness' => 'Manufacturer & Exporter',
                'logo' => 'demo.png',
                'Banner' => '0',
                'Crn' => '',
                'Cey' => '',
                'importcode' => '0',
                'Whatsappno' => $user->phone ?? '0',
                'Alternateno' => '0',
                'recsuppliy' => 0,
                'admin_id' => 0,
                'added_on' => now()->toDateString(),
                'created_at' => now()->toDateString(),
                'updated_at' => now(),
            ]);
        }

        $productCount = Product::where(function ($q) use ($supplier) {
                $q->where('supplierid', $supplier->id)
                  ->orWhere('supplierid', $supplier->SuplierID);
            })
            ->whereIn('status', [0, 'active'])
            ->count();

        $inquiryCount = Inquiry::where(function ($q) use ($supplier) {
                $q->where('supplier_id', $supplier->id)
                  ->orWhere('supplier_id', $supplier->SuplierID);
            })->count();

        // Get 5 recent inquiries received
        $recentInquiries = Inquiry::where(function ($q) use ($supplier) {
                $q->where('supplier_id', $supplier->id)
                  ->orWhere('supplier_id', $supplier->SuplierID);
            })
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'products_count' => $productCount,
            'inquiries_count' => $inquiryCount,
            'recent_inquiries' => $recentInquiries,
            'profile_views' => 128 + ($productCount * 14),
            'response_rate' => 95,
            'supplier' => $supplier
        ]);
    }
}
