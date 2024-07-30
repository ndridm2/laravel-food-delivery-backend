<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Faker\Provider\ar_EG\Payment;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    // User: create new order
    public function createOrder(Request $request)
    {
        $request->validate([
            'order_items' => 'required|array',
            'order_items.*.product_id' => 'required|integer|exists:products,id',
            'order_items.*.quantity' => 'required|integer|min:1',
            'restaurant_id' => 'required|integer|exists:users,id',
            'shipping_cost' => 'required|integer',
        ]);

        $totalPrice = 0;
        foreach ($request->order_items as $item) {
            $product = Product::find($item['product_id']);
            $totalPrice += $product->price * $item['quantity'];
        }

        $totalBill = $totalPrice + $request->shipping_cost;

        $user = $request->user();
        $data = $request->all();
        $data['user_id'] = $user->id;
        $shippingAddress = $user->id;
        $data['shipping_address'] = $shippingAddress;
        $shippingLatLong = $user->latlong;
        $data['shipping_latlong'] = $shippingLatLong;
        $data['status'] = 'pending';
        $data['total_price'] = $totalPrice;
        $data['total_bill'] = $totalBill;

        $order = Order::create($data);

        foreach ($request->order_items as $item) {
            $product = Product::find($item['product_id']);
            $orderItem = new OrderItem([
                'product_id' => $product->id,
                'order_id' => $order->id,
                'quantity' => $item['quantity'],
                'price' => $product->price,
            ]);
            $order->orderItems()->save($orderItem);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Order created successfully',
            'data' => $order,
        ]);
    }

    // update purchase status
    public function updatePurchaseStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:pending,processing,completed,cancelled',
        ]);
        $order = Order::find($id);
        $order->status = $request->status;
        $order->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Order status updated successfully',
            'data' => $order,
        ]);
    }

    //order history
    public function orderHistory(Request $request)
    {
        $user = $request->user();
        $orders = Order::where('user_id', $user->id)->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Get all order history',
            'data' => $orders
        ]);
    }

    // cancel order
    public function cancelOrder(Request $request, $id)
    {
        $order = Order::find($id);
        $order->status = 'cancelled';
        $order->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Order cancelled successfully',
            'data' => $order,
        ]);
    }

    // get orders by status for restaurant
    public function getOrdersByStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|string|in:pending,processing,completed,cancelled',
        ]);

        $user = $request->user();
        $orders = Order::where('restaurant_id', $user->id)
            ->where('status', $request->status)
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Get all order history',
            'data' => $orders,
        ]);
    }

    // update order status for restaurant
    public function updateOrderStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:pending,processing,completed,cancelled,ready_for_delivery,prepared',
        ]);

        $order = Order::find($id);
        $order->status = $request->status;
        $order->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Order status updated successfully',
            'data' => $order,
        ]);
    }

    // get orders by status for driver
    public function getOrdersByStatusForDriver(Request $request)
    {
        $request->validate([
            'status' => 'required|string|in:pending,processing,completed,cancelled,ready_for_delivery,prepared',
        ]);

        $user = $request->user();
        $orders = Order::where('driver_id', $user->id)
            ->where('status', $request->status)
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Get all order status',
            'data' => $orders,
        ]);
    }

    // get order status ready for delivery
    public function getOrdersReadyForDelivery(Request $request)
    {
        // $user = $request->user();
        $orders = Order::with('restaurant')
            ->where('status', 'ready_for_delivery')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Get all order status ready',
            'data' => $orders,
        ]);
    }

    // update order status for driver
    public function updateOrderStatusDriver(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:pending,processing,completed,cancelled,on_the_way,delivered',
        ]);

        $order = Order::find($id);
        $order->status = $request->status;
        $order->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Order status updated driver',
            'data' => $order,
        ]);
    }

    // get payment method
    public function getPaymentMethod()
    {
        $paymentMethods = [
            'e_wallet' => [
                'ID_OVO' => 'OVO',
                'ID_DANA' => 'DANA',
                'ID_LINKAJA' => 'linkAja',
                'ID_SHOPEEPAY' => 'ShopeePay',
            ]
        ];

        return response()->json([
            'message' => 'Payment methods retrieved susccess',
            'payment_methods' => $paymentMethods
        ], 200);
    }

    public function purchaseOrderWithToken(Request $request, $orderId)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:bank_transfer,e_wallet',
            'payment_e_wallet' => 'nullable|required_if:payment_method,e_wallet|string',
            'payment_method_id' => 'nullable|required_if:payment_method,e_wallet|string',
        ]);

        $order = Order::where('id', $orderId)->where('user_id', auth()->id())->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($validated['payment_method'] === 'e_wallet') {
            $apiInstance = new \Xendit\PaymentRequest\PaymentRequestApi();
            $idempotency_key = uniqid();
            $for_user_id = auth()->id();

            $payment_request_parameters = new \Xendit\PaymentRequest\PaymentRequestAuthParameters([
                'reference_id' => 'order-' . $orderId,
                'amount' => $order->total_bill,
                'currency' => 'IDR',
                'payment_method_id' => $validated['payment_method_id'],
                'metadata' => [
                    'order_id' => $orderId,
                    'user_id' => $order->user->id,
                ]
            ]);

            try {
                $result = $apiInstance->createPaymentRequest($idempotency_key, $for_user_id, $payment_request_parameters);

                // Payment::create([
                //     'order_id' => $orderId,
                //     'payment_type' => $validated['payment_method'],
                //     'payment_provider' => $validated['payment_e_wallet'],
                //     'amount' => $order->total_bill,
                //     'status' => 'pending',
                //     'xendit_id' => $result['id'],
                // ]);

                return response()->json(['message' => 'payment created success', 'order' => $order, 'payment' => $result], 200);
            } catch (\Xendit\XenditSdkException $e) {
                return response()->json(['message' => 'Failed to create payment', 'error' => $e->getMessage(), 'full_error' => $e->getFullError()], 50);
            }
        } else {
            $order->status = 'purchase';
            $order->payment_method = $validated['payment_method'];
            $order->save();

            // $this->sendNotificationToRestaurant($order->restaurant_id, 'Order Purchased', 'An order has been purchase')

            return response()->json(['message' => 'Order purchased success', 'order' => $order], 200);
        }
    }
}
