<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;

class AdminController extends Controller
{
    /**
     * Get high-level dynamic overview metrics for the Admin Dashboard.
     */
    public function getMetrics()
    {
        try {
            $totalLeads = Lead::count();
            $totalProducts = Product::count();
            $totalSuppliers = Supplier::count();
            $totalUsers = User::count();
            $totalBlogs = DB::table('blog')->count();
            $totalCategories = Category::count();
            $distinctCountries = Lead::whereNotNull('Country')->where('Country', '!=', '')->distinct()->count('Country');

            // Recent leads
            $recentLeads = Lead::orderBy('Id', 'desc')->take(10)->get();

            // Leads added today and this month
            $today = date('Y-m-d');
            $thisMonth = date('Y-m');
            $lastMonth = date('Y-m', strtotime('-1 month'));

            $leadsToday = Lead::whereDate('Date', $today)->count();
            $leadsThisMonth = Lead::where('Date', 'like', "$thisMonth%")->count();
            $leadsLastMonth = Lead::where('Date', 'like', "$lastMonth%")->count();

            // Dynamic weekly chart (last 7 days from DB)
            $weeklyChart = [];
            for ($i = 6; $i >= 0; $i--) {
                $d = date('Y-m-d', strtotime("-$i days"));
                $cnt = Lead::whereDate('Date', $d)->count();
                $weeklyChart[] = [
                    'name' => date('D', strtotime($d)),
                    'date' => $d,
                    'value' => $cnt,
                ];
            }
            $maxWVal = max(array_column($weeklyChart, 'value'));
            foreach ($weeklyChart as &$wItem) {
                if ($maxWVal > 0 && $wItem['value'] === $maxWVal) {
                    $wItem['highlight'] = true;
                }
            }

            // Dynamic monthly chart (last 4 months from DB)
            $monthlyChart = [];
            for ($m = 3; $m >= 0; $m--) {
                $mStr = date('Y-m', strtotime("-$m month"));
                $mName = date('M', strtotime("-$m month"));
                $cnt = Lead::where('Date', 'like', "$mStr%")->count();
                $monthlyChart[] = [
                    'name' => $mName,
                    'value' => $cnt,
                ];
            }
            $maxMVal = max(array_column($monthlyChart, 'value'));
            foreach ($monthlyChart as &$mItem) {
                if ($maxMVal > 0 && $mItem['value'] === $maxMVal) {
                    $mItem['highlight'] = true;
                }
            }

            // Dynamic quarterly chart (Q1, Q2, Q3, Q4 from DB)
            $quarterlyChart = [
                ['name' => 'Q1', 'value' => Lead::whereRaw("QUARTER(Date) = 1 AND YEAR(Date) = YEAR(CURDATE())")->count()],
                ['name' => 'Q2', 'value' => Lead::whereRaw("QUARTER(Date) = 2 AND YEAR(Date) = YEAR(CURDATE())")->count()],
                ['name' => 'Q3', 'value' => Lead::whereRaw("QUARTER(Date) = 3 AND YEAR(Date) = YEAR(CURDATE())")->count()],
                ['name' => 'Q4', 'value' => Lead::whereRaw("QUARTER(Date) = 4 AND YEAR(Date) = YEAR(CURDATE())")->count()],
            ];
            $maxQVal = max(array_column($quarterlyChart, 'value'));
            foreach ($quarterlyChart as &$qItem) {
                if ($maxQVal > 0 && $qItem['value'] === $maxQVal) {
                    $qItem['highlight'] = true;
                }
            }

            // Calculate lead delta vs last month
            $leadDiff = $leadsThisMonth - $leadsLastMonth;
            $leadDelta = ($leadDiff >= 0 ? "+$leadDiff" : "$leadDiff") . " vs last month";

            return response()->json([
                'success' => true,
                'data' => [
                    'counts' => [
                        'leads' => $totalLeads,
                        'products' => $totalProducts,
                        'suppliers' => $totalSuppliers,
                        'users' => $totalUsers,
                        'blogs' => $totalBlogs,
                        'categories' => $totalCategories,
                        'countries' => $distinctCountries,
                        'leads_today' => $leadsToday,
                        'leads_this_month' => $leadsThisMonth,
                        'leads_last_month' => $leadsLastMonth,
                    ],
                    'deltas' => [
                        'leads' => $leadDelta,
                        'leads_type' => $leadDiff >= 0 ? 'positive' : 'negative',
                        'suppliers' => '+49.9k verified',
                        'products' => '+4.0k catalogued',
                    ],
                    'charts' => [
                        'Weekly' => $weeklyChart,
                        'Monthly' => $monthlyChart,
                        'Quarterly' => $quarterlyChart,
                    ],
                    'platform_summary' => [
                        'total_listings' => $totalLeads + $totalProducts + $totalSuppliers,
                        'total_categories' => $totalCategories,
                        'total_articles' => $totalBlogs,
                        'active_countries' => $distinctCountries,
                    ],
                    'recent_leads' => $recentLeads,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard metrics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dynamic options (categories, subcategories, countries, units) for the Lead Upload form.
     */
    public function getLeadFormData()
    {
        try {
            $categories = Category::with(['subCategories' => function ($q) {
                $q->select('id', 'categories_id', 'sub_categories');
            }])
            ->select('id', 'categories', 'slug')
            ->orderBy('categories', 'asc')
            ->get();

            // Fetch countries
            $countries = DB::table('country')
                ->select('countryname', 'code', 'country_code')
                ->orderBy('countryname', 'asc')
                ->get();

            $commonUnits = [
                'Pieces',
                'Boxes',
                'Cartons',
                'Sets',
                'Tons',
                'Metric Tons (MT)',
                'Kilograms (Kg)',
                'Grams (g)',
                'Liters (L)',
                'Barrels',
                'Containers (20ft)',
                'Containers (40ft)',
                'Bags',
                'Bales',
                'Packs',
                'Rolls',
                'Meters',
                'Square Meters',
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'categories' => $categories,
                    'countries' => $countries,
                    'units' => $commonUnits,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load form metadata: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload a single lead into the leads table.
     */
    public function storeSingleLead(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'Title' => 'required|string|max:500',
            'Qty' => 'required|string|max:255',
            'Unit' => 'required|string|max:100',
            'Country' => 'required|string|max:100',
            'Name' => 'nullable|string|max:100',
            'Email' => 'nullable|email|max:100',
            'Contact' => 'nullable|string|max:30',
            'CompanyName' => 'nullable|string|max:255',
            'Description' => 'nullable|string',
            'PaymentTerms' => 'nullable|string|max:255',
            'Address' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $adminId = ($user && isset($user->id)) ? $user->id : 33;

            // Generate unique LeadID
            $leadId = 'EWL' . date('ymd') . strtoupper(substr(uniqid(), -4));

            $lead = Lead::create([
                'LeadID' => $leadId,
                'Type' => 'Buyer',
                'Date' => now(),
                'Title' => trim($request->input('Title')),
                'Qty' => trim($request->input('Qty')),
                'Unit' => trim($request->input('Unit')),
                'Country' => trim($request->input('Country')),
                'Name' => trim($request->input('Name', 'Verified Buyer')),
                'Email' => trim($request->input('Email', '')),
                'Contact' => trim($request->input('Contact', '')),
                'CompanyName' => trim($request->input('CompanyName', '')),
                'Description' => trim($request->input('Description', '')),
                'PaymentTerms' => trim($request->input('PaymentTerms', 'LC / TT / DP')),
                'Address' => trim($request->input('Address', '')),
                'admin_id' => $adminId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lead uploaded successfully.',
                'data' => $lead
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save lead: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk upload leads via CSV file or JSON array.
     */
    public function bulkUploadLeads(Request $request)
    {
        $user = $request->user();
        $adminId = ($user && isset($user->id)) ? $user->id : 33;

        // Check if file upload (CSV)
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            if (!$file->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid file uploaded.'
                ], 422);
            }

            $path = $file->getRealPath();
            $handle = fopen($path, 'r');
            if (!$handle) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to read the uploaded CSV file.'
                ], 422);
            }

            // Read header row
            $header = fgetcsv($handle);
            if (!$header) {
                fclose($handle);
                return response()->json([
                    'success' => false,
                    'message' => 'The CSV file is empty.'
                ], 422);
            }

            // Normalize header column names (lowercase, trimmed, strip BOM)
            $cleanedHeaders = [];
            foreach ($header as $col) {
                $cleaned = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', trim($col));
                $cleanedHeaders[] = strtolower($cleaned);
            }

            $inserted = 0;
            $skipped = 0;
            $errors = [];
            $failedRows = [];
            $batch = [];
            $rowNum = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                // Skip completely empty row
                if (empty(array_filter($row))) {
                    continue;
                }

                $data = [];
                foreach ($cleanedHeaders as $idx => $key) {
                    $data[$key] = isset($row[$idx]) ? trim($row[$idx]) : '';
                }

                // Match expected fields with fallbacks
                $title = $data['title'] ?? $data['requirement'] ?? $data['product'] ?? '';
                $qty = $data['qty'] ?? $data['quantity'] ?? '100';
                $unit = $data['unit'] ?? 'Pieces';
                $country = $data['country'] ?? 'India';
                $name = $data['name'] ?? $data['buyer_name'] ?? 'Verified Buyer';
                $email = $data['email'] ?? '';
                $contact = $data['contact'] ?? $data['phone'] ?? $data['whatsapp'] ?? '';
                $company = $data['company'] ?? $data['companyname'] ?? '';
                $desc = $data['description'] ?? $data['details'] ?? $data['specification'] ?? '';
                $payment = $data['paymentterms'] ?? $data['payment_terms'] ?? 'LC / TT';
                $address = $data['address'] ?? $data['location'] ?? '';

                if (empty($title)) {
                    $skipped++;
                    $reason = "Missing Title: The requirement or product title is blank.";
                    $errors[] = "Row #$rowNum: $reason";
                    $failedRows[] = [
                        'row_number' => $rowNum,
                        'reason' => $reason,
                        'title' => '',
                        'qty' => $qty,
                        'unit' => $unit,
                        'country' => $country,
                        'name' => $name,
                        'email' => $email,
                        'contact' => $contact,
                        'company' => $company,
                        'description' => $desc,
                        'payment_terms' => $payment,
                        'address' => $address,
                    ];
                    continue;
                }

                $leadId = 'EWL' . date('ymd') . strtoupper(substr(uniqid(), -4));

                $batch[] = [
                    'LeadID' => $leadId,
                    'Type' => 'Buyer',
                    'Date' => now(),
                    'Title' => $title,
                    'Qty' => $qty,
                    'Unit' => $unit,
                    'Country' => $country,
                    'Name' => $name,
                    'Email' => $email,
                    'Contact' => $contact,
                    'CompanyName' => $company,
                    'Description' => $desc,
                    'PaymentTerms' => $payment,
                    'Address' => $address,
                    'admin_id' => $adminId,
                    '_rowNum' => $rowNum,
                ];

                // Insert in batches of 100 for maximum performance
                if (count($batch) >= 100) {
                    $this->insertLeadBatch($batch, $inserted, $skipped, $errors, $failedRows);
                    $batch = [];
                }
            }

            fclose($handle);

            // Insert remaining batch
            if (count($batch) > 0) {
                $this->insertLeadBatch($batch, $inserted, $skipped, $errors, $failedRows);
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully imported $inserted leads." . ($skipped > 0 ? " ($skipped failed/skipped)" : ""),
                'data' => [
                    'total_processed' => $inserted + $skipped,
                    'imported' => $inserted,
                    'skipped' => $skipped,
                    'errors' => $errors,
                    'failed_rows' => $failedRows,
                ]
            ]);
        }

        // Check if JSON array was sent
        if ($request->has('leads') && is_array($request->input('leads'))) {
            $leadsData = $request->input('leads');
            $inserted = 0;
            $skipped = 0;
            $batch = [];

            foreach ($leadsData as $idx => $item) {
                $title = trim($item['Title'] ?? $item['title'] ?? '');
                if (empty($title)) {
                    $skipped++;
                    continue;
                }

                $batch[] = [
                    'LeadID' => 'EWL' . date('ymd') . strtoupper(substr(uniqid(), -4)),
                    'Type' => 'Buyer',
                    'Date' => now(),
                    'Title' => $title,
                    'Qty' => trim($item['Qty'] ?? $item['qty'] ?? '100'),
                    'Unit' => trim($item['Unit'] ?? $item['unit'] ?? 'Pieces'),
                    'Country' => trim($item['Country'] ?? $item['country'] ?? 'India'),
                    'Name' => trim($item['Name'] ?? $item['name'] ?? 'Verified Buyer'),
                    'Email' => trim($item['Email'] ?? $item['email'] ?? ''),
                    'Contact' => trim($item['Contact'] ?? $item['contact'] ?? ''),
                    'CompanyName' => trim($item['CompanyName'] ?? $item['company'] ?? ''),
                    'Description' => trim($item['Description'] ?? $item['description'] ?? ''),
                    'PaymentTerms' => trim($item['PaymentTerms'] ?? $item['payment_terms'] ?? 'LC / TT'),
                    'Address' => trim($item['Address'] ?? $item['address'] ?? ''),
                    'admin_id' => $adminId,
                ];

                if (count($batch) >= 100) {
                    Lead::insert($batch);
                    $inserted += count($batch);
                    $batch = [];
                }
            }

            if (count($batch) > 0) {
                Lead::insert($batch);
                $inserted += count($batch);
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully imported $inserted leads." . ($skipped > 0 ? " ($skipped skipped)" : ""),
                'data' => [
                    'imported' => $inserted,
                    'skipped' => $skipped,
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Please provide either a CSV file or an array of leads.'
        ], 422);
    }

    /**
     * Safely insert a batch of leads, falling back to row-by-row on errors to preserve valid records and log failures.
     */
    private function insertLeadBatch(array $batch, int &$inserted, int &$skipped, array &$errors, array &$failedRows)
    {
        $cleanBatch = array_map(function ($item) {
            unset($item['_rowNum']);
            return $item;
        }, $batch);

        try {
            Lead::insert($cleanBatch);
            $inserted += count($cleanBatch);
        } catch (\Exception $e) {
            // Batch failed; attempt row-by-row insertion to identify and log exact failures
            foreach ($batch as $lead) {
                $rowNum = $lead['_rowNum'] ?? 'Unknown';
                $title = $lead['Title'] ?? 'Untitled';
                $toInsert = $lead;
                unset($toInsert['_rowNum']);

                try {
                    Lead::insert($toInsert);
                    $inserted++;
                } catch (\Exception $subEx) {
                    $skipped++;
                    $errMsg = $subEx->getMessage();
                    if (str_contains($errMsg, 'Data too long')) {
                        $reason = "Data field too long for database column";
                    } elseif (str_contains($errMsg, 'Incorrect string value')) {
                        $reason = "Encoding error: Contains characters not supported in UTF-8";
                    } else {
                        $reason = "Database rejection: " . substr($errMsg, 0, 120);
                    }

                    $errors[] = "Row #$rowNum ($title): $reason";
                    $failedRows[] = [
                        'row_number' => $rowNum,
                        'reason' => $reason,
                        'title' => $lead['Title'] ?? '',
                        'qty' => $lead['Qty'] ?? '',
                        'unit' => $lead['Unit'] ?? '',
                        'country' => $lead['Country'] ?? '',
                        'name' => $lead['Name'] ?? '',
                        'email' => $lead['Email'] ?? '',
                        'contact' => $lead['Contact'] ?? '',
                        'company' => $lead['CompanyName'] ?? '',
                        'description' => $lead['Description'] ?? '',
                        'payment_terms' => $lead['PaymentTerms'] ?? '',
                        'address' => $lead['Address'] ?? '',
                    ];
                }
            }
        }
    }

    /**
     * Generate & stream a downloadable sample CSV template for bulk upload.
     */
    public function downloadSampleCsv()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="leads_sample_template.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $columns = [
            'Title',
            'Qty',
            'Unit',
            'Country',
            'Name',
            'Email',
            'Contact',
            'CompanyName',
            'Description',
            'PaymentTerms',
            'Address'
        ];

        $sampleRows = [
            [
                'Basmati Rice 1121 Steam',
                '500',
                'Tons',
                'Saudi Arabia',
                'Al-Rashid Trading Co',
                'purchase@alrashid.sa',
                '+966 50 123 4567',
                'Al-Rashid Food Imports',
                'Require high quality long grain Basmati 1121 steam rice in 25kg PP bags. CIF Dammam port.',
                '100% LC at sight',
                'Riyadh, Saudi Arabia'
            ],
            [
                'Cotton T-Shirts Blank Wholesale',
                '10000',
                'Pieces',
                'United States',
                'Michael Johnson',
                'mjohnson@apparelco.com',
                '+1 415 555 2671',
                'Johnson Apparel Brands LLC',
                '100% combed cotton blank round neck t-shirts (180 GSM), black and white colors assorted.',
                '30% Advance, 70% against BL',
                'Los Angeles, CA, USA'
            ],
            [
                'Industrial Copper Wire Scrap Millberry',
                '100',
                'Metric Tons (MT)',
                'Germany',
                'Hans Mueller',
                'procurement@mueller-metal.de',
                '+49 170 987 6543',
                'Mueller Metal GMBH',
                'Clean copper wire scrap millberry 99.9% purity. Delivery to Hamburg port.',
                'LC / TT',
                'Frankfurt, Germany'
            ]
        ];

        $callback = function () use ($columns, $sampleRows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($sampleRows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get paginated blog articles for admin management.
     */
    public function getAdminBlogs(Request $request)
    {
        try {
            $query = Blog::query();

            if ($request->has('search') && !empty($request->input('search'))) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('heading', 'like', "%{$search}%")
                      ->orWhere('keyword', 'like', "%{$search}%")
                      ->orWhere('slug', 'like', "%{$search}%");
                });
            }

            $perPage = $request->input('per_page', 10);
            $blogs = $query->orderBy('date', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $blogs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load blogs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single blog details for editor.
     */
    public function getAdminBlog($id)
    {
        try {
            $blog = Blog::find($id);
            if (!$blog) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blog article not found.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $blog
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load blog: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created blog post.
     */
    public function storeBlog(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'heading' => 'required|string|max:255',
            'description' => 'required|string',
            'slug' => 'nullable|string|max:255|unique:blog,slug',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'keyword' => 'nullable|string',
            'date' => 'nullable|date',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $adminId = ($user && isset($user->id)) ? $user->id : 33;

            // Generate slug if omitted
            $heading = trim($request->input('heading'));
            $slug = trim($request->input('slug', ''));
            if (empty($slug)) {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $heading), '-'));
                // Ensure uniqueness
                $originalSlug = $slug;
                $counter = 1;
                while (Blog::where('slug', $slug)->exists()) {
                    $slug = "{$originalSlug}-{$counter}";
                    $counter++;
                }
            }

            // Handle image upload
            $imageName = '';
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $destinationPath = public_path('uploads/blogs');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                $imageName = 'blog_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move($destinationPath, $imageName);
            }

            $blog = Blog::create([
                'heading' => $heading,
                'description' => $request->input('description'),
                'link' => $request->input('link', ''),
                'image' => $imageName,
                'meta_title' => $request->input('meta_title') ?: $heading,
                'keyword' => $request->input('keyword', ''),
                'meta_description' => $request->input('meta_description', ''),
                'date' => $request->input('date') ?: now(),
                'slug' => $slug,
                'admin_id' => $adminId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Blog article published successfully.',
                'data' => $blog
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to publish blog article: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing blog post.
     */
    public function updateBlog(Request $request, $id)
    {
        $blog = Blog::find($id);
        if (!$blog) {
            return response()->json([
                'success' => false,
                'message' => 'Blog article not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'heading' => 'required|string|max:255',
            'description' => 'required|string',
            'slug' => 'nullable|string|max:255|unique:blog,slug,' . $id,
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'keyword' => 'nullable|string',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = [
                'heading' => trim($request->input('heading')),
                'description' => $request->input('description'),
                'meta_title' => $request->input('meta_title', $blog->meta_title),
                'keyword' => $request->input('keyword', $blog->keyword),
                'meta_description' => $request->input('meta_description', $blog->meta_description),
            ];

            if ($request->filled('slug')) {
                $data['slug'] = trim($request->input('slug'));
            }

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $destinationPath = public_path('uploads/blogs');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                $imageName = 'blog_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move($destinationPath, $imageName);
                $data['image'] = $imageName;
            }

            $blog->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Blog article updated successfully.',
                'data' => $blog
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update blog: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a blog post.
     */
    public function deleteBlog($id)
    {
        try {
            $blog = Blog::find($id);
            if (!$blog) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blog article not found.'
                ], 404);
            }

            $blog->delete();

            return response()->json([
                'success' => true,
                'message' => 'Blog article deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete blog: ' . $e->getMessage()
            ], 500);
        }
    }
}

