@extends('admin.layouts.app')

@section('content')
    <h3>Dashboard</h3>

    <div class="row mt-4">

        <!-- Total Sellers -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">Total Sellers</h6>
                    <h2 class="fw-bold">
                        {{ \App\Models\Seller::count() }}
                    </h2>
                </div>
            </div>
        </div>

        <!-- Total Items -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted">Total Items</h6>
                    <h2 class="fw-bold">
                        {{ \App\Models\Item::count() }}
                    </h2>
                </div>
            </div>
        </div>

        <!-- Today Sales -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-primary text-white">
                <div class="card-body">
                    <h6>Today Sales</h6>
                    <h3>₹ {{ number_format($todayTotal) }}</h3>
                    <small>Seller Share (60%): ₹ {{ number_format($todayTotal * 0.6) }}</small>
                </div>
            </div>
        </div>

        <!-- Monthly Sales -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-success text-white">
                <div class="card-body">
                    <h6>Monthly Sales</h6>
                    <h3>₹ {{ number_format($monthlyTotal) }}</h3>
                    <small>Seller Share (60%): ₹ {{ number_format($monthlyTotal * 0.6) }}</small>
                </div>
            </div>
        </div>

    </div>

    <div class="row mt-4">

        <!-- Grand Total -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0 bg-dark text-white">
                <div class="card-body">
                    <h6>Grand Total Sales</h6>
                    <h3>₹ {{ number_format($grandTotal) }}</h3>
                    <small>Total Seller Share (40%): ₹ {{ number_format($grandTotal * 0.4) }}</small>
                </div>
            </div>
        </div>

        <!-- Red Flag -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0 bg-danger text-white">
                <div class="card-body">
                    <h6>Red Flag Entries</h6>
                    <h3>{{ $redFlags }}</h3>
                </div>
            </div>
        </div>

    </div>
@endsection
