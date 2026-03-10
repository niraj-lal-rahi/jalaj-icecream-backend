<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\SellerDocument;
use Illuminate\Http\Request;
use App\Http\Requests\AdminCreateSellerRequest;
use App\Http\Requests\UpdateSellerRequest;

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

    public function store(AdminCreateSellerRequest $request)
    {
        $validated = $request->validated();

        $seller = Seller::create($validated);

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

    public function update(UpdateSellerRequest $request, Seller $seller)
    {
        $validated = $request->validated();

        $seller->update($validated);

        return redirect()->route('admin.sellers.index')
            ->with('success', 'Seller updated successfully');
    }

    public function destroy(Seller $seller)
    {
        $seller->delete();

        return back()->with('success', 'Seller deleted successfully');
    }
}
