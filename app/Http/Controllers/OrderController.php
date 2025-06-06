<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Storage;


class OrderController extends Controller
{
    public function form()
    {
        return view('order.form');
    }

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'email' => 'nullable|email',
            'phone' => 'required',
            'address' => 'nullable',
            'vehicle_number' => 'required',
            'stnk_date' => 'required|date',
            'plate_size' => 'required',
            'custom_size' => 'nullable|string',
            'plate_material' => 'required',
            'plate_color' => 'required',
            'custom_plate_color' => 'nullable|string',
            'font_type' => 'required',
            'custom_font_type' => 'nullable|string',
            'finishing' => 'required',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable',
            'stnk_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        if ($request->hasFile('stnk_file')) {
            $file = $request->file('stnk_file');
            $path = $file->store('temp', 'public');
            $validated['stnk_file'] = $path;
            $validated['stnk_file_name'] = $file->getClientOriginalName();
        }

        return view('order.preview', ['data' => $validated]);
    }

        public function submit(Request $request)
    {
        $validated = $request->all();

        $generatedId = 'MS-' . now()->format('dm') . '-' . rand(1000, 9999);

        if (!empty($validated['stnk_file']) && \Storage::disk('public')->exists($validated['stnk_file'])) {
            $newPath = 'stnk_files/' . basename($validated['stnk_file']);
            \Storage::disk('public')->move($validated['stnk_file'], $newPath);
            $validated['stnk_file'] = $newPath;
        }

        $order = Order::create([
            'order_id' => $generatedId,
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'],
            'address' => $validated['address'] ?? null,
            'vehicle_number' => $validated['vehicle_number'],
            'stnk_date' => $validated['stnk_date'],
            'plate_size' => $validated['plate_size'],
            'plate_material' => $validated['plate_material'],
            'plate_color' => $validated['plate_color'],
            'font_type' => $validated['font_type'],
            'finishing' => $validated['finishing'],
            'quantity' => $validated['quantity'],
            'notes' => $validated['notes'] ?? null,
            'stnk_file' => $validated['stnk_file'] ?? null,
        ]);

        return redirect()->route('order.success', ['id' => $order->order_id]);
    }

    public function success($id)
    {
        return view('order.success', ['order_id' => $id]);
    }

        public function trackPage(Request $request)
    {
        $order = null;

        if ($request->has('order_id')) {
            $order = Order::where('order_id', $request->order_id)->first();

            if (!$order) {
                return view('order.track')->with('error', 'ID Pesanan tidak ditemukan.');
            }
        }

        return view('order.track', compact('order'));
    }

        public function track(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string'
        ]);

        $order = Order::where('order_id', $request->order_id)->first();

        if (!$order) {
            return redirect()->route('order.trackPage')->with('error', 'ID Pemesanan tidak ditemukan.');
        }

        return view('order.track', ['order' => $order]);
    }
}
