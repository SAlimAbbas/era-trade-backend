<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\SubSubCategory;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    /**
     * Get categories list hierarchical (Category -> SubCategories -> SubSubCategories).
     */
    public function getCategories()
    {
        // Cache could be added here for performance
        $categories = Category::where('status', 1)
            ->with(['subCategories' => function ($q) {
                $q->where('status', 1)->with(['subSubCategories' => function ($sq) {
                    $sq->where('status', 1);
                }]);
            }])
            ->get();

        return response()->json($categories);
    }

    /**
     * List products with advanced filters, pagination, and searching.
     */
    public function getProducts(Request $request)
    {
        $query = Product::whereIn('status', [0, 'active']);

        // Search by title or description
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        // Filter by Category
        if ($request->filled('category_id')) {
            $query->where('categories_id', $request->input('category_id'));
        }

        // Filter by Subcategory
        if ($request->filled('subcategory_id')) {
            $query->where('subcategories_id', $request->input('subcategory_id'));
        }

        // Filter by Sub-Subcategory
        if ($request->filled('sub_subcategory_id')) {
            $query->where('subsubcategories_id', $request->input('sub_subcategory_id'));
        }

        // Filter by Price range
        if ($request->filled('min_price')) {
            $query->where('price', '>=', (int) $request->input('min_price'));
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', (int) $request->input('max_price'));
        }

        // Filter by Country (through Supplier)
        if ($request->filled('country')) {
            $country = $request->input('country');
            $query->whereHas('supplier', function ($q) use ($country) {
                $q->where('Country', $country);
            });
        }

        // Pagination
        $perPage = $request->input('per_page', 20);
        $products = $query->with(['category', 'supplier'])->paginate($perPage);

        return response()->json($products);
    }

    /**
     * Get single product details by slug.
     */
    public function getProductDetail($slug)
    {
        $product = Product::where(function ($q) use ($slug) {
                $q->where('slug', $slug)
                  ->orWhere('id', $slug)
                  ->orWhere('productid', $slug);
            })
            ->whereIn('status', [0, 'active'])
            ->with(['category', 'subCategory', 'subSubCategory', 'supplier'])
            ->first();

        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        return response()->json($product);
    }

    /**
     * Search Autocomplete.
     */
    public function autocomplete(Request $request)
    {
        $term = $request->input('term');

        if (empty($term)) {
            return response()->json([]);
        }

        $products = Product::whereIn('status', [0, 'active'])
            ->where('title', 'like', '%' . $term . '%')
            ->limit(10)
            ->select('id', 'title', 'slug')
            ->get();

        return response()->json($products);
    }

    /**
     * List suppliers.
     */
    public function getSuppliers(Request $request)
    {
        $query = Supplier::whereIn('status', [0, 1, 'active']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('Name', 'like', '%' . $search . '%')
                  ->orWhere('CompanyName', 'like', '%' . $search . '%')
                  ->orWhere('Description', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('country')) {
            $country = $request->input('country');
            $query->where(function($q) use ($country) {
                $q->where('Country', $country)
                  ->orWhere('Country', 'like', '%' . $country . '%');
            });
        }

        if ($request->filled('category')) {
            $cat = $request->input('category');
            $query->where(function($q) use ($cat) {
                $q->where('Businesscategories', 'like', '%' . $cat . '%')
                  ->orWhere('Primarybusiness', 'like', '%' . $cat . '%')
                  ->orWhere('Description', 'like', '%' . $cat . '%');
            });
        }

        // Order descending: latest suppliers first
        $query->orderBy('id', 'desc');

        $perPage = $request->input('per_page', 12);
        $user = $request->user('sanctum');
        $suppliers = $query->paginate($perPage);

        $suppliers->getCollection()->transform(function ($supplier) use ($user) {
            $revealed = false;
            if ($user) {
                $revealed = \App\Models\ContactReveal::where('user_id', $user->id)
                    ->where('revealed_id', $supplier->SuplierID)
                    ->where('revealed_type', 'supplier')
                    ->exists();
            }
            if (!$revealed) {
                $supplier->Contact = '***';
                $supplier->Email = '***';
            }
            $supplier->revealed = $revealed;
            return $supplier;
        });

        return response()->json($suppliers);
    }

    /**
     * Get distinct supplier countries with counts.
     */
    public function getSupplierCountries()
    {
        $countries = Supplier::selectRaw('Country, COUNT(*) as count')
            ->whereIn('status', [0, 1, 'active'])
            ->whereNotNull('Country')
            ->where('Country', '<>', '')
            ->groupBy('Country')
            ->orderBy('count', 'desc')
            ->get();

        return response()->json($countries);
    }

    /**
     * Get single supplier details and their active products.
     */
    public function getSupplierDetail(Request $request, $id)
    {
        $supplier = Supplier::find($id);

        if (!$supplier) {
            $supplier = Supplier::where('SuplierID', $id)->first();
        }

        if (!$supplier || $supplier->status === 'inactive') {
            return response()->json(['message' => 'Supplier not found.'], 404);
        }

        $user = $request->user('sanctum');
        $revealed = false;
        if ($user) {
            $revealed = \App\Models\ContactReveal::where('user_id', $user->id)
                ->where('revealed_id', $supplier->SuplierID)
                ->where('revealed_type', 'supplier')
                ->exists();
        }

        if (!$revealed) {
            $supplier->Contact = '***';
            $supplier->Email = '***';
        }
        $supplier->revealed = $revealed;

        // Get products of this supplier
        $products = Product::where(function ($q) use ($supplier) {
                $q->where('supplierid', $supplier->id)
                  ->orWhere('supplierid', $supplier->SuplierID);
            })
            ->whereIn('status', [0, 'active'])
            ->get();

        return response()->json([
            'supplier' => $supplier,
            'products' => $products
        ]);
    }

    /**
     * Get dynamic popular products/keywords directly from products table.
     * Optimized query with select & limit to prevent server load.
     */
    public function getPopularProducts()
    {
        $popularTitles = Product::whereIn('status', [0, 'active'])
            ->select('id', 'title', 'slug')
            ->orderBy('id', 'desc')
            ->limit(180)
            ->get()
            ->pluck('title')
            ->filter()
            ->unique()
            ->values();

        // Chunk into groups of 60 items per page
        $pages = $popularTitles->chunk(60)->values();

        return response()->json($pages);
    }
}
