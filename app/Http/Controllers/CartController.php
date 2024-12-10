<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Surfsidemedia\Shoppingcart\Facades\Cart;



class CartController extends Controller
{
    public function index(){
        $items = Cart::instance('cart')->content();
        return view('cart', compact('items'));

    }
    public function add_to_cart(Request $request){
        Cart::instance('cart')->add($request->id,$request->name,$request->quantity,$request->price )->associate('App\Models\Product');
        return redirect()->back();

    }
    public function increase_cart_quantity($rowId){
        $product = Cart::instance('cart')->get($rowId);
        $qty = $product->qty + 1 ;
        Cart::instance('cart')->update($rowId, ['qty' => $qty]);
        return redirect()->back();

    }
    public function decrease_cart_quantity($rowId){
        $product = Cart::instance('cart')->get($rowId);
        $qty = $product->qty - 1 ;
        Cart::instance('cart')->update($rowId, ['qty' => $qty]);
        return redirect()->back();


    }
    public function remove_item($rowId){
        Cart::instance('cart')->remove($rowId);
        return redirect()->back();

    }
    public function empty_cart(){
        Cart::instance('cart')->destroy();
        return redirect()->back();
    }


    public function apply_coupon_code(Request $request){
        $coupon_code = $request->coupon_code;
        if(isset($coupon_code)){
            $coupon = Coupon::where('code',$coupon_code)->where('expiry_date','>=',Carbon::today())->where('cart_value','<=',Cart::instance('cart')->subtotal())->first();
            if(!$coupon){
                return redirect()->back()->with('error','Invalid coupon code !');


            }
            else{
                Session::put('coupon',[
                    'code' => $coupon->code,
                    'type' => $coupon->type ,
                    'value' => $coupon->value,
                    'cart_value' => $coupon->cart_value,

                ]);
                $this->calculateDiscount();
                return redirect()->back()->with('success','Coupon code applied successfully !');


            }


        }
        else{
            return redirect()->back()->with('error','Invalid coupon code !');
        }
    }

    public function calculateDiscount(){
        $discount = 0 ;
        if(Session::has('coupon')){
            if(Session::get('coupon')['type']=='fixed'){
                $discount = Session::get('coupon')['value'];

            }
            else{
                $discount = (Cart::instance('cart')->subtotal() * Session::get('coupon')['value'])/100;
            }
            $subtotalAfterDiscount = Cart::instance('cart')->subtotal() - $discount;
            $taxAfterDiscount = ($subtotalAfterDiscount * config('cart.tax'))/100;
            $totalAfterDiscount = $subtotalAfterDiscount + $taxAfterDiscount;


            Session::put('discounts',[
                'discount' => number_format(floatval($discount),2,'.',''),
                'subtotal' => number_format(floatval($subtotalAfterDiscount),2,'.','') ,
                'tax' => number_format(floatval($taxAfterDiscount),2,'.',''),
                'total' => number_format(floatval($totalAfterDiscount),2,'.','')
            ]);
        }

    }


    public function remove_coupon_code(){
        Session::forget('coupon');
        Session::forget('discounts');
        return  back()->with('success','Coupon Has Been Removed !');


    }

    public function checkout() {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $address = Address::where('user_id', Auth::user()->id)->where('isdefault', 1)->first();
        return view('checkout', compact('address'));
    }

    public function place_an_order(Request $request) {
        $user_id = Auth::user()->id;
        $address = Address::where('user_id', $user_id)->where('isdefault', true)->first();

        // Validate and create address if none exists
        if (!$address) {
            $request->validate([
                'name' => 'required|max:100',
                'phone' => 'required|numeric|digits:11',
                'address' => 'required',
                'city' => 'required',
                'state' => 'required',
                'landmark' => 'required',
                'locality' => 'required',
                'zip' => 'required|numeric|digits:6'
            ]);

            $address = new Address();
            $address->name = $request->name;
            $address->phone = $request->phone;
            $address->address = $request->address;
            $address->city = $request->city;
            $address->state = $request->state;
            $address->landmark = $request->landmark;
            $address->locality = $request->locality;
            $address->zip = $request->zip;
            $address->country = 'Egypt';
            $address->user_id = $user_id;
            $address->isdefault = true;
            $address->save();
        }

        // Calculate amounts for checkout
        $this->setAmountforCheckout();

        // Retrieve calculated values from the session
        $subtotal = Session::get('checkout')['subtotal'];
        $discount = Session::get('checkout')['discount'];
        $tax = Session::get('checkout')['tax'];
        $total = $subtotal + $tax - $discount; // Correct total calculation

        // Create new order
        $order = new Order();
        $order->user_id = $user_id;
        $order->subtotal = $subtotal;
        $order->discount = $discount;
        $order->tax = $tax;
        $order->total = $total; // Save the calculated total

        // Save address details to the order
        $order->name = $address->name;
        $order->phone = $address->phone;
        $order->locality = $address->locality;
        $order->address = $address->address;
        $order->city = $address->city;
        $order->state = $address->state;
        $order->landmark = $address->landmark;
        $order->zip = $address->zip;
        $order->country = $address->country;
        $order->save();

        // Save order items
        foreach (Cart::instance('cart')->content() as $item) {
            $orderItem = new OrderItem();
            $orderItem->product_id = $item->id;
            $orderItem->order_id = $order->id;
            $orderItem->price = $item->price;
            $orderItem->quantity = $item->qty;
            $orderItem->save();
        }

        // Handle payment mode
        if ($request->mode === 'card') {
            // Handle card payment logic here
        } elseif ($request->mode === 'paypal') {
            // Handle PayPal payment logic here
        } elseif ($request->mode === 'cod') {
            $transaction = new Transaction();
            $transaction->order_id = $order->id;
            $transaction->user_id = $user_id;
            $transaction->mode = $request->mode;
            $transaction->status = 'pending';
            $transaction->save();
        }

        // Clear the cart and session data
        Cart::instance('cart')->destroy();
        Session::forget('checkout');
        Session::forget('coupon');
        Session::forget('discounts');
        Session::put('order_id', $order->id);

        return redirect()->route('cart.order.confirmation');
    }

    public function setAmountforCheckout() {
        // Check if cart is empty
        if (Cart::instance('cart')->content()->count() <= 0) {
            Session::forget('checkout');
            return;
        }

        // Calculate checkout amounts
        if (Session::has('coupon')) {
            Session::put('checkout', [
                'discount' => Session::get('discounts')['discount'],
                'subtotal' => Session::get('discounts')['subtotal'],
                'tax' => Session::get('discounts')['tax'],
                'total' => Session::get('discounts')['total'],
            ]);
        } else {
            Session::put('checkout', [
                'discount' => 0,
                'subtotal' => Cart::instance('cart')->subtotal(), // Ensure this returns a correct float value
                'tax' => Cart::instance('cart')->tax(), // Ensure this returns a correct float value
                'total' => Cart::instance('cart')->total() // Ensure this returns a correct float value
            ]);
        }
    }

    public function order_confirmation() {
        if (Session::has('order_id')) {
            $order = Order::find(Session::get('order_id'));
            return view('order-confirmation', compact('order'));
        }
        return redirect()->route('cart.index');
    }













}




















