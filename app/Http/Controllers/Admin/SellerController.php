<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\SellerDocument;
use Illuminate\Http\Request;

class SellerController extends Controller
{
    public function index()
    {
        $sellers = Seller::with('documents')->latest()->get();

        return view('admin.sellers.index', compact('sellers'));
    }

    public function create()
    {
        return view('admin.sellers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'number' => 'required',
            'address' => 'required',
            'documents.*' => 'file|mimes:pdf,jpg,png',
        ]);

        $seller = Seller::create($request->only('name', 'number', 'address'));

        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $path = $file->store('seller_documents', 'public');

                SellerDocument::create([
                    'seller_id' => $seller->id,
                    'file_path' => $path,
                ]);
            }
        }

        return redirect()->route('admin.sellers.index')
            ->with('success', 'Seller created successfully');
    }

    public function edit(Seller $seller)
    {
        return view('admin.sellers.edit', compact('seller'));
    }

    public function update(Request $request, Seller $seller)
    {
        $request->validate([
            'name' => 'required',
            'number' => 'required',
            'address' => 'required',
        ]);

        $seller->update($request->only('name', 'number', 'address'));

        return redirect()->route('admin.sellers.index')
            ->with('success', 'Seller updated successfully');
    }

    public function destroy(Seller $seller)
    {
        $seller->delete();

        return back()->with('success', 'Seller deleted successfully');
    }
}
