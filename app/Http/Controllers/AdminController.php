<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Slide;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\File; // Use File for additional file operations

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Laravel\Facades\Image;

use function Pest\Laravel\get;

class AdminController extends Controller
{
    // Dashboard View
    public function index()
    {
        $orders = Order::orderBy('created_at', 'DESC')->get()->take(10);
        $dashboardDatas = DB::select("
                        SELECT
                            SUM(total) AS TotalAmount,
                            SUM(IF(status = 'ordered', total, 0)) AS TotalOrderedAmount,
                            SUM(IF(status = 'delivered', total, 0)) AS TotalDeliveredAmount,
                            SUM(IF(status = 'canceled', total, 0)) AS TotalCanceledAmount,
                            COUNT(*) AS Total,
                            SUM(IF(status = 'ordered', 1, 0)) AS TotalOrdered,
                            SUM(IF(status = 'delivered', 1, 0)) AS TotalDelivered,
                            SUM(IF(status = 'canceled', 1, 0)) AS TotalCanceled
                        FROM Orders
                    ");
                    $monthlyDatas = DB::select("SELECT M.id as MonthNo, M.name as MonthName,
                    IFNULL(D.TotalAmount,0) AS TotalAmount,
                    IFNULL(D.TotalOrderedAmount,0) AS TotalOrderedAmount,
                    IFNULL(D.TotalDeliveredAmount,0) AS TotalDeliveredAmount,
                    IFNULL(D.TotalCanceledAmount,0) AS TotalCanceledAmount FROM month_names M
                    LEFT JOIN (SELECT DATE_FORMAT(created_at, '%b') AS MonthName,
                    MONTH(created_at) AS MonthNo,
                    SUM(total) AS TotalAmount,
                    SUM(IF(status='ordered',total,0)) AS TotalOrderedAmount,
                    SUM(IF(status='delivered',total,0)) AS TotalDeliveredAmount,
                    SUM(IF(status='canceled',total,0)) AS TotalCanceledAmount
                    FROM Orders WHERE YEAR(created_at)=YEAR(NOW()) GROUP BY YEAR(created_at), MONTH(created_at) , DATE_FORMAT(created_at, '%b')
                    Order By MONTH(created_at) ) D On D.MonthNo=M.id");

                $AmountM = implode(',', collect($monthlyDatas)->pluck('TotalAmount')->toArray());
                $OrderedAmountM = implode(',', collect($monthlyDatas)->pluck('TotalOrderedAmount')->toArray());
                $DeliveredAmountM = implode(',', collect($monthlyDatas)->pluck('TotalDeliveredAmount')->toArray());
                $CanceledAmountM = implode(',', collect($monthlyDatas)->pluck('TotalCanceledAmount')->toArray());

                $TotalAmount = collect($monthlyDatas)->sum('TotalAmount');
                $TotalOrderedAmount = collect($monthlyDatas)->sum('TotalOrderedAmount');
                $TotalDeliveredAmount = collect($monthlyDatas)->sum('TotalDeliveredAmount');
                $TotalCanceledAmount = collect($monthlyDatas)->sum('TotalCanceledAmount');

                return view('admin.index', compact('orders', 'dashboardDatas', 'AmountM', 'OrderedAmountM', 'DeliveredAmountM', 'CanceledAmountM', 'TotalAmount', 'TotalOrderedAmount','TotalDeliveredAmount','TotalCanceledAmount'));
    }

    // List all brands with pagination
    public function brands()
    {
        $brands = Brand::orderBy('id', 'DESC')->paginate(10);
        return view('admin.brands', compact('brands'));
    }

    // View for adding a new brand
    public function addBrand()
    {
        return view('admin.brand-add');
    }

    // Store a new brand
    public function brand_store(Request $request)
    {
        // Validate the input fields
        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|mimes:jpg,jpeg,png,gif|max:2048', // Allowing 'nullable' if image is optional
            'slug' => 'required|string|max:255|unique:brands,slug',
        ]);

        // Create a new brand instance
        $brand = new Brand();
        $brand->name = $request->name;
        $brand->slug = $request->slug;

        // Check if an image file is uploaded
        if ($request->hasFile('image')) {
            // Get the uploaded file
            $image = $request->file('image');

            // Generate a unique filename with timestamp
            $file_name = Carbon::now()->timestamp . '.' . $image->getClientOriginalExtension();

            // Define the destination path for storing the uploaded image
            $destinationPath = public_path('uploads/brands');

            // Create the directory if it doesn't exist
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            // Move the file to the destination path
            $image->move($destinationPath, $file_name);

            // Assign the filename to the brand model
            $brand->image = $file_name;
        }

        // Save the brand to the database
        $brand->save();

        // Redirect to the brands page with a success message
        return redirect()->route('admin.brands')->with('status', 'Brand has been added successfully!');
    }
    public function brand_edit($id)
    {
        $brand = Brand::find($id);
        return view('admin.brand-edit', compact('brand'));
    }
    public function brand_update(Request $request, $id)
    {
        // Validate the input fields
        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|mimes:jpg,jpeg,png,gif|max:2048', // Allowing 'nullable' if image is optional
            'slug' => 'required|string|max:255|unique:brands,slug,' . $id, // Exclude the current brand from unique check
        ]);

        // Find the brand by the given ID
        $brand = Brand::find($id);

        // Check if brand is found
        if (!$brand) {
            return redirect()->route('admin.brands')->with('error', 'Brand not found!');
        }

        // Update brand details
        $brand->name = $request->name;
        $brand->slug = $request->slug;

        // Check if an image file is uploaded
        if ($request->hasFile('image')) {
            // Get the uploaded file
            $image = $request->file('image');

            // Generate a unique filename with timestamp
            $file_name = Carbon::now()->timestamp . '.' . $image->getClientOriginalExtension();

            // Define the destination path for storing the uploaded image
            $destinationPath = public_path('uploads/brands');

            // Create the directory if it doesn't exist
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            // Delete the old image file if it exists
            if ($brand->image && file_exists($destinationPath . '/' . $brand->image)) {
                unlink($destinationPath . '/' . $brand->image);
            }

            // Move the new image file to the destination path
            $image->move($destinationPath, $file_name);

            // Assign the filename to the brand model
            $brand->image = $file_name;
        }

        // Save the brand to the database
        $brand->save();

        // Redirect to the brands page with a success message
        return redirect()->route('admin.brands')->with('status', 'Brand has been updated successfully!');
    }
    public function brand_destroy($id)
    {
        // Find the brand by the given ID
        $brand = Brand::find($id);

        // Check if the brand is found
        if (!$brand) {
            return redirect()->route('admin.brands')->with('error', 'Brand not found!');
        }

        // Delete the brand image from the file system if it exists
        $imagePath = public_path('uploads/brands/' . $brand->image);
        if ($brand->image && file_exists($imagePath)) {
            unlink($imagePath); // Deletes the image file from the server
        }

        // Delete the brand from the database
        $brand->delete();

        // Redirect to the brands page with a success message
        return redirect()->route('admin.brands')->with('status', 'Brand and its image have been deleted successfully');
    }
    public function categories()
    {
        $categories = Category::orderBy('id', 'DESC')->paginate(10);
        return view('admin.category', compact('categories'));
    }
    public function addCategory()
    {
        return view('admin.category-add');
    }
    public function category_store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|mimes:jpg,jpeg,png,gif|max:2048', // Allowing 'nullable' if image is optional
            'slug' => 'required|string|max:255|unique:categories,slug',
        ]);
        $category = new Category();
        // Create a new brand instance

        $category->name = $request->name;
        $category->slug = $request->slug;

        // Check if an image file is uploaded
        if ($request->hasFile('image')) {
            // Get the uploaded file
            $image = $request->file('image');

            // Generate a unique filename with timestamp
            $file_name = Carbon::now()->timestamp . '.' . $image->getClientOriginalExtension();

            // Define the destination path for storing the uploaded image
            $destinationPath = public_path('uploads/categories');

            // Create the directory if it doesn't exist
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            // Move the file to the destination path
            $image->move($destinationPath, $file_name);

            // Assign the filename to the brand model
            $category->image = $file_name;
        }

        // Save the brand to the database
        $category->save();

        // Redirect to the brands page with a success message
        return redirect()->route('admin.categories')->with('status', 'Category has been added successfully!');
    }
    public function category_edit($id)
    {
        $category = Category::find($id);
        return view('admin.category-edit', compact('category'));
    }
    public function category_update(Request $request, $id)
    {
        // Validate the input fields
        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|mimes:jpg,jpeg,png,gif|max:2048', // Allowing 'nullable' if image is optional
            'slug' => 'required|string|max:255|unique:categories,slug,' . $id, // Exclude the current brand from unique check
        ]);

        // Find the brand by the given ID
        $category = Category::find($id);

        // Check if brand is found
        if (!$category) {
            return redirect()->route('admin.categories')->with('error', 'Category not found!');
        }

        // Update brand details
        $category->name = $request->name;
        $category->slug = $request->slug;

        // Check if an image file is uploaded
        if ($request->hasFile('image')) {
            // Get the uploaded file
            $image = $request->file('image');

            // Generate a unique filename with timestamp
            $file_name = Carbon::now()->timestamp . '.' . $image->getClientOriginalExtension();

            // Define the destination path for storing the uploaded image
            $destinationPath = public_path('uploads/categories');

            // Create the directory if it doesn't exist
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            // Delete the old image file if it exists
            if ($category->image && file_exists($destinationPath . '/' . $category->image)) {
                unlink($destinationPath . '/' . $category->image);
            }

            // Move the new image file to the destination path
            $image->move($destinationPath, $file_name);

            // Assign the filename to the brand model
            $category->image = $file_name;
        }

        // Save the brand to the database
        $category->save();

        // Redirect to the brands page with a success message
        return redirect()->route('admin.categories')->with('status', 'Category has been updated successfully!');
    }
    public function category_destroy($id)
    {
        // Find the brand by the given ID
        $category = Category::find($id);

        // Check if the brand is found
        if (!$category) {
            return redirect()->route('admin.categories')->with('error', 'Category not found!');
        }

        // Delete the brand image from the file system if it exists
        $imagePath = public_path('uploads/brands/' . $category->image);
        if ($category->image && file_exists($imagePath)) {
            unlink($imagePath); // Deletes the image file from the server
        }

        // Delete the brand from the database
        $category->delete();

        // Redirect to the brands page with a success message
        return redirect()->route('admin.categories')->with('status', 'Category and its image have been deleted successfully');
    }

    public function products()
    {
        $products = Product::orderBy('created_at', 'DESC')->paginate(10);
        return view('admin.products', compact('products'));
    }
    public function addProduct()
    {
        $categories = Category::select('id', 'name')->orderBy('name')->get();
        $brands = Brand::select('id', 'name')->orderBy('name')->get();
        return view('admin.products-add', compact('categories', 'brands'));
    }
    public function storeProduct(Request $request)
    {
        // Validate incoming request data
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:products,slug',
            'short_description' => 'required',
            'description' => 'required',
            'regular_price' => 'required|numeric',
            'sale_price' => 'required|numeric',
            'SKU' => 'required',
            'stock_status' => 'required',
            'featured' => 'required|boolean',
            'quantity' => 'required|integer',
            'image' => 'required|mimes:jpg,jpeg,png,gif|max:2048',
            'images' => 'required',
            'images.*' => 'mimes:jpg,jpeg,png,gif|max:2048', // Validation for multiple images
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
        ]);

        // Create a new product instance
        $product = new Product();
        $product->name = $request->name;
        $product->slug = $request->slug;
        $product->short_description = $request->short_description;
        $product->description = $request->description;
        $product->regular_price = $request->regular_price;
        $product->sale_price = $request->sale_price;
        $product->SKU = $request->SKU;
        $product->stock_status = $request->stock_status;
        $product->featured = $request->featured;
        $product->quantity = $request->quantity;
        $product->category_id = $request->category_id;
        $product->brand_id = $request->brand_id;

        $current_timestamp = Carbon::now()->timestamp;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->extension();
            $this->GenerateProductThumbnailImage($image, $imageName);

            $product->image = $imageName;
        }
        $gallery_arr = array();
        $gallery_images = "";
        $counter = 1;
        if ($request->hasFile('images')) {
            $allowedfileExtion = ['jpg', 'png', 'jpeg'];
            $files = $request->file('images');
            foreach ($files as $file) {
                $gextension = $file->getClientOriginalExtension();
                $gcheck = in_array($gextension, $allowedfileExtion);
                if ($gcheck) {
                    $gfilename = $current_timestamp . '.' . $counter . $gextension;
                    $this->GenerateProductThumbnailImage($file, $gfilename);
                    array_push($gallery_arr, $gfilename);
                    $counter = $counter + 1;
                }
            }
            $gallery_images = implode(",", $gallery_arr);
        }
        $product->images = $gallery_images;
        $product->save();
        return redirect()->route('admin.products')->with('status', 'Product Added Successfully');
    }
    public function GenerateProductThumbnailImage($image, $imageName)
    {
        $destinationPath = public_path('uploads/products');
        $destinationPathThumbnails = public_path('uploads/products/thumbnails');

        $img = Image::read($image->path());

        $img->cover(540, 689, "top");
        $img->resize(540, 689, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPath . '/' . $imageName);

        $img->resize(104, 104, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPathThumbnails . '/' . $imageName);
    }
    public function editProduct($id)
    {
        $product = Product::find($id);
        $categories = Category::select('id', 'name')->orderBy('name')->get();
        $brands = Brand::select('id', 'name')->orderBy('name')->get();
        return view('admin.products-edit', compact('product', 'categories', 'brands'));
    }
    public function updateProduct(Request $request, $id)
    {
        // Validate request data
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:products,slug,' . $id, // Ensure uniqueness excluding the current product
            'short_description' => 'required',
            'description' => 'required',
            'regular_price' => 'required|numeric',
            'sale_price' => 'required|numeric',
            'SKU' => 'required',
            'stock_status' => 'required',
            'featured' => 'required|boolean',
            'quantity' => 'required|integer',
            'image' => 'required|mimes:jpg,jpeg,png,gif|max:2048',
            'images' => 'required',
            'images.*' => 'mimes:jpg,jpeg,png,gif|max:2048', // Validation for multiple images
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
        ]);

        // Find the product to update
        $product = Product::findOrFail($id);

        // Update product details
        $product->name = $request->name;
        $product->slug = $request->slug;
        $product->short_description = $request->short_description;
        $product->description = $request->description;
        $product->regular_price = $request->regular_price;
        $product->sale_price = $request->sale_price;
        $product->SKU = $request->SKU;
        $product->stock_status = $request->stock_status;
        $product->featured = $request->featured;
        $product->quantity = $request->quantity;
        $product->category_id = $request->category_id;
        $product->brand_id = $request->brand_id;

        // Handle main product image
        if ($request->hasFile('image')) {
            // Delete the old image if it exists
            if ($product->image && File::exists(public_path('uploads/products') . '/' . $product->image)) {
                File::delete(public_path('uploads/products') . '/' . $product->image);
            }
            if ($product->image && File::exists(public_path('uploads/products/thumbnails') . '/' . $product->image)) {
                File::delete(public_path('uploads/products/thumbnails') . '/' . $product->image);
            }

            // Store new image
            $image = $request->file('image');
            $imageName = time() . '.' . $image->extension();
            $this->GenerateProductThumbnailImage($image, $imageName); // Generate thumbnail

            $product->image = $imageName; // Set the new image
        }

        // Handle multiple gallery images
        $gallery_arr = [];
        if ($request->hasFile('images')) {
            // Delete old gallery images if they exist
            if ($product->images) {
                foreach (explode(',', $product->images) as $oldImage) {
                    if (File::exists(public_path('uploads/products') . '/' . $oldImage)) {
                        File::delete(public_path('uploads/products') . '/' . $oldImage);
                    }
                    if (File::exists(public_path('uploads/products/thumbnails') . '/' . $oldImage)) {
                        File::delete(public_path('uploads/products/thumbnails') . '/' . $oldImage);
                    }
                }
            }

            // Store new gallery images
            $allowedFileExtensions = ['jpg', 'png', 'jpeg'];
            $files = $request->file('images');
            foreach ($files as $file) {
                $gextension = $file->getClientOriginalExtension();
                if (in_array($gextension, $allowedFileExtensions)) {
                    $gfilename = time() . '.' . uniqid() . '.' . $gextension; // Unique filename
                    $this->GenerateProductThumbnailImage($file, $gfilename); // Generate thumbnail for gallery image
                    $gallery_arr[] = $gfilename; // Add filename to array
                }
            }
            $gallery_images = implode(",", $gallery_arr);
            // Convert array to comma-separated string
            $product->images = $gallery_images;
        }

        // Save updated product
        $product->save();

        return redirect()->route('admin.products')->with('status', 'Product Updated Successfully');
    }

    public function destroyProduct($id)
    {
        $product = Product::find($id);
        if (File::exists(public_path('uploads/products') . '/' . $product->image)) {
            File::delete(public_path('uploads/products') . '/' . $product->image);
        }
        if (File::exists(public_path('uploads/products/thumbnails') . '/' . $product->image)) {
            File::delete(public_path('uploads/products/thumbnails') . '/' . $product->image);
        }
        foreach (explode(',', $product->images) as $oldImage) {
            if (File::exists(public_path('uploads/products') . '/' . $oldImage)) {
                File::delete(public_path('uploads/products') . '/' . $oldImage);
            }
            if (File::exists(public_path('uploads/products/thumbnails') . '/' . $oldImage)) {
                File::delete(public_path('uploads/products/thumbnails') . '/' . $oldImage);
            }
        }
        $product->delete();
        return redirect()->route('admin.products')->with('status', 'Product Deleted Successfully');
    }

    public function coupons()
    {
        $coupons = Coupon::orderBy('expiry_date', 'DESC')->paginate(12);
        return view('admin.coupons', compact('coupons'));
    }
    public function coupon_add()
    {
        return view('admin.coupon-add');
    }
    public function coupon_store(Request $request)
    {
        $coupon = new Coupon();
        $request->validate([
            'code' => 'required',
            'type' => 'required',
            'value' => 'required|numeric',
            'cart_value' => 'required|numeric',
            'expiry_date' => 'required|date'


        ]);
        $coupon->code = $request->code;
        $coupon->type = $request->type;
        $coupon->value = $request->value;
        $coupon->cart_value = $request->cart_value;
        $coupon->expiry_date = $request->expiry_date;
        $coupon->save();
        return redirect()->route('admin.coupons')->with('status', 'Coupon Added Successfully');
    }
    public function coupon_edit($id)
    {
        $coupon = Coupon::find($id);
        return view('admin.coupon-edit', compact('coupon'));
    }
    public function coupon_update(Request $request, $id)
    {
        $coupon = Coupon::find($id);
        $request->validate([
            'code' => 'required',
            'type' => 'required',
            'value' => 'required|numeric',
            'cart_value' => 'required|numeric',
            'expiry_date' => 'required|date'
        ]);
        $coupon->code = $request->code;
        $coupon->type = $request->type;
        $coupon->value = $request->value;
        $coupon->cart_value = $request->cart_value;
        $coupon->expiry_date = $request->expiry_date;
        $coupon->save();
        return redirect()->route('admin.coupons')->with('status', 'Coupon Updated Successfully');
    }
    public function coupon_delete($id)
    {
        $coupon = Coupon::find($id);
        $coupon->delete();
        return redirect()->route('admin.coupons')->with('status', 'Coupon Deleted Successfully');
    }

    public function orders()
    {
        $orders = Order::orderBy('created_at', 'DESC')->paginate(10);
        return view('admin.orders', compact('orders'));
    }

    public function order_details($order_id)
    {
        $order = Order::find($order_id);

        if (!$order) {
            // Handle the case where the order is not found
            return redirect()->back()->withErrors('Order not found.');
        }

        $orderItems = OrderItem::where('order_id', $order_id)
            ->orderBy('id')
            ->paginate(12);

        $transaction = Transaction::where('order_id', $order_id)->first();

        return view('admin.order-details', compact('order', 'orderItems', 'transaction'));
    }

    public function updateOrderStatus(Request $request)
    {
        $order = Order::find($request->order_id);

        if (!$order) {
            // Handle the case where the order is not found
            return redirect()->back()->withErrors('Order not found.');
        }

        // Update order status
        $order->status = $request->order_status;

        // Set date based on status
        if ($request->order_status == 'delivered') {
            $order->delivered_date = Carbon::now();
        } elseif ($request->order_status == 'canceled') {
            $order->canceled_date = Carbon::now();
        }

        // Save the updated order
        $order->save();

        // Update transaction status if the order is delivered
        if ($request->order_status == 'delivered') {
            $transaction = Transaction::where('order_id', $request->order_id)->first();

            if ($transaction) {
                $transaction->status = 'approved';
                $transaction->save();
            } else {
                // Handle missing transaction (optional)
                return redirect()->back()->withErrors('Transaction not found for this order.');
            }
        }

        return back()->with('status', 'Status changed successfully!');
    }

    public function slides()
    {
        $slides = Slide::orderBy('id', 'DESC')->paginate(12);
        return view('admin.slides', compact('slides'));
    }

    public function addSlide()
    {
        return view('admin.slide-add');
    }
    public function slideStore(Request $request)
    {
        $slide = new Slide();
        $request->validate([
            'tagline' => 'required',
            'title' => 'required',
            'subtitle' => 'required',
            'link' => 'required',
            'status' => 'required',
            'image' => 'required|mimes:jpg,jpeg,png|max:2048'

        ]);
        $slide->tagline = $request->tagline;
        $slide->title = $request->title;
        $slide->subtitle = $request->subtitle;
        $slide->link = $request->link;
        $slide->status = $request->status;

        $image = $request->file('image');
        $file_extention = $request->file('image')->extension();
        $file_name = Carbon::now()->timestamp . '.' . $file_extention;
        $this->GenerateSlideThumbnailImage($image, $file_name);
        $slide->image = $file_name;
        $slide->save();
        return redirect()->route('admin.slides')->with('status', 'Slide added successfully');
    }

    public function GenerateSlideThumbnailImage($image, $imageName)
    {
        $destinationPath = public_path('uploads/slides/');
        $img = Image::read($image->path());
        $img->cover(400, 690, 'top');
        $img->resize(400, 690, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPath . '/' . $imageName);
    }
    public function editSlide($id)
    {
        $slide = Slide::find($id);
        return view('admin.slide-edit', compact('slide'));
    }
    public function updateSlide(Request $request, $id)
    {
        $request->validate([
            'tagline' => 'required',
            'title' => 'required',
            'subtitle' => 'required',
            'link' => 'required',
            'status' => 'required',
            'image' => 'required|mimes:jpg,jpeg,png|max:2048'

        ]);
        $slide = Slide::find($request->id);
        $slide->tagline = $request->tagline;
        $slide->title = $request->title;
        $slide->subtitle = $request->subtitle;
        $slide->link = $request->link;
        $slide->status = $request->status;

        if ($request->hasFile('image')) {
            if (File::exists(public_path('uploads/slides') . '/' . $slide->image)) {
                File::delete(public_path('uploads/slides') . '/' . $slide->image);
            }
            $image = $request->file('image');
            $file_extention = $request->file('image')->extension();
            $file_name = Carbon::now()->timestamp . '.' . $file_extention;
            $this->GenerateSlideThumbnailImage($image, $file_name);
            $slide->image = $file_name;
        }

        $slide->save();
        return redirect()->route('admin.slides')->with('status', 'Slide Updated successfully');
    }
    public function deleteSlide($id)
    {
        $slide = Slide::find($id);
        if (File::exists(public_path('uploads/slides') . '/' . $slide->image)) {
            File::delete(public_path('uploads/slides') . '/' . $slide->image);
        }
        $slide->delete();
        return redirect()->route('admin.slides')->with('status', 'Slide Deleted successfully');
    }

    public function contacts(){
        $contacts = Contact::orderBy('created_at','DESC')->paginate(10);
        return view('admin.contacts', compact('contacts'));

    }
    public function removeContact($id){
        $contact = Contact::find($id);
        $contact->delete();
        return redirect()->route('admin.contacts')->with('status', 'Contact Deleted successfully');
    }
    public function search(Request $request) {
        $query = $request->input('query');

        // Fix the query to use 'LIKE' properly and limit the results
        $results = Product::where('name', 'LIKE', "%{$query}%")
                          ->take(8)  // Fetch the first 8 results
                          ->get();

        return response()->json($results);  // Return the results as JSON
    }

}
