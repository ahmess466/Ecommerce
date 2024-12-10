<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(){
        return view('user.index');
    }

    public function orders(){
        $orders = Order::where('user_id',Auth::user()->id)
                        ->orderBy('created_at','DESC')
                        ->paginate(10);

        return view('user.orders', compact('orders'));
    }

    public function order_details($order_id){
        $order = Order::where('user_id', Auth::user()->id)
                      ->where('id', $order_id)
                      ->first();

        if ($order) {
            $orderItems = OrderItem::where('order_id', $order->id)
                                   ->orderBy('id')
                                   ->paginate(10);

            $transaction = Transaction::where('order_id', $order->id)
                                      ->first();

            // Pass the data to the view
            return view('user.order_details', compact('order', 'orderItems', 'transaction'));
        } else {
            // Optional: Use abort(404) to show a 404 page if order is not found.
            abort(404, 'Order not found');
        }
    }
    public function order_cancel(Request $request){
        $order = Order::find($request->order_id);
        $order->status = 'canceled';
        $order->canceled_date = Carbon::now();
        $order->save();
        return back()->with('status','Order Has Beeen Canceled');
    }
}
