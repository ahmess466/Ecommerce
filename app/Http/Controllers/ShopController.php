<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        // Get the 'size' query parameter from the URL or set a default value of 12
        $size = $request->query('size', 12);

        // Initialize default sorting parameters
        $o_column = 'id';
        $o_order = 'DESC';

        // Get the 'order' query parameter or set a default value of -1
        $order = $request->query('order', -1);
        $f_brands = $request->query('brands', '');
        $f_categories = $request->query('categories', '');

        // Determine column and order direction based on 'order' value
        switch ((int)$order) {
            case 1:
                $o_column = 'created_at';
                $o_order = 'DESC';
                break;
            case 2:
                $o_column = 'created_at';
                $o_order = 'ASC';
                break;
            case 3:
                $o_column = 'sale_price';
                $o_order = 'ASC';
                break;
            case 4:
                $o_column = 'sale_price';
                $o_order = 'DESC';
                break;
        }

        // Fetch all brands and categories for the view
        $brands = Brand::orderBy('name', 'ASC')->get();
        $categories = Category::orderBy('name', 'ASC')->get();

        // Fetch products with the specified order and pagination size
        $products = Product::where(function($query) use ($f_brands) {
            if (!empty($f_brands)) {
                $query->whereIn('brand_id', explode(',', $f_brands));
            }
        })
        ->where(function($query) use ($f_categories) {
            if (!empty($f_categories)) {
                $query->whereIn('category_id', explode(',', $f_categories));
            }
        })
        ->orderBy($o_column, $o_order)
        ->paginate($size);

        // Return the view with the products and query parameters
        return view('shop', compact('products', 'size', 'order', 'brands', 'f_brands', 'categories', 'f_categories'));
    }




    public function productDetails($product_id)
    {
        $product = Product::findOrFail($product_id);
        $rproducts = Product::where('id', '<>', $product_id)->get()->take(8);

        return view('show', compact('product', 'rproducts'));
    }
}
